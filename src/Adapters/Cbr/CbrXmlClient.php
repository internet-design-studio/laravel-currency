<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Adapters\Cbr;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use SimpleXMLElement;
use SvkDigital\Currency\Contracts\CurrencyClient;
use SvkDigital\Currency\DTO\CurrencyRateDTO;
use SvkDigital\Currency\DTO\EnumValuteItemDTO;
use SvkDigital\Currency\DTO\EnumValutesDTO;
use SvkDigital\Currency\DTO\RatesOnDateDTO;
use SvkDigital\Currency\Exceptions\CbrClientException;
use SvkDigital\Currency\Exceptions\CurrencyAdapterException;
use Throwable;

final class CbrXmlClient implements CurrencyClient
{
    private const DAILY_RATES_ACTION = 'GetCursOnDate';

    private const ENUM_VALUTES_ACTION = 'EnumValutes';

    private const LATEST_DATE_TIME_ACTION = 'GetLatestDateTime';

    private const SOAP_NAMESPACE = 'http://schemas.xmlsoap.org/soap/envelope/';

    private const SOAP12_NAMESPACE = 'http://www.w3.org/2003/05/soap-envelope';

    private const CBR_NAMESPACE = 'http://web.cbr.ru/';

    public function __construct(
        private readonly Factory $http,
        private readonly string $baseUri,
        private readonly float $timeout,
        private readonly int $retryTimes,
        private readonly int $retrySleep
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(Factory $http, array $config): self
    {
        $httpConfig = $config['http'] ?? [];
        $retryConfig = $httpConfig['retry'] ?? [];

        return new self(
            $http,
            $config['base_uri'] ?? 'https://www.cbr.ru/DailyInfoWebServ/DailyInfo.asmx',
            (float) ($httpConfig['timeout'] ?? 10.0),
            (int) ($retryConfig['times'] ?? 1),
            (int) ($retryConfig['sleep'] ?? 100)
        );
    }

    public function getRatesOnDate(DateTimeInterface $date): RatesOnDateDTO
    {
        $result = $this->soapCall(self::DAILY_RATES_ACTION, [
            'On_date' => $date->format(DateTimeInterface::ATOM),
        ]);

        $dataset = $this->extractDataset($result, 'ValuteData', self::DAILY_RATES_ACTION);

        return $this->mapRatesOnDate($dataset, $date);
    }

    public function getEnumValutes(bool $monthly = false): EnumValutesDTO
    {
        $result = $this->soapCall(self::ENUM_VALUTES_ACTION, [
            'Seld' => $monthly ? 'true' : 'false',
        ]);

        $dataset = $this->extractDataset($result, 'ValuteData', self::ENUM_VALUTES_ACTION);

        return $this->mapEnumValutes($dataset);
    }

    public function getLatestDateTime(): DateTimeImmutable
    {
        $result = $this->soapCall(self::LATEST_DATE_TIME_ACTION);
        $value = mb_trim((string) $result);

        if ($value === '') {
            throw CbrClientException::invalidDateTime('latest rates', $value);
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable $throwable) {
            throw CbrClientException::invalidDateTime('latest rates', $value, $throwable);
        }
    }

    /**
     * @param  array<string, string>  $parameters
     *
     * @throws CurrencyAdapterException
     */
    private function soapCall(string $action, array $parameters = []): SimpleXMLElement
    {
        try {
            $response = $this->pendingRequest()
                ->withHeaders($this->soapHeaders($action))
                ->withBody($this->buildEnvelope($action, $parameters), 'text/xml; charset=utf-8')
                ->post($this->endpoint())
                ->throw();
        } catch (RequestException $e) {
            throw CurrencyAdapterException::requestFailed(
                'CBR',
                $e->getMessage(),
                $e
            );
        } catch (ConnectionException $e) {
            throw CurrencyAdapterException::connectionFailed(
                'CBR',
                $e->getMessage(),
                $e
            );
        }

        $xml = $this->loadXml($response, "{$action} SOAP response");

        return $this->unwrapSoapResult($xml, $action);
    }

    private function pendingRequest(): PendingRequest
    {
        $request = $this->http
            ->timeout($this->timeout)
            ->withHeaders(['Accept' => 'text/xml, application/soap+xml']);

        if ($this->retryTimes > 0) {
            $request = $request->retry($this->retryTimes, $this->retrySleep);
        }

        return $request;
    }

    private function endpoint(): string
    {
        return mb_rtrim($this->baseUri, '/');
    }

    /**
     * @return array<string, string>
     */
    private function soapHeaders(string $action): array
    {
        return [
            'Content-Type' => 'text/xml; charset=utf-8',
            'SOAPAction' => $this->soapActionUri($action),
        ];
    }

    private function soapActionUri(string $action): string
    {
        return self::CBR_NAMESPACE.mb_ltrim($action, '/');
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function buildEnvelope(string $action, array $parameters): string
    {
        $payload = sprintf('<%1$s xmlns="%2$s">', $action, self::CBR_NAMESPACE);

        foreach ($parameters as $name => $value) {
            $payload .= sprintf(
                '<%1$s>%2$s</%1$s>',
                $name,
                $this->escapeValue((string) $value)
            );
        }

        $payload .= sprintf('</%s>', $action);

        return sprintf(
            '<?xml version="1.0" encoding="utf-8"?>'.
            '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="%s">'.
            '<soap:Body>%s</soap:Body>'.
            '</soap:Envelope>',
            self::SOAP_NAMESPACE,
            $payload
        );
    }

    private function escapeValue(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function unwrapSoapResult(SimpleXMLElement $xml, string $action): SimpleXMLElement
    {
        $body = $this->soapBodyFromEnvelope($xml);

        if ($body === null) {
            throw CbrClientException::missingSoapResult($action);
        }

        $this->guardAgainstFault($body);

        $responseNode = $body->children(self::CBR_NAMESPACE)->{$action.'Response'} ?? null;

        if ($responseNode === null) {
            throw CbrClientException::missingSoapResult($action);
        }

        $resultNode = $responseNode->{$action.'Result'} ?? null;

        if ($resultNode === null) {
            throw CbrClientException::missingSoapResult($action);
        }

        return $resultNode;
    }

    private function soapBodyFromEnvelope(SimpleXMLElement $xml): ?SimpleXMLElement
    {
        $body = $xml->children(self::SOAP_NAMESPACE)->Body ?? null;

        if ($body instanceof SimpleXMLElement) {
            return $body;
        }

        $body = $xml->children(self::SOAP12_NAMESPACE)->Body ?? null;

        if ($body instanceof SimpleXMLElement) {
            return $body;
        }

        return $xml->Body ?? null;
    }

    private function guardAgainstFault(SimpleXMLElement $body): void
    {
        $fault = $body->children(self::SOAP_NAMESPACE)->Fault ?? null;

        if (! $fault instanceof SimpleXMLElement) {
            $fault = $body->children(self::SOAP12_NAMESPACE)->Fault ?? null;
        }

        if (! $fault instanceof SimpleXMLElement) {
            return;
        }

        $code = isset($fault->faultcode) ? (string) $fault->faultcode : null;
        $message = isset($fault->faultstring) ? (string) $fault->faultstring : null;

        throw CbrClientException::soapFault($code, $message);
    }

    private function loadXml(Response $response, string $context): SimpleXMLElement
    {
        $body = mb_trim($response->body());

        libxml_use_internal_errors(true);

        try {
            return new SimpleXMLElement($body);
        } catch (Throwable $throwable) {
            throw CbrClientException::unableToParse($context, $throwable);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors(false);
        }
    }

    private function extractDataset(SimpleXMLElement $result, string $nodeName, string $action): SimpleXMLElement
    {
        $xpath = sprintf('//*[local-name()="%s" and not(ancestor::*[local-name()="schema"])]', $nodeName);
        $nodes = $result->xpath($xpath) ?: [];

        if ($nodes !== [] && $nodes[0] instanceof SimpleXMLElement) {
            return $nodes[0];
        }

        throw CbrClientException::missingDataset($action, $nodeName);
    }

    /**
     * @throws Exception
     */
    private function mapRatesOnDate(SimpleXMLElement $xml, DateTimeInterface $fallbackDate): RatesOnDateDTO
    {
        $attributes = $xml->attributes();
        $onDateString = $attributes?->OnDate ? (string) $attributes->OnDate : null;
        $onDate = $onDateString
            ? new DateTimeImmutable($onDateString)
            : DateTimeImmutable::createFromInterface($fallbackDate);

        $rates = [];
        $nodes = $xml->xpath('.//*[local-name()="ValuteCursOnDate"]') ?: [];

        foreach ($nodes as $node) {
            $rates[] = new CurrencyRateDTO(
                name: mb_trim((string) $node->Vname),
                nominal: $this->toInt((string) $node->Vnom),
                rate: $this->toFloat((string) $node->Vcurs),
                numericCode: mb_trim((string) $node->Vcode),
                charCode: mb_trim((string) $node->VchCode),
                unitRate: $this->nullableFloat((string) ($node->VunitRate ?? ''))
            );
        }

        return new RatesOnDateDTO($onDate, $rates);
    }

    private function mapEnumValutes(SimpleXMLElement $xml): EnumValutesDTO
    {
        $items = [];
        $nodes = $xml->xpath('.//*[local-name()="EnumValute"]') ?: [];

        foreach ($nodes as $node) {
            $items[] = new EnumValuteItemDTO(
                internalCode: mb_trim((string) $node->Vcode),
                name: mb_trim((string) $node->Vname),
                englishName: mb_trim((string) $node->VEngname),
                nominal: $this->toInt((string) $node->Vnom),
                commonCode: $this->emptyToNull((string) $node->VcommonCode),
                isoNumericCode: $this->emptyToNull((string) $node->VnumCode),
                isoCharCode: $this->emptyToNull((string) $node->VcharCode)
            );
        }

        return new EnumValutesDTO($items);
    }

    private function toInt(string $value): int
    {
        return (int) mb_trim($value);
    }

    private function toFloat(string $value): float
    {
        return (float) str_replace(',', '.', mb_trim($value));
    }

    private function nullableFloat(string $value): ?float
    {
        $value = mb_trim($value);

        if ($value === '') {
            return null;
        }

        return $this->toFloat($value);
    }

    private function emptyToNull(string $value): ?string
    {
        $value = mb_trim($value);

        return $value === '' ? null : $value;
    }
}
