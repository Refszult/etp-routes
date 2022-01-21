<?php

namespace App\Exceptions;

class BaseApiException extends \Exception
{
    /**
     * @var \Throwable
     */
    protected $prev;

    /**
     * @var int
     */
    protected $httpCode;

    public function __construct(
        $message = '',
        $code = 0,
        \Throwable $previous = null,
        int $httpCode = 400,
        \Throwable $prev = null
    ) {
        $this->message = $message;
        $this->code = $code;
        $this->prev = $prev;
        $this->httpCode = $httpCode;
    }

    final public function getHttpCode()
    {
        return $this->httpCode;
    }

    final public function getPrev()
    {
        return $this->prev;
    }
}
