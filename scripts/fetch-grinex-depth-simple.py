#!/usr/bin/env python3

import sys
import json

import cloudscraper

def fetch_depth_data(market: str = "usda7a5"):
    api_url = f"https://grinex.io/api/v2/depth?market={market}"
    trading_url = f"https://grinex.io/trading/{market}"

    scraper = cloudscraper.create_scraper(
        browser={
            'browser': 'chrome',
            'platform': 'windows',
            'desktop': True
        }
    )
    
    try:
        response = scraper.get(trading_url, timeout=30)
        response = scraper.get(
            api_url,
            timeout=30,
            headers={
                'Referer': trading_url,
                'Origin': 'https://grinex.io',
                'Accept': 'application/json, text/plain, */*',
            }
        )

        
        if response.status_code == 200:
            try:
                data = response.json()
                return data['bids']
            except json.JSONDecodeError:
                return None
        else:
            print("Response:")
            print(response.text)
            return None
            
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()
        return None


def main():
    market = sys.argv[1] if len(sys.argv) > 1 else "usda7a5"
    result = fetch_depth_data(market)
    sys.exit(0 if result else 1)


if __name__ == "__main__":
    main()

