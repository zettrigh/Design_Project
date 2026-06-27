<?php

namespace Core;

class Result
{
    private function __construct(
        private readonly bool $success,
        private readonly mixed $value = null,
        private readonly string $message = ''
    ) {}

    public static function success(mixed $value = null, string $message = ''): self
    {
        return new self(true, $value, $message);
    }

    public static function failure(string $message, mixed $value = null): self
    {
        return new self(false, $value, $message);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isFailure(): bool
    {
        return !$this->success;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function unwrapOr(mixed $default): mixed
    {
        return $this->success ? $this->value : $default;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data'    => $this->value,
            'message' => $this->message,
        ];
    }
}
