<?php

declare(strict_types=1);

namespace SvkDigital\Currency\Tests\Http;

use DateTimeImmutable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SvkDigital\Currency\Http\CbrXmlClient;
use SvkDigital\Currency\Tests\TestCase;

final class CbrXmlClientTest extends TestCase
{
    public function test_it_parses_rates_xml(): void
    {
        Http::fake([
            'https://example.test/cbr*' => Http::response($this->ratesSoapResponse()),
        ]);

        $client = CbrXmlClient::fromConfig(
            $this->app->make(HttpFactory::class),
            [
                'base_uri' => 'https://example.test/cbr',
                'http' => [
                    'timeout' => 5,
                    'retry' => ['times' => 0, 'sleep' => 0],
                ],
            ]
        );

        $dto = $client->getRatesOnDate(new DateTimeImmutable('2024-11-30'));

        $this->assertCount(1, $dto->rates);
        $this->assertSame('USD', $dto->rates[0]->charCode);
        $this->assertSame(90.1234, $dto->rates[0]->rate);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && $request->url() === 'https://example.test/cbr'
            && str_contains($request->body(), '<GetCursOnDate xmlns="http://web.cbr.ru/">'));
    }

    public function test_it_parses_enum_valutes(): void
    {
        Http::fake([
            'https://example.test/cbr*' => Http::response($this->enumValutesSoapResponse()),
        ]);

        $client = CbrXmlClient::fromConfig(
            $this->app->make(HttpFactory::class),
            [
                'base_uri' => 'https://example.test/cbr',
            ]
        );

        $dto = $client->getEnumValutes();

        $this->assertCount(1, $dto->items);
        $this->assertSame('R01010', $dto->items[0]->internalCode);

        Http::assertSent(fn (Request $request) => $request->hasHeader('SOAPAction')
            && $request->header('SOAPAction')[0] === 'http://web.cbr.ru/EnumValutes');
    }

    public function test_it_fetches_latest_datetime(): void
    {
        Http::fake([
            'https://example.test/cbr*' => Http::response($this->latestDateTimeSoapResponse()),
        ]);

        $client = CbrXmlClient::fromConfig(
            $this->app->make(HttpFactory::class),
            [
                'base_uri' => 'https://example.test/cbr',
            ]
        );

        $latest = $client->getLatestDateTime();

        $this->assertSame('2024-11-30T12:00:00+03:00', $latest->format(DateTimeImmutable::ATOM));
    }

    private function ratesSoapResponse(): string
    {
        $body = <<<'XML'
<GetCursOnDateResponse xmlns="http://web.cbr.ru/">
  <GetCursOnDateResult>
    <diffgr:diffgram xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">
      <ValuteData OnDate="2024-11-30T00:00:00">
        <ValuteCursOnDate>
          <Vname>US Dollar</Vname>
          <Vnom>1</Vnom>
          <Vcurs>90,1234</Vcurs>
          <Vcode>840</Vcode>
          <VchCode>USD</VchCode>
          <VunitRate>90,1234</VunitRate>
        </ValuteCursOnDate>
      </ValuteData>
    </diffgr:diffgram>
  </GetCursOnDateResult>
</GetCursOnDateResponse>
XML;

        return $this->soapEnvelope($body);
    }

    private function enumValutesSoapResponse(): string
    {
        $body = <<<'XML'
<EnumValutesResponse xmlns="http://web.cbr.ru/">
  <EnumValutesResult>
    <diffgr:diffgram xmlns:diffgr="urn:schemas-microsoft-com:xml-diffgram-v1">
      <ValuteData>
        <EnumValutes>
          <EnumValute>
            <Vcode>R01010</Vcode>
            <Vname>Доллар США</Vname>
            <VEngname>US Dollar</VEngname>
            <Vnom>1</Vnom>
            <VcommonCode>USD</VcommonCode>
            <VnumCode>840</VnumCode>
            <VcharCode>USD</VcharCode>
          </EnumValute>
        </EnumValutes>
      </ValuteData>
    </diffgr:diffgram>
  </EnumValutesResult>
</EnumValutesResponse>
XML;

        return $this->soapEnvelope($body);
    }

    private function latestDateTimeSoapResponse(): string
    {
        $body = <<<'XML'
<GetLatestDateTimeResponse xmlns="http://web.cbr.ru/">
  <GetLatestDateTimeResult>2024-11-30T12:00:00+03:00</GetLatestDateTimeResult>
</GetLatestDateTimeResponse>
XML;

        return $this->soapEnvelope($body);
    }

    private function soapEnvelope(string $innerBody): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    {$innerBody}
  </soap:Body>
</soap:Envelope>
XML;
    }
}
