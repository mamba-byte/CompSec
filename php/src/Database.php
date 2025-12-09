<?php

declare(strict_types=1);

final class Database
{
    private PDO $pdo;

    private function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function fromEnv(): self
    {
        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $db = Env::get('DB_NAME', 'compsec_lab');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return new self($pdo);
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function insertIngestRun(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO ingest_runs (source_file, start_line, end_line, batch_size, rows_written, duration_ms, avg_hash_time_ms)
            VALUES (:source_file, :start_line, :end_line, :batch_size, :rows_written, :duration_ms, :avg_hash_time_ms)
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function bulkInsertHashes(int $ingestRunId, array $rows): void
    {
        $sql = <<<SQL
            INSERT INTO hashes (ingest_run_id, line_no, plaintext, md5_hash, php_elapsed_ms)
            VALUES (:ingest_run_id, :line_no, :plaintext, :md5_hash, :php_elapsed_ms)
            ON DUPLICATE KEY UPDATE
                plaintext = VALUES(plaintext),
                php_elapsed_ms = VALUES(php_elapsed_ms),
                updated_at = CURRENT_TIMESTAMP
        SQL;

        $stmt = $this->pdo->prepare($sql);
        foreach ($rows as $row) {
            $row['ingest_run_id'] = $ingestRunId;
            $stmt->execute($row);
        }
    }

    public function bulkInsertHashesSha3(int $ingestRunId, array $rows): void
    {
        $sql = <<<SQL
            INSERT INTO hashes (ingest_run_id, line_no, plaintext, sha3_hash, php_elapsed_ms)
            VALUES (:ingest_run_id, :line_no, :plaintext, :sha3_hash, :php_elapsed_ms)
            ON DUPLICATE KEY UPDATE
                plaintext = VALUES(plaintext),
                php_elapsed_ms = VALUES(php_elapsed_ms),
                updated_at = CURRENT_TIMESTAMP
        SQL;

        $stmt = $this->pdo->prepare($sql);
        foreach ($rows as $row) {
            $row['ingest_run_id'] = $ingestRunId;
            $stmt->execute($row);
        }
    }

    public function fetchHashesForExport(?int $limit = null): array
    {
        $sql = 'SELECT md5_hash FROM hashes WHERE cracked_at IS NULL AND md5_hash IS NOT NULL';
        if ($limit !== null) {
            $sql .= ' ORDER BY id LIMIT :lim';
        }
        $stmt = $this->pdo->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function fetchHashesForExportSha3(?int $limit = null): array
    {
        $sql = 'SELECT sha3_hash FROM hashes WHERE cracked_at IS NULL AND sha3_hash IS NOT NULL';
        if ($limit !== null) {
            $sql .= ' ORDER BY id LIMIT :lim';
        }
        $stmt = $this->pdo->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function bulkInsertHashesBlake2b(int $ingestRunId, array $rows): void
    {
        $sql = <<<SQL
            INSERT INTO hashes (ingest_run_id, line_no, plaintext, blake2b_hash, php_elapsed_ms)
            VALUES (:ingest_run_id, :line_no, :plaintext, :blake2b_hash, :php_elapsed_ms)
            ON DUPLICATE KEY UPDATE
                plaintext = VALUES(plaintext),
                php_elapsed_ms = VALUES(php_elapsed_ms),
                updated_at = CURRENT_TIMESTAMP
        SQL;

        $stmt = $this->pdo->prepare($sql);
        foreach ($rows as $row) {
            $row['ingest_run_id'] = $ingestRunId;
            $stmt->execute($row);
        }
    }

    public function bulkInsertHashesArgon2id(int $ingestRunId, array $rows): void
    {
        $sql = <<<SQL
            INSERT INTO hashes (ingest_run_id, line_no, plaintext, argon2id_hash, php_elapsed_ms)
            VALUES (:ingest_run_id, :line_no, :plaintext, :argon2id_hash, :php_elapsed_ms)
            ON DUPLICATE KEY UPDATE
                plaintext = VALUES(plaintext),
                php_elapsed_ms = VALUES(php_elapsed_ms),
                updated_at = CURRENT_TIMESTAMP
        SQL;

        $stmt = $this->pdo->prepare($sql);
        foreach ($rows as $row) {
            $row['ingest_run_id'] = $ingestRunId;
            $stmt->execute($row);
        }
    }

    public function fetchHashesForExportBlake2b(?int $limit = null): array
    {
        $sql = 'SELECT blake2b_hash FROM hashes WHERE cracked_at IS NULL AND blake2b_hash IS NOT NULL';
        if ($limit !== null) {
            $sql .= ' ORDER BY id LIMIT :lim';
        }
        $stmt = $this->pdo->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function fetchHashesForExportArgon2id(?int $limit = null): array
    {
        $sql = 'SELECT argon2id_hash FROM hashes WHERE cracked_at IS NULL AND argon2id_hash IS NOT NULL';
        if ($limit !== null) {
            $sql .= ' ORDER BY id LIMIT :lim';
        }
        $stmt = $this->pdo->prepare($sql);
        if ($limit !== null) {
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function insertHashcatRun(array $data): int
    {
        $sql = <<<SQL
            INSERT INTO hashcat_runs
                (run_name, hash_mode, wordlist, hash_file, options_json, status_json_path, started_at, completed_at, duration_s, hashes_total, hashes_cracked)
            VALUES
                (:run_name, :hash_mode, :wordlist, :hash_file, :options_json, :status_json_path, :started_at, :completed_at, :duration_s, :hashes_total, :hashes_cracked)
        SQL;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function applyPotfile(string $potfile, int $hashcatRunId, ?float $runDuration): int
    {
        if (!is_file($potfile)) {
            throw new InvalidArgumentException("Potfile not found: {$potfile}");
        }

        $lines = file($potfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read potfile {$potfile}");
        }

        $updateSql = <<<SQL
            UPDATE hashes
            SET cracked_at = COALESCE(cracked_at, NOW()),
                crack_run_id = :run_id,
                crack_time_s = COALESCE(crack_time_s, :crack_time_s),
                crack_plaintext = :plaintext
            WHERE md5_hash = :hash
        SQL;
        $stmt = $this->pdo->prepare($updateSql);
        $updated = 0;

        foreach ($lines as $line) {
            [$hash, $plaintext] = array_pad(explode(':', $line, 2), 2, '');
            if ($hash === '') {
                continue;
            }
            $stmt->execute([
                'run_id' => $hashcatRunId,
                'crack_time_s' => $runDuration,
                'plaintext' => $plaintext,
                'hash' => $hash,
            ]);
            $updated += $stmt->rowCount();
        }

        return $updated;
    }

    public function applyPotfileSha3(string $potfile, int $hashcatRunId, ?float $runDuration): int
    {
        if (!is_file($potfile)) {
            throw new InvalidArgumentException("Potfile not found: {$potfile}");
        }

        $lines = file($potfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read potfile {$potfile}");
        }

        $updateSql = <<<SQL
            UPDATE hashes
            SET cracked_at = COALESCE(cracked_at, NOW()),
                crack_run_id = :run_id,
                crack_time_s = COALESCE(crack_time_s, :crack_time_s),
                crack_plaintext = :plaintext
            WHERE sha3_hash = :hash
        SQL;
        $stmt = $this->pdo->prepare($updateSql);
        $updated = 0;

        foreach ($lines as $line) {
            [$hash, $plaintext] = array_pad(explode(':', $line, 2), 2, '');
            if ($hash === '') {
                continue;
            }
            $stmt->execute([
                'run_id' => $hashcatRunId,
                'crack_time_s' => $runDuration,
                'plaintext' => $plaintext,
                'hash' => $hash,
            ]);
            $updated += $stmt->rowCount();
        }

        return $updated;
    }

    public function applyPotfileBlake2b(string $potfile, int $hashcatRunId, ?float $runDuration): int
    {
        if (!is_file($potfile)) {
            throw new InvalidArgumentException("Potfile not found: {$potfile}");
        }

        $lines = file($potfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read potfile {$potfile}");
        }

        $updateSql = <<<SQL
            UPDATE hashes
            SET cracked_at = COALESCE(cracked_at, NOW()),
                crack_run_id = :run_id,
                crack_time_s = COALESCE(crack_time_s, :crack_time_s),
                crack_plaintext = :plaintext
            WHERE blake2b_hash = :hash
        SQL;
        $stmt = $this->pdo->prepare($updateSql);
        $updated = 0;

        foreach ($lines as $line) {
            [$hash, $plaintext] = array_pad(explode(':', $line, 2), 2, '');
            if ($hash === '') {
                continue;
            }
            // Strip $BLAKE2$ prefix if present (Hashcat adds it to potfile)
            $hash = str_replace('$BLAKE2$', '', $hash);
            $stmt->execute([
                'run_id' => $hashcatRunId,
                'crack_time_s' => $runDuration,
                'plaintext' => $plaintext,
                'hash' => $hash,
            ]);
            $updated += $stmt->rowCount();
        }

        return $updated;
    }

    public function applyPotfileArgon2id(string $potfile, int $hashcatRunId, ?float $runDuration): int
    {
        if (!is_file($potfile)) {
            throw new InvalidArgumentException("Potfile not found: {$potfile}");
        }

        $lines = file($potfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException("Unable to read potfile {$potfile}");
        }

        $updateSql = <<<SQL
            UPDATE hashes
            SET cracked_at = COALESCE(cracked_at, NOW()),
                crack_run_id = :run_id,
                crack_time_s = COALESCE(crack_time_s, :crack_time_s),
                crack_plaintext = :plaintext
            WHERE argon2id_hash = :hash
        SQL;
        $stmt = $this->pdo->prepare($updateSql);
        $updated = 0;

        foreach ($lines as $line) {
            [$hash, $plaintext] = array_pad(explode(':', $line, 2), 2, '');
            if ($hash === '') {
                continue;
            }
            $stmt->execute([
                'run_id' => $hashcatRunId,
                'crack_time_s' => $runDuration,
                'plaintext' => $plaintext,
                'hash' => $hash,
            ]);
            $updated += $stmt->rowCount();
        }

        return $updated;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}

