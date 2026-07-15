<?php

declare(strict_types=1);

namespace App\Services\Chemistry;

use RuntimeException;

/**
 * Parses a plain-text chemical formula (e.g. "H2SO4", "Ca(OH)2",
 * "Al2(SO4)3") into a map of element symbol => atom count. Supports
 * arbitrarily nested parentheses. Ionic charge suffixes (e.g. "SO4^2-")
 * and physical-state suffixes are not part of the formula itself and
 * must be stripped by the caller before parsing.
 */
final class ChemicalFormulaParser
{
    /**
     * @return array<string, int> element symbol => total atom count
     * @throws RuntimeException on malformed input
     */
    public function parse(string $formula): array
    {
        $formula = trim($formula);
        $position = 0;

        $result = $this->parseGroup($formula, $position);

        if ($position !== strlen($formula)) {
            throw new RuntimeException("Công thức hóa học không hợp lệ: '{$formula}' (ký tự thừa ở vị trí {$position}).");
        }

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function parseGroup(string $formula, int &$position): array
    {
        $counts = [];
        $length = strlen($formula);

        while ($position < $length) {
            $char = $formula[$position];

            if ($char === ')') {
                break; // handled by the caller that opened this group
            }

            if ($char === '(') {
                $position++; // consume '('
                $subCounts = $this->parseGroup($formula, $position);

                if ($position >= $length || $formula[$position] !== ')') {
                    throw new RuntimeException("Công thức hóa học thiếu dấu ')' đóng: '{$formula}'.");
                }

                $position++; // consume ')'
                $multiplier = $this->readNumber($formula, $position, 1);

                foreach ($subCounts as $element => $count) {
                    $counts[$element] = ($counts[$element] ?? 0) + $count * $multiplier;
                }

                continue;
            }

            if (ctype_upper($char)) {
                $symbol = $char;
                $position++;

                while ($position < $length && ctype_lower($formula[$position])) {
                    $symbol .= $formula[$position];
                    $position++;
                }

                $count = $this->readNumber($formula, $position, 1);
                $counts[$symbol] = ($counts[$symbol] ?? 0) + $count;

                continue;
            }

            throw new RuntimeException("Ký tự không hợp lệ trong công thức hóa học: '{$char}' trong '{$formula}'.");
        }

        return $counts;
    }

    private function readNumber(string $formula, int &$position, int $default): int
    {
        $start = $position;

        while ($position < strlen($formula) && ctype_digit($formula[$position])) {
            $position++;
        }

        if ($position === $start) {
            return $default;
        }

        return (int) substr($formula, $start, $position - $start);
    }
}
