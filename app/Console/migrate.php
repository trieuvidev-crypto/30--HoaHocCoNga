<?php

declare(strict_types=1);

/**
 * Usage: php app/Console/migrate.php
 *
 * Runs every .sql file in database/migrations/ in filename order,
 * tracking applied migrations in `schema_migrations` so re-running is
 * idempotent. This is the only supported way to apply schema changes —
 * never run raw SQL against production by hand.
 */

require_once __DIR__ . '/../../bootstrap/helpers.php';

(function (): void {
    $path = dirname(__DIR__, 2) . '/.env';

    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        putenv(trim($key) . '=' . $_ENV[trim($key)]);
    }
})();

$config = config('database.connections.' . config('database.default'));

$dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $config['host'], $config['port'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

$dbName = $config['database'];
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$config['charset']} COLLATE {$config['collation']}");
$pdo->exec("USE `{$dbName}`");

$migrationsTable = config('database.migrations_table', 'schema_migrations');

$pdo->exec("
    CREATE TABLE IF NOT EXISTS `{$migrationsTable}` (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(191) NOT NULL,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$applied = $pdo->query("SELECT migration FROM `{$migrationsTable}`")->fetchAll(PDO::FETCH_COLUMN);

$migrationFiles = glob(dirname(__DIR__, 2) . '/database/migrations/*.sql');
sort($migrationFiles);

foreach ($migrationFiles as $file) {
    $name = basename($file);

    if (in_array($name, $applied, true)) {
        echo "SKIP  {$name} (already applied)\n";
        continue;
    }

    echo "APPLY {$name} ...\n";

    $sql = file_get_contents($file);

    // Strip '--' line comments before splitting on ';' — a semicolon
    // appearing inside a prose comment (e.g. "the receiving account;
    // configured by an admin") would otherwise be mistaken for a
    // statement terminator by the naive split below. This bug was
    // caught for real: migration 0007 has exactly such a comment.
    $sqlWithoutComments = preg_replace('/^\s*--.*$/m', '', $sql);

    // Naive statement splitting on ';' — sufficient because these migrations
    // contain no stored procedures/triggers with internal DELIMITER blocks,
    // and now that line comments are stripped, no semicolon-in-a-comment
    // can corrupt the split either. If a future migration needs DELIMITER
    // blocks, this runner must be extended first.
    //
    // IMPORTANT: MySQL DDL (CREATE TABLE, ALTER TABLE, ...) causes an
    // implicit commit and is not transactional — wrapping these in
    // beginTransaction()/commit()/rollBack() is meaningless (rollBack()
    // will itself throw once MySQL has already auto-committed a prior
    // DDL statement in the "transaction"). Execute statements directly
    // and report exactly which one failed instead.
    $statements = array_filter(array_map('trim', explode(';', $sqlWithoutComments)));
    $statementIndex = 0;

    try {
        foreach ($statements as $statement) {
            $statementIndex++;

            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
        }

        $stmt = $pdo->prepare("INSERT INTO `{$migrationsTable}` (migration) VALUES (:name)");
        $stmt->execute(['name' => $name]);

        echo "OK    {$name}\n";
    } catch (Throwable $e) {
        echo "FAILED {$name} (statement #{$statementIndex})\n";
        fwrite(STDERR, "Migration {$name} failed at statement #{$statementIndex}: " . $e->getMessage() . "\n");
        fwrite(STDERR, "--- Failing statement ---\n" . ($statement ?? '') . "\n-------------------------\n");
        exit(1);
    }
}

echo "\nAll migrations applied.\n";
