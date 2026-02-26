<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Merchant Credentials
    |--------------------------------------------------------------------------
    */
    'merchant_id' => env('PAYNKOLAY_MERCHANT_ID'),
    'merchant_secret' => env('PAYNKOLAY_MERCHANT_SECRET'),
    'sx' => env('PAYNKOLAY_SX'),
    'sx_cancel' => env('PAYNKOLAY_SX_CANCEL'),
    'sx_list' => env('PAYNKOLAY_SX_LIST'),
    
    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    | Options: 'sandbox', 'production'
    */
    'environment' => env('PAYNKOLAY_ENVIRONMENT', 'sandbox'),
    
    /*
    |--------------------------------------------------------------------------
    | API URLs
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'sandbox' => [
            'base' => 'https://paynkolaytest.nkolayislem.com.tr',
            'payment' => '/Vpos/v1/Payment',
            'complete_payment' => '/Vpos/v1/CompletePayment',
            'payment_installments' => '/Vpos/Payment/PaymentInstallments',
            'cancel_refund' => '/Vpos/v1/CancelRefundPayment',
            'payment_list' => '/Vpos/Payment/PaymentList',
            'card_storage_register' => '/Vpos/Payment/CardStorageCardRegister',
            'card_storage_list' => '/Vpos/Payment/CardStorageCardList',
            'card_storage_delete' => '/Vpos/Payment/CardStorageCardDelete',
            'recurring_create' => '/Vpos/api/RecurringPaymentCreate',
            'recurring_cancel' => '/Vpos/api/RecurringPaymentCancel',
            'recurring_list' => '/Vpos/api/RecurringPaymentList',
            'pre_auth_approve' => '/Vpos/Payment/PreAuthorizationAprove',
            'pre_auth_cancel' => '/Vpos/Payment/PreAuthorizationCancel',
            'pre_auth_list' => '/Vpos/Payment/PreAuthorizationList',
            'by_link_create' => '/Vpos/by-link-create',
            'by_link_url_remove' => '/Vpos/by-link-url-remove',
            'pay_by_link_create' => '/Vpos/pay-by-link-create',
            'merchant_info' => '/Vpos/Payment/GetMerchandInformation',
        ],
        'production' => [
            'base' => 'https://secure.nkolayislem.com.tr',
            'payment' => '/Vpos/v1/Payment',
            'complete_payment' => '/Vpos/v1/CompletePayment',
            'payment_installments' => '/Vpos/Payment/PaymentInstallments',
            'cancel_refund' => '/Vpos/v1/CancelRefundPayment',
            'payment_list' => '/Vpos/Payment/PaymentList',
            'card_storage_register' => '/Vpos/Payment/CardStorageCardRegister',
            'card_storage_list' => '/Vpos/Payment/CardStorageCardList',
            'card_storage_delete' => '/Vpos/Payment/CardStorageCardDelete',
            'recurring_create' => '/Vpos/api/RecurringPaymentCreate',
            'recurring_cancel' => '/Vpos/api/RecurringPaymentCancel',
            'recurring_list' => '/Vpos/api/RecurringPaymentList',
            'pre_auth_approve' => '/Vpos/Payment/PreAuthorizationAprove',
            'pre_auth_cancel' => '/Vpos/Payment/PreAuthorizationCancel',
            'pre_auth_list' => '/Vpos/Payment/PreAuthorizationList',
            'by_link_create' => '/Vpos/by-link-create',
            'by_link_url_remove' => '/Vpos/by-link-url-remove',
            'pay_by_link_create' => '/Vpos/pay-by-link-create',
            'merchant_info' => '/Vpos/Payment/GetMerchandInformation',
        ]
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Callback URLs
    |--------------------------------------------------------------------------
    */
    'callback_urls' => [
        'success' => env('PAYNKOLAY_SUCCESS_URL', '/payment/callback/success'),
        'fail' => env('PAYNKOLAY_FAIL_URL', '/payment/callback/fail'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */
    'currency' => env('PAYNKOLAY_CURRENCY', '949'), // 949 = TL
    'default_installment' => env('PAYNKOLAY_DEFAULT_INSTALLMENT', 1),
    'use_3d' => env('PAYNKOLAY_USE_3D', true),
    
    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('PAYNKOLAY_LOGGING_ENABLED', true),
        'channel' => env('PAYNKOLAY_LOGGING_CHANNEL', 'stack'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    */
    'timezone' => env('PAYNKOLAY_TIMEZONE', 'Europe/Istanbul'),
    
    /*
    |--------------------------------------------------------------------------
    | Transaction Models
    |--------------------------------------------------------------------------
    | Customize the models used for storing transactions and saved cards
    */
    'models' => [
        'transaction' => \Zkriahac\Paynkolay\Models\Transaction::class,
        'saved_card' => \Zkriahac\Paynkolay\Models\SavedCard::class
    ],
];