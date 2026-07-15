<?php

declare(strict_types=1);

namespace App\Services\Chemistry;

use App\Core\VietnameseTextNormalizer;
use App\Repositories\ChemistryRepository;
use RuntimeException;

/**
 * Lookup service for the compound library — the backing logic for the
 * "Chemical Search" feature described in CHEMISTRY_DOMAIN.md (search by
 * compound name, formula, common name, alias, with typo tolerance).
 */
final class ChemistryCompoundService
{
    public function __construct(private readonly ChemistryRepository $chemistry)
    {
    }

    public function findByFormula(string $formula): array
    {
        $compound = $this->chemistry->findCompoundByFormula($formula);

        if ($compound === null) {
            throw new RuntimeException('Không tìm thấy hợp chất với công thức đã cho.');
        }

        return $this->attachReactions($compound);
    }

    public function findByUuid(string $uuid): array
    {
        $compound = $this->chemistry->findCompoundByUuid($uuid);

        if ($compound === null) {
            throw new RuntimeException('Không tìm thấy hợp chất.');
        }

        return $this->attachReactions($compound);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query): array
    {
        $normalized = VietnameseTextNormalizer::stripDiacritics(trim($query));

        if ($normalized === '') {
            return [];
        }

        return $this->chemistry->searchCompoundsByAlias($normalized);
    }

    private function attachReactions(array $compound): array
    {
        $compound['reactions'] = $this->chemistry->findReactionsForCompound((int) $compound['id']);

        return $compound;
    }
}
