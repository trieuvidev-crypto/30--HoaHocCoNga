<?php

declare(strict_types=1);

namespace App\Services\Chemistry;

use App\Repositories\ChemistryRepository;
use RuntimeException;

/**
 * Implements the calculators listed in CHEMISTRY_DOMAIN.md §Chemical
 * Calculator. Every method returns not just a result but also the
 * formula and step-by-step explanation used, per that document's
 * requirement that "every calculator should display: Formula,
 * Explanation, Steps, Result".
 */
final class ChemistryCalculatorService
{
    public function __construct(
        private readonly ChemicalFormulaParser $formulaParser,
        private readonly ChemistryRepository $chemistry
    ) {
    }

    /**
     * Molar mass (g/mol) of a compound from its formula, computed from
     * seeded atomic masses — not a hardcoded lookup table.
     *
     * @throws RuntimeException if the formula references an unseeded element
     */
    public function molarMass(string $formula): array
    {
        $elementCounts = $this->formulaParser->parse($formula);
        $steps = [];
        $total = 0.0;

        foreach ($elementCounts as $symbol => $count) {
            $element = $this->chemistry->findElementBySymbol($symbol);

            if ($element === null) {
                throw new RuntimeException("Không tìm thấy nguyên tố '{$symbol}' trong cơ sở dữ liệu.");
            }

            $mass = (float) $element['atomic_mass'];
            $contribution = $mass * $count;
            $total += $contribution;

            $steps[] = "{$symbol}: {$count} × {$mass} = " . round($contribution, 3) . ' g/mol';
        }

        return [
            'formula' => 'M = Σ (số nguyên tử × khối lượng nguyên tử)',
            'steps' => $steps,
            'result' => round($total, 3),
            'unit' => 'g/mol',
            'explanation' => "Khối lượng mol của {$formula} bằng tổng khối lượng nguyên tử của tất cả nguyên tử trong phân tử.",
        ];
    }

    /**
     * pH from H+ concentration (mol/L): pH = -log10([H+])
     */
    public function pH(float $hydrogenConcentrationMolPerLiter): array
    {
        if ($hydrogenConcentrationMolPerLiter <= 0) {
            throw new RuntimeException('Nồng độ H+ phải lớn hơn 0.');
        }

        $ph = -log10($hydrogenConcentrationMolPerLiter);

        return [
            'formula' => 'pH = -log₁₀[H⁺]',
            'steps' => ["pH = -log₁₀({$hydrogenConcentrationMolPerLiter}) = " . round($ph, 3)],
            'result' => round($ph, 3),
            'unit' => null,
            'explanation' => $ph < 7
                ? 'Dung dịch có tính axit (pH < 7).'
                : ($ph > 7 ? 'Dung dịch có tính bazơ (pH > 7).' : 'Dung dịch trung tính (pH = 7).'),
        ];
    }

    /**
     * Dilution: C1 × V1 = C2 × V2. Provide exactly 3 of the 4 values
     * (as null for the unknown) to solve for the fourth.
     */
    public function dilution(?float $c1, ?float $v1, ?float $c2, ?float $v2): array
    {
        $unknowns = array_filter([
            'c1' => $c1 === null,
            'v1' => $v1 === null,
            'c2' => $c2 === null,
            'v2' => $v2 === null,
        ]);

        if (count($unknowns) !== 1) {
            throw new RuntimeException('Cần cung cấp chính xác 3 trong 4 giá trị (C1, V1, C2, V2) để tính giá trị còn lại.');
        }

        $unknown = array_key_first($unknowns);

        $result = match ($unknown) {
            'c1' => ($c2 * $v2) / $v1,
            'v1' => ($c2 * $v2) / $c1,
            'c2' => ($c1 * $v1) / $v2,
            'v2' => ($c1 * $v1) / $c2,
        };

        return [
            'formula' => 'C₁V₁ = C₂V₂',
            'steps' => ["Giải phương trình C₁V₁ = C₂V₂ cho ẩn " . strtoupper($unknown)],
            'result' => round($result, 4),
            'unit' => str_starts_with($unknown, 'c') ? 'mol/L' : 'L',
            'explanation' => 'Định luật bảo toàn số mol chất tan khi pha loãng: số mol trước và sau khi pha loãng không đổi.',
            'solved_for' => $unknown,
        ];
    }

    /**
     * Basic stoichiometry: given moles of a known substance and the
     * mole ratio (from a balanced equation) to a target substance,
     * compute moles and mass of the target.
     */
    public function stoichiometry(float $knownMoles, int $knownCoefficient, int $targetCoefficient, string $targetFormula): array
    {
        if ($knownCoefficient <= 0 || $targetCoefficient <= 0) {
            throw new RuntimeException('Hệ số phương trình phải lớn hơn 0.');
        }

        $targetMoles = $knownMoles * $targetCoefficient / $knownCoefficient;
        $molarMassResult = $this->molarMass($targetFormula);
        $targetMass = $targetMoles * $molarMassResult['result'];

        return [
            'formula' => 'n(chất cần tìm) = n(chất đã biết) × (hệ số chất cần tìm / hệ số chất đã biết)',
            'steps' => [
                "n({$targetFormula}) = {$knownMoles} × ({$targetCoefficient}/{$knownCoefficient}) = " . round($targetMoles, 4) . ' mol',
                "m({$targetFormula}) = n × M = " . round($targetMoles, 4) . " × {$molarMassResult['result']} = " . round($targetMass, 3) . ' g',
            ],
            'result' => [
                'moles' => round($targetMoles, 4),
                'mass_grams' => round($targetMass, 3),
                'molar_mass' => $molarMassResult['result'],
            ],
            'explanation' => 'Tỉ lệ mol giữa các chất trong phản ứng bằng đúng tỉ lệ hệ số cân bằng của chúng trong phương trình hóa học.',
        ];
    }
}
