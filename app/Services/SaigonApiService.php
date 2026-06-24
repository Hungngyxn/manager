<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class SaigonApiService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.track123.base_url');
        $this->apiKey = config('services.track123.key');
    }

    protected function client()
    {
        return new Client([
            'headers' => [
                'Track123-Api-Secret' => $this->apiKey,
                'accept' => 'application/json',
                'content-type' => 'application/json'
            ],
            'timeout' => 20
        ]);
    }

    /**
     * REGISTER tracking khi order được tạo
     */
    public function registerTracking(string $trackingNumber, string $carrier)
    {
        try {

            $response = $this->client()->post(
                $this->baseUrl . '/track/import',
                [
                    'json' => [
                        [
                            'trackNo' => $trackingNumber,
                            'courierCode' => $carrier
                        ]
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents(), true);

        } catch (ClientException $e) {

            \Log::error('Track123 register error', [
                'tracking' => $trackingNumber,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * QUERY tracking status (batch)
     */
    public function queryTracking(array $trackingNumbers)
    {
        $trackInfos = [];

        foreach ($trackingNumbers as $track) {
            $trackInfos[] = [
                'trackNo' => $track
            ];
        }

        try {

            $response = $this->client()->post(
                $this->baseUrl . '/track/query',
                [
                    'json' => [
                        'trackNoInfos' => $trackInfos
                    ]
                ]
            );
            return json_decode($response->getBody()->getContents(), true);

        } catch (ClientException $e) {

            \Log::error('Track123 query error', [
                'tracking' => $trackingNumbers,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}