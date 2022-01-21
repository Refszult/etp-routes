<?php

namespace App\Exceptions;

use Throwable;

class NotHandledMQException extends \Exception
{

    protected $MQType = '';

    public function __construct(string $message = '', int $code = 0, string $MQType = '', Throwable $previous = null)
    {
        $this->MQType = $MQType;
        parent::__construct($message, $code, $previous);
    }

    final public function getMQType(): string
    {
        return $this->MQType;
    }
}
