<?php

namespace Caliente\Common\Laravel\Exceptions;

use Exception;

/**
 * Abstract class to be extended on Custom Exceptions
 */
abstract class BaseException extends Exception
{
    protected $code;
    protected $message;

    public function __construct($message, $code)
    {
        parent::__construct($message);
        $this->message = $message;
        $this->code = $code;
    }
}
