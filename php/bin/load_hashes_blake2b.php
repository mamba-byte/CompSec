#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Database.php';

Env::load(__DIR__ . '/../.env');
Env::load(__DIR__ . '/../env.local');

$options = getopt('', [
    'file::',
    'batch-size::',
    'start-line::',
    'max-lines::',
]);

$file = $options['file'] ?? Env::get('ROCKYOU_PATH');
if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Rockyou file not found. Provide via --file or ROCKYOU_PATH.\n");
    exit(1);
}

$batchSize = isset($options['batch-size']) ? (int)$options['batch-size'] : Env::getInt('BATCH_SIZE', 1000);
$startLine = isset($options['start-line']) ? (int)$options['start-line'] : Env::getInt('START_LINE', 1);
$maxLines = isset($options['max-lines']) ? (int)$options['max-lines'] : null;

if (!function_exists('sodium_crypto_generichash')) {
    fwrite(STDERR, "Error: Sodium extension not available. BLAKE2b requires sodium_crypto_generichash().\n");
    exit(1);
}

$db = Database::fromEnv();

$handle = fopen($file, 'rb');
if ($handle === false) {
    throw new RuntimeException("Unable to open {$file}");
}

$currentLine = 0;
$written = 0;
$batch = [];
$hashTimes = [];
$startTs = hrtime(true);

while (($line = fgets($handle)) !== false) {
    $currentLine++;
    if ($currentLine < $startLine) {
        continue;
    }
    if ($maxLines !== null && $written >= $maxLines) {
        break;
    }

    $plaintext = trim($line, "\r\n");
    $hashStart = hrtime(true);
    $blake2bHash = bin2hex(sodium_crypto_generichash($plaintext, '', 64)); // BLAKE2b-512 = 64 bytes = 128 hex chars
    $hashDuration = (hrtime(true) - $hashStart) / 1_000_000; // ms

    $batch[] = [
        'line_no' => $currentLine,
        'plaintext' => $plaintext,
        'blake2b_hash' => $blake2bHash,
        'php_elapsed_ms' => $hashDuration,
    ];
    $hashTimes[] = $hashDuration;
    $written++;

    if (count($batch) >= $batchSize) {
        flushBatch($db, $file, $startLine, $currentLine, $batch, $hashTimes, $startTs, $written);
        $batch = [];
        $hashTimes = [];
        $startTs = hrtime(true);
    }
}

if ($batch !== []) {
    flushBatch($db, $file, $startLine, $currentLine, $batch, $hashTimes, $startTs, $written);
}

fclose($handle);
fprintf(STDOUT, "Completed BLAKE2b ingestion. Processed %d lines.\n", $written);

function flushBatch(Database $db, string $file, int $startLine, int $currentLine, array $batch, array $hashTimes, float $startTs, int $written): void
{
    $durationMs = (hrtime(true) - $startTs) / 1_000_000;
    $avgHash = array_sum($hashTimes) / max(count($hashTimes), 1);

    $db->beginTransaction();
    try {
        $runId = $db->insertIngestRun([
            'source_file' => $file,
            'start_line' => $startLine,
            'end_line' => $currentLine,
            'batch_size' => count($batch),
            'rows_written' => count($batch),
            'duration_ms' => (int)$durationMs,
            'avg_hash_time_ms' => $avgHash,
        ]);
        $db->bulkInsertHashesBlake2b($runId, $batch);
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    $throughput = count($batch) / ($durationMs / 1000);
    fprintf(
        STDOUT,
        "Batch complete | lines %d-%d | rows %d | %.2f ms avg | %.0f H/s\n",
        $currentLine - count($batch) + 1,
        $currentLine,
        count($batch),
        $avgHash,
        $throughput
    );
}

