<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class ChemistryRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findElementBySymbol(string $symbol): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM chemistry_elements WHERE symbol = :symbol',
            ['symbol' => $symbol]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllElements(): array
    {
        return $this->db->fetchAll('SELECT * FROM chemistry_elements ORDER BY atomic_number ASC');
    }

    public function findCompoundByFormula(string $formula): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM chemistry_compounds WHERE formula = :formula AND deleted_at IS NULL',
            ['formula' => $formula]
        );
    }

    public function findCompoundByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM chemistry_compounds WHERE uuid = :uuid AND deleted_at IS NULL',
            ['uuid' => $uuid]
        );
    }

    /**
     * Typo-tolerant search: matches against the normalized alias table
     * seeded in Phase 1 (accent-stripped, lowercase). Falls back to a
     * LIKE scan when no exact normalized match is found, so a partial
     * or slightly misspelled query still surfaces results.
     *
     * @return array<int, array<string, mixed>>
     */
    public function searchCompoundsByAlias(string $normalizedQuery, int $limit = 20): array
    {
        $exact = $this->db->fetchAll(
            "SELECT DISTINCT c.* FROM chemistry_compounds c
             INNER JOIN chemistry_compound_aliases a ON a.compound_id = c.id
             WHERE a.alias_normalized = :query AND c.deleted_at IS NULL
             LIMIT {$limit}",
            ['query' => $normalizedQuery]
        );

        if (!empty($exact)) {
            return $exact;
        }

        return $this->db->fetchAll(
            "SELECT DISTINCT c.* FROM chemistry_compounds c
             INNER JOIN chemistry_compound_aliases a ON a.compound_id = c.id
             WHERE a.alias_normalized LIKE :query AND c.deleted_at IS NULL
             LIMIT {$limit}",
            ['query' => '%' . $normalizedQuery . '%']
        );
    }

    /** @return array<int, array<string, mixed>> reactions this compound participates in */
    public function findReactionsForCompound(int $compoundId): array
    {
        return $this->db->fetchAll(
            'SELECT DISTINCT r.* FROM chemistry_reactions r
             INNER JOIN chemistry_reaction_participants p ON p.reaction_id = r.id
             WHERE p.compound_id = :compound_id AND r.deleted_at IS NULL
             ORDER BY r.created_at DESC',
            ['compound_id' => $compoundId]
        );
    }

    public function findReactionByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM chemistry_reactions WHERE uuid = :uuid AND deleted_at IS NULL',
            ['uuid' => $uuid]
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function findParticipantsForReaction(int $reactionId): array
    {
        return $this->db->fetchAll(
            'SELECT p.*, c.formula_display, c.name_vi FROM chemistry_reaction_participants p
             LEFT JOIN chemistry_compounds c ON c.id = p.compound_id
             WHERE p.reaction_id = :reaction_id
             ORDER BY p.sort_order ASC',
            ['reaction_id' => $reactionId]
        );
    }
}
