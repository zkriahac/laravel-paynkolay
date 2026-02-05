<?php

namespace Zkriahac\Paynkolay\Exceptions;

use Exception;

class PaynkolayException extends Exception
{
    protected $code = 500;
    
    public function __construct($message = "Paynkolay payment error", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    public function render($request)
    {
        return response()->json([
            'error' => true,
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 500);
    }
}