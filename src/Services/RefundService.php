<?php

namespace Zkriahac\Paynkolay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Zkriahac\Paynkolay\Exceptions\PaynkolayException;

class RefundService
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

    public function cancelOrRefund(array $data): array
    {
        $hashData = $this->hashService->generateCancelRefundHash([
            'sx' => $this->config['sx_cancel'],
            'referenceCode' => $data['referenceCode'],
            'type' => $data['type'],
            'amount' => $data['amount'],
            'trxDate' => $data['trxDate'],
        ], $this->config['merchant_secret']);

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['cancel_refund'];

        try {
            $response = $this->client->post($endpoint, [
                'form_params' => array_merge($data, [
                    'sx' => $this->config['sx_cancel'],
                    'hashDatav2' => $hashData,
                ])
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['RESPONSE_CODE']) && $result['RESPONSE_CODE'] == 0) {
                throw new PaynkolayException($result['RESPONSE_DATA'] ?? 'Cancel/Refund failed');
            }

            return $result;

        } catch (RequestException $e) {
            throw new PaynkolayException('Cancel/Refund failed: ' . $e->getMessage());
        }
    }

    public function cancel(string $referenceCode, string $trxDate, float $amount): array
    {
        return $this->cancelOrRefund([
            'referenceCode' => $referenceCode,
            'type' => 'cancel',
            'trxDate' => $trxDate,
            'amount' => $amount,
        ]);
    }

    public function refund(string $referenceCode, string $trxDate, float $amount): array
    {
        return $this->cancelOrRefund([
            'referenceCode' => $referenceCode,
            'type' => 'refund',
            'trxDate' => $trxDate,
            'amount' => $amount,
        ]);
    }
}