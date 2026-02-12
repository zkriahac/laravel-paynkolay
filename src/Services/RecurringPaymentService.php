<?php

namespace Zkriahac\Paynkolay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Zkriahac\Paynkolay\Exceptions\PaynkolayException;

class RecurringPaymentService
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
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function createRecurringPayment(array $data): array
    {
        $hashData = $this->hashService->generateRecurringHash([
            'sx' => $this->config['sx'],
            'Gsm' => $data['Gsm'],
            'Amount' => $data['Amount'],
            'ClientRefCode' => $data['ClientRefCode'],
        ], $this->config['merchant_secret']);

        $payload = array_merge($data, [
            'sx' => $this->config['sx'],
            'hashDatav2' => $hashData,
        ]);

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['recurring_create'];

        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['RESPONSE_CODE']) && $result['RESPONSE_CODE'] == 0) {
                throw new PaynkolayException($result['RESPONSE_DATA'] ?? 'Recurring payment creation failed');
            }

            return $result;

        } catch (RequestException $e) {
            throw new PaynkolayException('Recurring payment creation failed: ' . $e->getMessage());
        }
    }

    public function cancelRecurringPayment(string $instructionNumber): array
    {
        $hashString = $this->config['sx'] . '|' . $instructionNumber . '|' . $this->config['merchant_secret'];
        $hashData = base64_encode(hash('sha512', $hashString, true));

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['recurring_cancel'];

        $response = $this->client->post($endpoint, [
            'json' => [
                'sx' => $this->config['sx'],
                'InstructionNumber' => $instructionNumber,
                'hashDatav2' => $hashData,
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (isset($result['RESPONSE_CODE']) && $result['RESPONSE_CODE'] == 0) {
            throw new PaynkolayException($result['RESPONSE_DATA'] ?? 'Recurring payment cancellation failed');
        }

        return $result;
    }

    public function listRecurringPayments(array $filters = []): array
    {
        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['recurring_list'];

        $response = $this->client->post($endpoint, [
            'json' => array_merge([
                'sx' => $this->config['sx_list'],
            ], $filters)
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}