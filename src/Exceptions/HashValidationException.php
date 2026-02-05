<?php

namespace Zkriahac\Paynkolay\Exceptions;

class HashValidationException extends PaynkolayException
{
    protected $code = 400;
    
    public function __construct($message = "Hash validation failed")
    {
        parent::__construct($message);
    }
}