<?php

namespace Zkriahac\Paynkolay\Services;

use Zkriahac\Paynkolay\Exceptions\HashValidationException;

class HashService
{
    public function generateHashV2(array $params, string $secretKey): string
    {
        $hashString = implode('|', $params);
        return base64_encode(hash('sha512', $hashString, true));
    }

    public function generatePaymentHash(array $data, string $secretKey): string
    {
        $params = [
            $data['sx'],
            $data['clientRefCode'],
            $data['amount'],
            $data['successUrl'],
            $data['failUrl'],
            $data['rnd'],
            $data['customerKey'] ?? '',
            $secretKey
        ];
        
        return $this->generateHashV2($params, $secretKey);
    }

    public function generateCancelRefundHash(array $data, string $secretKey): string
    {
        $params = [
            $data['sx'],
            $data['referenceCode'],
            $data['type'],
            $data['amount'],
            $data['trxDate'],
            $secretKey
        ];
        
        return $this->generateHashV2($params, $secretKey);
    }

    public function generateCardStorageHash(array $data, string $secretKey): string
    {
        $params = [
            $data['sx'],
            $data['cardNumber'],
            $data['cvv'],
            $secretKey
        ];
        
        return $this->generateHashV2($params, $secretKey);
    }

    public function generateRecurringHash(array $data, string $secretKey): string
    {
        $params = [
            $data['sx'],
            $data['Gsm'],
            $data['Amount'],
            $data['ClientRefCode'],
            $secretKey
        ];
        
        return $this->generateHashV2($params, $secretKey);
    }

    public function validateHash(array $data, string $secretKey, string $receivedHash): bool
    {
        $generatedHash = $this->generateHashV2($data, $secretKey);
        
        if (!hash_equals($generatedHash, $receivedHash)) {
            throw new HashValidationException('Invalid hash provided');
        }
        
        return true;
    }
}