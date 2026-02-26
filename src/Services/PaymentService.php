<?php

namespace Zkriahac\Paynkolay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Zkriahac\Paynkolay\Exceptions\PaynkolayException;
use Zkriahac\Paynkolay\Models\Transaction;

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
            'sx' => $this->config['sx'],
            'merchantSecret' => $this->config['merchant_secret'],
        ], $paymentData);

        // Generate hash
        $paymentData['hashDatav2'] = $this->hashService->generatePaymentHash([
            'sx' => $paymentData['sx'],
            'clientRefCode' => $paymentData['clientRefCode'],
            'amount' => $paymentData['amount'],
            'successUrl' => $paymentData['successUrl'],
            'failUrl' => $paymentData['failUrl'],
            'rnd' => $paymentData['rnd'],
            'customerKey' => $paymentData['csCustomerKey'] ?? '',
        ], $paymentData['merchantSecret']);

        // Create transaction record
        $transaction = Transaction::create([
            'client_ref_code' => $paymentData['clientRefCode'],
            'amount' => $paymentData['amount'],
            'currency' => $paymentData['currencyNumber'],
            'status' => 'pending',
            'transaction_type' => $paymentData['transactionType'],
            'use_3d' => $paymentData['use3D'] === 'true',
            'customer_key' => $paymentData['csCustomerKey'] ?? null,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'request_data' => $paymentData,
        ]);

        try {
            $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
            $endpoint = $this->config['urls'][$env]['payment'];
            \Log::debug('[makePayment] paymentData', [
                    'paymentData' => $paymentData,
                    'endpoint' => $endpoint,
                ]);
            $response = $this->client->post($endpoint, [
                'form_params' => $paymentData,
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $apiResponse = $responseData['response'] ?? $responseData;
            
            if (!isset($apiResponse['RESPONSE_CODE']) || $apiResponse['RESPONSE_CODE'] == 0) {
                $errorMsg = $apiResponse['RESPONSE_MESSAGE'] ?? $apiResponse['RESPONSE_DATA'] ?? $apiResponse['message'] ?? 'Payment failed';
                
                // Update transaction with error response
                $transaction->update([
                    'status' => 'failed',
                    'response_data' => $apiResponse,
                ]);
                
                throw new PaynkolayException($errorMsg);
            }

            // Update transaction with success response
            $transaction->update([
                'reference_code' => $apiResponse['REFERENCE_CODE'] ?? null,
                'response_data' => $apiResponse,
            ]);

            return $apiResponse;

        } catch (RequestException $e) {
            // Update transaction with request exception
            $transaction->update([
                'status' => 'failed',
                'response_data' => ['error' => $e->getMessage()],
            ]);
            
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

    public function listPayments(string $clientRefCode = '', string $startDate = '', string $endDate = '', int $pageCount = 0, int $pageSize = 25): array
    {
        \Log::debug('[listPayments] Config values', [
            'sx_list' => $this->config['sx_list'],
            'merchant_secret' => $this->config['merchant_secret'],
        ]);

        // Hash format (matching Postman): sx | startDate | endDate | clientRefCode | merchant_secret
        $hashParams = [
            $this->config['sx_list'],
            $startDate,
            $endDate,
            $clientRefCode,
            $this->config['merchant_secret']
        ];
        $hashData = $this->hashService->generateHashV2($hashParams, $this->config['merchant_secret']);

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['payment_list'];

        $formParams = [
            'sx' => $this->config['sx_list'],
            'startDate' => $startDate,
            'endDate' => $endDate,
            'clientRefCode' => $clientRefCode,
            'hashDatav2' => $hashData,
            'pageCount' => $pageCount,
            'pageSize' => $pageSize,
        ];

        \Log::debug('[listPayments] Request', [
            'endpoint' => $endpoint,
            'hashParams' => implode('|', $hashParams),
            'formParams' => $formParams,
        ]);

        $response = $this->client->post($endpoint, [
            'form_params' => $formParams
        ]);
        
        $body = $response->getBody()->getContents();
        $decoded = json_decode($body, true);
        
        // Handle double-encoded JSON
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }
        
        \Log::debug('[listPayments] Response', [
            'clientRefCode' => $clientRefCode,
            'decoded' => $decoded,
        ]);
        
        return is_array($decoded) ? $decoded : [];
    }

    public function completePayment(string $referenceCode): array
    {
        try {
            $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
            $endpoint = $this->config['urls'][$env]['complete_payment'];

            $response = $this->client->post($endpoint, [
                'form_params' => [
                    'sx' => $this->config['sx'],
                    'referenceCode' => $referenceCode,
                ]
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Update transaction with completion response
            $transaction = Transaction::where('reference_code', $referenceCode)->first();
            if ($transaction) {
                $transaction->update([
                    'status' => isset($responseData['RESPONSE_CODE']) && $responseData['RESPONSE_CODE'] != 0 ? 'success' : 'failed',
                    'response_data' => $responseData,
                    'paid_at' => isset($responseData['RESPONSE_CODE']) && $responseData['RESPONSE_CODE'] != 0 ? now() : null,
                ]);
            }

            return $responseData;
        } catch (RequestException $e) {
            // Update transaction with error
            $transaction = Transaction::where('reference_code', $referenceCode)->first();
            if ($transaction) {
                $transaction->update([
                    'status' => 'failed',
                    'response_data' => ['error' => $e->getMessage()],
                ]);
            }
            
            throw new PaynkolayException('Complete payment failed: ' . $e->getMessage());
        }
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

        // Create transaction record for pay by link
        $transaction = Transaction::create([
            'client_ref_code' => $data['clientRefCode'],
            'amount' => $data['amount'],
            'currency' => $data['currencyCode'],
            'status' => 'pending',
            'transaction_type' => $data['transactionType'],
            'use_3d' => $data['use3D'] === 'true',
            'customer_key' => $data['customerKey'] ?? null,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'request_data' => $data,
        ]);

        try {
            $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
            $endpoint = $this->config['urls'][$env]['pay_by_link_create'];

            $response = $this->client->post($endpoint, [
                'form_params' => $data
            ]);

            $responseData = json_decode($response->getBody()->getContents(), true);
            
            // Update transaction with response
            $transaction->update([
                'response_data' => $responseData,
            ]);

            return $responseData;
        } catch (RequestException $e) {
            // Update transaction with error
            $transaction->update([
                'status' => 'failed',
                'response_data' => ['error' => $e->getMessage()],
            ]);
            
            throw new PaynkolayException('Pay by link creation failed: ' . $e->getMessage());
        }
    }
}