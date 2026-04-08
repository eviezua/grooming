<?php

namespace App\Message;

final class AuthMessage
{
    public function __construct(
        public int $masterId,
        public string $token,
    ) {
    }
}
