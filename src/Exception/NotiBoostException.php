<?php

namespace NotiBoost\Exception;

class NotiBoostException extends \Exception
{
    private $statusCode;
    private $response;

    public function __construct(string $message, int $statusCode = 0, array $response = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->response = $response;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}

