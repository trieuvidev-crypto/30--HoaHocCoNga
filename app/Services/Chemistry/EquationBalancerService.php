<?php

declare(strict_types=1);

namespace App\Services\Chemistry;

use RuntimeException;

/**
 * Balances a chemical equation given its reactant and product formulas,
 * using deterministic linear algebra — NOT an AI/LLM call. (Per
 * CHEMISTRY_AI.md, an AI tutor may *use* this service to explain a
 * balanced equation, but the balancing itself must be an exact,
 * reproducible algorithm.)
 *
 * Method: build the stoichiometric matrix (one row per distinct element,
 * one column per compound; reactant columns positive, product columns
 * negative), reduce it to row-echelon form using exact fraction
 * arithmetic, then solve for the null space. This covers the standard
 * case taught in the Grade 8-12 curriculum where the solution space is
 * exactly one-dimensional (i.e. there is one essentially-unique smallest
 * whole-number solution) — which is true for the overwhelming majority
 * of textbook reactions. Reactions with a genuinely multi-dimensional
 * solution space (rare, typically only in advanced redox systems with
 * multiple independent half-reactions) are explicitly detected and
 * reported as such rather than silently returning a wrong answer.
 */
final class EquationBalancerService
{
    public function __construct(private readonly ChemicalFormulaParser $parser)
    {
    }

    /**
     * @param array<int, string> $reactantFormulas
     * @param array<int, string> $productFormulas
     * @return array{reactant_coefficients: array<int,int>, product_coefficients: array<int,int>}
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function balance(array $reactantFormulas, array $productFormulas): array
    {
        if (empty($reactantFormulas) || empty($productFormulas)) {
            throw new RuntimeException('Cần ít nhất một chất tham gia và một sản phẩm để cân bằng phương trình.');
        }

        $reactantCounts = array_map([$this->parser, 'parse'], $reactantFormulas);
        $productCounts = array_map([$this->parser, 'parse'], $productFormulas);

        $elements = $this->collectDistinctElements(array_merge($reactantCounts, $productCounts));

        $numReactants = count($reactantFormulas);
        $numProducts = count($productFormulas);
        $numUnknowns = $numReactants + $numProducts;

        // Build the matrix: rows = elements, columns = unknowns (reactants
        // then products), reactant coefficients positive, product
        // coefficients negative (since reactant atoms - product atoms = 0).
        $matrix = [];

        foreach ($elements as $element) {
            $row = [];

            foreach ($reactantCounts as $counts) {
                $row[] = Fraction::fromInt($counts[$element] ?? 0);
            }

            foreach ($productCounts as $counts) {
                $row[] = Fraction::fromInt(-($counts[$element] ?? 0));
            }

            $matrix[] = $row;
        }

        [$reduced, $pivotColumns] = $this->rowEchelonForm($matrix, $numUnknowns);

        $freeColumns = array_values(array_diff(range(0, $numUnknowns - 1), $pivotColumns));

        if (count($freeColumns) === 0) {
            throw new RuntimeException('Phương trình không thể cân bằng: hệ phương trình chỉ có nghiệm bằng 0 (các chất có thể không thực sự phản ứng với nhau như đã cho).');
        }

        if (count($freeColumns) > 1) {
            throw new RuntimeException('Phương trình có nhiều hơn một cách cân bằng độc lập (hệ số không xác định duy nhất) — cần cung cấp thêm điều kiện hoặc bán phản ứng cụ thể.');
        }

        $solution = $this->backSubstitute($reduced, $pivotColumns, $freeColumns[0], $numUnknowns);
        $integerCoefficients = $this->scaleToIntegers($solution);

        if (array_sum(array_map('abs', $integerCoefficients)) === 0) {
            throw new RuntimeException('Không tìm được nghiệm hợp lệ để cân bằng phương trình.');
        }

        // Solution may come out entirely negative depending on which
        // variable was chosen free — normalize sign so reactant
        // coefficients (by convention) are positive.
        if ($integerCoefficients[0] < 0) {
            $integerCoefficients = array_map(fn (int $v) => -$v, $integerCoefficients);
        }

        return [
            'reactant_coefficients' => array_slice($integerCoefficients, 0, $numReactants),
            'product_coefficients' => array_slice($integerCoefficients, $numReactants, $numProducts),
        ];
    }

    /** @return array<int, string> */
    private function collectDistinctElements(array $allCounts): array
    {
        $elements = [];

        foreach ($allCounts as $counts) {
            foreach (array_keys($counts) as $element) {
                $elements[$element] = true;
            }
        }

        return array_keys($elements);
    }

    /**
     * @param array<int, array<int, Fraction>> $matrix
     * @return array{0: array<int, array<int, Fraction>>, 1: array<int, int>} reduced matrix + pivot column indices
     */
    private function rowEchelonForm(array $matrix, int $numUnknowns): array
    {
        $numRows = count($matrix);
        $pivotRow = 0;
        $pivotColumns = [];

        for ($col = 0; $col < $numUnknowns && $pivotRow < $numRows; $col++) {
            $selected = null;

            for ($row = $pivotRow; $row < $numRows; $row++) {
                if (!$matrix[$row][$col]->isZero()) {
                    $selected = $row;
                    break;
                }
            }

            if ($selected === null) {
                continue; // this column has no pivot; it will be a free variable
            }

            [$matrix[$pivotRow], $matrix[$selected]] = [$matrix[$selected], $matrix[$pivotRow]];

            $pivotValue = $matrix[$pivotRow][$col];

            for ($c = 0; $c < $numUnknowns; $c++) {
                $matrix[$pivotRow][$c] = $matrix[$pivotRow][$c]->divide($pivotValue);
            }

            for ($row = 0; $row < $numRows; $row++) {
                if ($row === $pivotRow || $matrix[$row][$col]->isZero()) {
                    continue;
                }

                $factor = $matrix[$row][$col];

                for ($c = 0; $c < $numUnknowns; $c++) {
                    $matrix[$row][$c] = $matrix[$row][$c]->subtract($factor->multiply($matrix[$pivotRow][$c]));
                }
            }

            $pivotColumns[$col] = $pivotRow;
            $pivotRow++;
        }

        return [$matrix, array_keys($pivotColumns)];
    }

    /**
     * @param array<int, array<int, Fraction>> $reduced
     * @param array<int, int> $pivotColumns list of pivot column indices, in
     *        row order — i.e. $pivotColumns[$rowIndex] is the pivot column
     *        for row $rowIndex in $reduced. This ordering holds because
     *        rowEchelonForm() assigns pivotRow sequentially (0, 1, 2, ...)
     *        in the same pass that discovers pivot columns in increasing
     *        column order.
     * @return array<int, Fraction> solution vector, indexed by unknown column
     */
    private function backSubstitute(array $reduced, array $pivotColumns, int $freeColumn, int $numUnknowns): array
    {
        $solution = array_fill(0, $numUnknowns, Fraction::fromInt(0));
        $solution[$freeColumn] = Fraction::fromInt(1);

        foreach ($pivotColumns as $rowIndex => $col) {
            // reduced[$rowIndex][$col] === 1 by construction (RREF), and
            // since there is exactly one free column, the only other
            // possibly-nonzero entry in this row is at $freeColumn:
            //   1 * solution[col] + reduced[rowIndex][freeColumn] * solution[freeColumn] = 0
            $solution[$col] = $reduced[$rowIndex][$freeColumn]->negate();
        }

        return $solution;
    }

    /**
     * @param array<int, Fraction> $solution
     * @return array<int, int>
     */
    private function scaleToIntegers(array $solution): array
    {
        $lcm = 1;

        foreach ($solution as $fraction) {
            $lcm = $this->lcm($lcm, $fraction->denominator);
        }

        $scaled = array_map(
            fn (Fraction $f) => intdiv($f->numerator * $lcm, $f->denominator),
            $solution
        );

        $gcdAll = 0;

        foreach ($scaled as $value) {
            $gcdAll = $this->gcd($gcdAll, abs($value));
        }

        if ($gcdAll > 1) {
            $scaled = array_map(fn (int $v) => intdiv($v, $gcdAll), $scaled);
        }

        return $scaled;
    }

    private function gcd(int $a, int $b): int
    {
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }

        return $a === 0 ? 1 : $a;
    }

    private function lcm(int $a, int $b): int
    {
        return intdiv($a * $b, $this->gcd($a, $b));
    }
}
