<?php

namespace Zkriahac\Paynkolay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Zkriahac\Paynkolay\Exceptions\PaynkolayException;

class PaymentService
{
    private Client $client;
    private HashService $hashService;
    private array $config;
    private string $baseUrl;

    public function __construct(array $config, HashService $hashService)
    {
        $this->config = $config;
        $this->hashService = $hashService;
        
        $env = $config['environment'] === 'production' ? 'production' : 'sandbox';
        $this->baseUrl = $config['urls'][$env]['base'];
        
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30,
            'verify' => $config['environment'] === 'production',
            'headers' => [
                'User-Agent' => 'Laravel-Paynkolay/1.0',
                'Accept' => 'application/json',
            ]
        ]);
    }

    public function makePayment(array $paymentData): array
    {
        // Set defaults
        $paymentData = array_merge([
            'environment' => 'API',
            'currencyNumber' => $this->config['currency'] ?? '949',
            'use3D' => $this->config['use_3d'] ? 'true' : 'false',
            'transactionType' => 'SALES',
            'rnd' => now()->format('d-m-Y H:i:s'),
        ], $paymentData);

        // Generate hash
        $paymentData['hashDatav2'] = $this->hashService->generatePaymentHash([
            'sx' => $this->config['sx'],
            'clientRefCode' => $paymentData['clientRefCode'],
            'amount' => $paymentData['amount'],
            'successUrl' => $paymentData['successUrl'],
            'failUrl' => $paymentData['failUrl'],
            'rnd' => $paymentData['rnd'],
            'customerKey' => $paymentData['csCustomerKey'] ?? '',
        ], $this->config['merchant_secret']);

        try {
            $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
            $endpoint = $this->config['urls'][$env]['payment'];
            
            $response = $this->client->post($endpoint, [
                'form_params' => $paymentData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            if (isset($responseData['status']) && $responseData['status'] !== 'success') {
                throw new PaynkolayException($responseData['message'] ?? 'Payment failed');
            }

            return $responseData;

        } catch (RequestException $e) {
            throw new PaynkolayException('Payment request failed: ' . $e->getMessage());
        }
    }

    public function getInstallments(float $amount, string $cardNumber, bool $isCardValid = false): array
    {
        $hashString = $this->config['sx'] . '|' . $amount . '|' . $cardNumber . '|' . $isCardValid . '|' . $this->config['merchant_secret'];
        $hashData = base64_encode(hash('sha512', $hashString, true));

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['payment_installments'];

        $response = $this->client->post($endpoint, [
            'form_params' => [
                'sx' => $this->config['sx'],
                'amount' => $amount,
                'cardNumber' => $cardNumber,
                'iscardvalid' => $isCardValid ? 'true' : 'false',
                'hashData' => $hashData,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function completePayment(string $referenceCode): array
    {
        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['payment'] . '/CompletePayment';

        $response = $this->client->post($endpoint, [
            'form_params' => [
                'sx' => $this->config['sx'],
                'referenceCode' => $referenceCode,
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }

    public function createPayByLink(array $data): array
    {
        $data = array_merge([
            'sx' => $this->config['sx'],
            'rnd' => date('YmdHis'),
            'use3D' => 'true',
            'currencyCode' => '949',
            'transactionType' => 'SALES',
        ], $data);

        $data['hashDatav2'] = $this->hashService->generatePaymentHash([
            'sx' => $data['sx'],
            'clientRefCode' => $data['clientRefCode'],
            'amount' => $data['amount'],
            'successUrl' => $data['successUrl'],
            'failUrl' => $data['failUrl'],
            'rnd' => $data['rnd'],
            'customerKey' => $data['customerKey'] ?? '',
        ], $this->config['merchant_secret']);

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['pay_by_link_create'];

        $response = $this->client->post($endpoint, [
            'form_params' => $data
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}