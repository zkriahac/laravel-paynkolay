<?php

namespace Zkriahac\Paynkolay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Zkriahac\Paynkolay\Exceptions\PaynkolayException;

class CardStorageService
{
    private Client $client;
    private HashService $hashService;
    private array $config;

    public function __construct(array $config, HashService $hashService)
    {
        $this->config = $config;
        $this->hashService = $hashService;
        
        $env = $config['environment'] === 'production' ? 'production' : 'sandbox';
        $baseUrl = $config['urls'][$env]['base'];
        
        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout' => 30,
            'verify' => $config['environment'] === 'production',
        ]);
    }

    public function registerCard(array $cardData): array
    {
        // Generate hash for card registration
        $cardData['hashDatav2'] = $this->hashService->generateCardStorageHash([
            'sx' => $this->config['sx'],
            'cardNumber' => $cardData['cardNumber'],
            'cvv' => $cardData['cvv'],
        ], $this->config['merchant_secret']);

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['card_storage_register'];

        try {
            $response = $this->client->post($endpoint, [
                'form_params' => array_merge([
                    'sx' => $this->config['sx'],
                ], $cardData)
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['status']) && $result['status'] !== 'success') {
                throw new PaynkolayException($result['message'] ?? 'Card registration failed');
            }

            return $result;

        } catch (RequestException $e) {
            throw new PaynkolayException('Card registration failed: ' . $e->getMessage());
        }
    }

    public function listCards(string $customerKey): array
    {
        $hashString = $this->config['sx'] . '|' . $customerKey . '|' . $this->config['merchant_secret'];
        $hashData = base64_encode(hash('sha512', $hashString, true));

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['card_storage_list'];

        $response = $this->client->post($endpoint, [
            'form_params' => [
                'sx' => $this->config['sx'],
                'customerKey' => $customerKey,
                'hashDatav2' => $hashData,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function deleteCard(string $customerKey, string $tranId, string $token = ''): array
    {
        $hashString = $this->config['sx'] . '|' . $customerKey . '|' . $tranId . '|' . $token . '|' . $this->config['merchant_secret'];
        $hashData = base64_encode(hash('sha512', $hashString, true));

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['card_storage_delete'];

        $response = $this->client->post($endpoint, [
            'form_params' => [
                'sx' => $this->config['sx'],
                'customerKey' => $customerKey,
                'tranId' => $tranId,
                'token' => $token,
                'hashDatav2' => $hashData,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}