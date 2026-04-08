<?php

namespace App\Message;

final class AdminMessage
{
    public function __construct(
        private array $data
    ) {}

    public function getData(): array
    {
        return $this->data;
    }
}
