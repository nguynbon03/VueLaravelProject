<?php

declare(strict_types=1);

namespace App\Core\SharedKernel\Domain\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        public int $amountMinor,
        public string $currency = 'VND',
    ) {
        if ($this->amountMinor < 0) {
            throw new InvalidArgumentException('Money amountMinor cannot be negative.');
        }

        if (trim($this->currency) === '') {
            throw new InvalidArgumentException('Currency code cannot be empty.');
        }
    }

    public static function vnd(int $amountMinor): self
    {
        return new self($amountMinor, 'VND');
    }

    public static function zero(string $currency = 'VND'): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->amountMinor > $this->amountMinor) {
            throw new InvalidArgumentException('Resulting Money amount cannot be negative.');
        }

        return new self($this->amountMinor - $other->amountMinor, $this->currency);
    }

    public function subtractClamped(self $other): self
    {
        $this->assertSameCurrency($other);

        $diff = $this->amountMinor - $other->amountMinor;

        return new self($diff > 0 ? $diff : 0, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('Multiplier cannot be negative.');
        }

        return new self($this->amountMinor * $multiplier, $this->currency);
    }

    public function percentage(int $percent): self
    {
        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100.');
        }

        return new self(intdiv($this->amountMinor * $percent, 100), $this->currency);
    }

    public function min(self $other): self
    {
        $this->assertSameCurrency($other);

        return $this->amountMinor <= $other->amountMinor ? $this : $other;
    }

    public function isGreaterThanOrEqual(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountMinor >= $other->amountMinor;
    }

    public function equals(self $other): bool
    {
        return $this->currency === $other->currency
            && $this->amountMinor === $other->amountMinor;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                sprintf('Currency mismatch: cannot combine %s with %s.', $this->currency, $other->currency)
            );
        }
    }
}
