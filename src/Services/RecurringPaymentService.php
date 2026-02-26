<?php

namespace Zkriahac\Paynkolay\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Zkriahac\Paynkolay\Exceptions\PaynkolayException;
use Zkriahac\Paynkolay\Models\Transaction;

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

        // Create transaction record
        $transaction = Transaction::create([
            'client_ref_code' => $data['ClientRefCode'],
            'amount' => $data['Amount'],
            'currency' => $this->config['currency'] ?? '949',
            'status' => 'pending',
            'transaction_type' => 'RECURRING',
            'use_3d' => false,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'request_data' => $payload,
        ]);

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['recurring_create'];
        \Log::debug('[createRecurringPayment] post payload', $payload);
        try {
            $response = $this->client->post($endpoint, [
                'json' => $payload
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['RESPONSE_CODE']) && $result['RESPONSE_CODE'] == 0) {
                // Update transaction with error
                $transaction->update([
                    'status' => 'failed',
                    'response_data' => $result,
                ]);
                
                throw new PaynkolayException($result['RESPONSE_DATA'] ?? 'Recurring payment creation failed');
            }

            // Update transaction with success response
            $transaction->update([
                'response_data' => $result,
            ]);

            return $result;

        } catch (RequestException $e) {
            // Update transaction with error
            $transaction->update([
                'status' => 'failed',
                'response_data' => ['error' => $e->getMessage()],
            ]);
            
            throw new PaynkolayException('Recurring payment creation failed: ' . $e->getMessage());
        }
    }

    public function cancelRecurringPayment(string $instructionNumber): array
    {
        $hashString = $this->config['sx'] . '|' . $instructionNumber . '|' . $this->config['merchant_secret'];
        $hashData = base64_encode(hash('sha512', $hashString, true));

        $env = $this->config['environment'] === 'production' ? 'production' : 'sandbox';
        $endpoint = $this->config['urls'][$env]['recurring_cancel'];

        $requestData = [
            'sx' => $this->config['sx'],
            'InstructionNumber' => $instructionNumber,
            'hashDatav2' => $hashData,
        ];

        try {
            $response = $this->client->post($endpoint, [
                'json' => $requestData
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['RESPONSE_CODE']) && $result['RESPONSE_CODE'] == 0) {
                // Log cancellation failure
                Transaction::create([
                    'status' => 'failed',
                    'transaction_type' => 'RECURRING_CANCEL',
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'request_data' => $requestData,
                    'response_data' => $result,
                ]);
                
                throw new PaynkolayException($result['RESPONSE_DATA'] ?? 'Recurring payment cancellation failed');
            }

            // Log successful cancellation
            Transaction::create([
                'status' => 'success',
                'transaction_type' => 'RECURRING_CANCEL',
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'request_data' => $requestData,
                'response_data' => $result,
            ]);

            return $result;
        } catch (RequestException $e) {
            // Log request exception
            Transaction::create([
                'status' => 'failed',
                'transaction_type' => 'RECURRING_CANCEL',
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'request_data' => $requestData,
                'response_data' => ['error' => $e->getMessage()],
            ]);
            
            throw new PaynkolayException('Recurring payment cancellation failed: ' . $e->getMessage());
        }
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