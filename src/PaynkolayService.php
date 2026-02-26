<?php

namespace Zkriahac\Paynkolay;

use Zkriahac\Paynkolay\Services\PaymentService;
use Zkriahac\Paynkolay\Services\CardStorageService;
use Zkriahac\Paynkolay\Services\RecurringPaymentService;
use Zkriahac\Paynkolay\Services\RefundService;
use Zkriahac\Paynkolay\Services\HashService;
class PaynkolayService
{
    private PaymentService $paymentService;
    private CardStorageService $cardStorageService;
    private RecurringPaymentService $recurringPaymentService;
    private RefundService $refundService;
    private array $config;

    public function __construct(
        string $merchantId,
        string $merchantSecret,
        string $sx,
        string $sxCancel,
        string $sxList,
        string $environment = 'sandbox',
        array $urls = [],
        array $callbackUrls = []
    ) { 

        $this->config = [
            'merchant_id' => $merchantId,
            'merchant_secret' => $merchantSecret,
            'sx' => $sx,
            'sx_cancel' => $sxCancel,
            'sx_list' => $sxList,
            'environment' => $environment,
            'currency' => '949',
            'use_3d' => true,
            'urls' => $urls,
            'callback_urls' => $callbackUrls
        ];

        $hashService = new HashService();
        
        $this->paymentService = new PaymentService($this->config, $hashService);
        $this->cardStorageService = new CardStorageService($this->config, $hashService);
        $this->recurringPaymentService = new RecurringPaymentService($this->config, $hashService);
        $this->refundService = new RefundService($this->config, $hashService);
    }

    // Payment methods
    public function makePayment(array $data): array
    {
        return $this->paymentService->makePayment($data);
    }

    public function getInstallments(float $amount, string $cardNumber, bool $isCardValid = false): array
    {
        return $this->paymentService->getInstallments($amount, $cardNumber, $isCardValid);
    }

    public function completePayment(string $referenceCode): array
    {
        return $this->paymentService->completePayment($referenceCode);
    }

    public function listPayments(string $clientRefCode = '', string $startDate = '', string $endDate = '', int $pageCount = 0, int $pageSize = 25): array
    {
        return $this->paymentService->listPayments($clientRefCode, $startDate, $endDate, $pageCount, $pageSize);
    }

    // Card storage methods
    public function registerCard(array $data): array
    {
        return $this->cardStorageService->registerCard($data);
    }

    public function listCards(string $customerKey): array
    {
        return $this->cardStorageService->listCards($customerKey);
    }

    public function deleteCard(string $customerKey, string $tranId, string $token = ''): array
    {
        return $this->cardStorageService->deleteCard($customerKey, $tranId, $token);
    }

    // Recurring payment methods
    public function createRecurringPayment(array $data): array
    {
        return $this->recurringPaymentService->createRecurringPayment($data);
    }

    public function cancelRecurringPayment(string $instructionNumber): array
    {
        return $this->recurringPaymentService->cancelRecurringPayment($instructionNumber);
    }
    public function listRecurringPayments(array $filters = []): array
    {
        return $this->recurringPaymentService->listRecurringPayments($filters);
    }

    // Refund methods
    public function cancelPayment(string $referenceCode, string $trxDate, float $amount): array
    {
        return $this->refundService->cancel($referenceCode, $trxDate, $amount);
    }

    public function refundPayment(string $referenceCode, string $trxDate, float $amount): array
    {
        return $this->refundService->refund($referenceCode, $trxDate, $amount);
    }

    // Utility methods
    public function generateClientRefCode(string $prefix = ''): string
    {
        return $prefix . uniqid() . time();
    }

    public function validateCallback(array $data): bool
    {
        $hashService = new HashService();
        return $hashService->validateHash(
            $data,
            $this->config['merchant_secret'],
            $data['hashDatav2'] ?? ''
        );
    }

    // Get services directly
    public function payment(): PaymentService
    {
        return $this->paymentService;
    }

    public function card(): CardStorageService
    {
        return $this->cardStorageService;
    }

    public function recurring(): RecurringPaymentService
    {
        return $this->recurringPaymentService;
    }

    public function refund(): RefundService
    {
        return $this->refundService;
    }
}