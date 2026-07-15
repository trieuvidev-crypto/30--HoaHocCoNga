<?php

declare(strict_types=1);

namespace App\Services\Chemistry;

/**
 * Exact rational arithmetic (numerator/denominator, always reduced,
 * denominator always positive). Equation balancing requires exact
 * fractions during Gaussian elimination — floating point would
 * silently accumulate rounding error and occasionally produce a wrong
 * integer coefficient after scaling.
 */
final class Fraction
{
    public readonly int $numerator;
    public readonly int $denominator;

    public function __construct(int $numerator, int $denominator = 1)
    {
        if ($denominator === 0) {
            throw new \InvalidArgumentException('Mẫu số không thể bằng 0.');
        }

        if ($denominator < 0) {
            $numerator = -$numerator;
            $denominator = -$denominator;
        }

        $gcd = self::gcd(abs($numerator), $denominator);
        $gcd = $gcd === 0 ? 1 : $gcd;

        $this->numerator = intdiv($numerator, $gcd);
        $this->denominator = intdiv($denominator, $gcd);
    }

    public static function fromInt(int $value): self
    {
        return new self($value, 1);
    }

    public function add(Fraction $other): self
    {
        return new self(
            $this->numerator * $other->denominator + $other->numerator * $this->denominator,
            $this->denominator * $other->denominator
        );
    }

    public function subtract(Fraction $other): self
    {
        return new self(
            $this->numerator * $other->denominator - $other->numerator * $this->denominator,
            $this->denominator * $other->denominator
        );
    }

    public function multiply(Fraction $other): self
    {
        return new self($this->numerator * $other->numerator, $this->denominator * $other->denominator);
    }

    public function divide(Fraction $other): self
    {
        if ($other->numerator === 0) {
            throw new \DivisionByZeroError('Chia cho 0 trong quá trình cân bằng phương trình.');
        }

        return new self($this->numerator * $other->denominator, $this->denominator * $other->numerator);
    }

    public function isZero(): bool
    {
        return $this->numerator === 0;
    }

    public function negate(): self
    {
        return new self(-$this->numerator, $this->denominator);
    }

    public function toFloat(): float
    {
        return $this->numerator / $this->denominator;
    }

    private static function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a === 0 ? 1 : $a;
    }
}
