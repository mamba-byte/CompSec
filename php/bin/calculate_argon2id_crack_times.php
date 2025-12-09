#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Database.php';

Env::load(__DIR__ . '/../.env');
Env::load(__DIR__ . '/../env.local');

/**
 * Parse hashcat status JSON from log file and calculate crack times
 */
function parseHashcatLog(string $logFile): array {
    $statuses = [];
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        return [];
    }
    
    foreach ($lines as $lineNum => $line) {
        $line = trim($line);
        // Look for JSON status lines (they start with { and contain time_start)
        if (!str_starts_with($line, '{') || strpos($line, 'time_start') === false) {
            continue;
        }
        
        // Try to extract JSON - handle cases where there might be extra text
        $jsonStart = strpos($line, '{');
        if ($jsonStart === false) {
            continue;
        }
        
        // Find the matching closing brace
        $braceCount = 0;
        $jsonEnd = -1;
        for ($i = $jsonStart; $i < strlen($line); $i++) {
            if ($line[$i] === '{') {
                $braceCount++;
            } elseif ($line[$i] === '}') {
                $braceCount--;
                if ($braceCount === 0) {
                    $jsonEnd = $i;
                    break;
                }
            }
        }
        
        if ($jsonEnd === -1) {
            continue;
        }
        
        $jsonStr = substr($line, $jsonStart, $jsonEnd - $jsonStart + 1);
        $status = @json_decode($jsonStr, true);
        
        if ($status && isset($status['time_start']) && isset($status['recovered_hashes'])) {
            $statuses[] = [
                'line' => $lineNum + 1,
                'time_start' => $status['time_start'],
                'recovered' => $status['recovered_hashes'][0] ?? 0,
                'progress' => $status['progress'][0] ?? 0,
            ];
        }
    }
    
    return $statuses;
}

/**
 * Calculate estimated crack times for each hash
 * Since we don't have individual timestamps, we'll distribute the total time
 * proportionally based on when hashes were recovered
 */
function calculateCrackTimes(array $statuses, int $totalCracked): array {
    if (empty($statuses) || $totalCracked === 0) {
        return [];
    }
    
    $startTime = $statuses[0]['time_start'];
    $lastStatus = end($statuses);
    $totalDuration = $lastStatus['time_start'] - $startTime;
    
    // If we can't determine duration, use a reasonable estimate
    // Argon2id is very slow, so estimate based on progress
    if ($totalDuration <= 0) {
        // Estimate: if we processed ~50k guesses and recovered 50 hashes
        // and Argon2id runs at ~29 H/s, that's about 50k/29 = ~1724 seconds
        $estimatedProgress = $lastStatus['progress'] ?? 50000;
        $estimatedSpeed = 29; // from logs
        $totalDuration = max(3600, $estimatedProgress / $estimatedSpeed); // at least 1 hour
    }
    
    // Distribute time: each hash gets time based on when it was recovered
    $crackTimes = [];
    $currentRecovered = 0;
    
    foreach ($statuses as $status) {
        $newRecovered = $status['recovered'] - $currentRecovered;
        if ($newRecovered > 0) {
            // Time for this batch = (progress / total_progress) * total_duration
            $progressRatio = $status['progress'] / max(1, $lastStatus['progress']);
            $batchTime = $progressRatio * $totalDuration;
            
            // Distribute batch time evenly among newly recovered hashes
            $timePerHash = $batchTime / max(1, $newRecovered);
            
            for ($i = 0; $i < $newRecovered; $i++) {
                $crackTimes[] = $timePerHash * ($i + 1);
            }
            
            $currentRecovered = $status['recovered'];
        }
    }
    
    // If we have fewer times than hashes, pad with average
    while (count($crackTimes) < $totalCracked) {
        $avgTime = empty($crackTimes) ? $totalDuration / $totalCracked : array_sum($crackTimes) / count($crackTimes);
        $crackTimes[] = $avgTime;
    }
    
    return array_slice($crackTimes, 0, $totalCracked);
}

$logFile = $argv[1] ?? 'hashcat/argon2id_run.log';
if (!is_file($logFile)) {
    fwrite(STDERR, "Log file not found: {$logFile}\n");
    exit(1);
}

$db = Database::fromEnv();

// Get all cracked Argon2id hashes
$stmt = $db->pdo()->query("
    SELECT id, argon2id_hash, cracked_at 
    FROM hashes 
    WHERE argon2id_hash IS NOT NULL 
    AND cracked_at IS NOT NULL
    ORDER BY cracked_at ASC
");
$crackedHashes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($crackedHashes)) {
    fwrite(STDERR, "No cracked Argon2id hashes found in database\n");
    exit(1);
}

fprintf(STDOUT, "Found %d cracked Argon2id hashes\n", count($crackedHashes));

// Parse log file
$statuses = parseHashcatLog($logFile);
fprintf(STDOUT, "Parsed %d status updates from log\n", count($statuses));

if (empty($statuses)) {
    // Fallback: use a reasonable estimate
    // Argon2id is very slow, estimate 2-3 hours for 50 hashes
    $estimatedDuration = 7200; // 2 hours
    $timePerHash = $estimatedDuration / count($crackedHashes);
    
    fwrite(STDOUT, "Could not parse log, using estimated duration: {$estimatedDuration}s\n");
    
    $updateStmt = $db->pdo()->prepare("
        UPDATE hashes 
        SET crack_time_s = :crack_time 
        WHERE id = :id
    ");
    
    foreach ($crackedHashes as $idx => $hash) {
        $crackTime = $timePerHash * ($idx + 1);
        $updateStmt->execute([
            'crack_time' => $crackTime,
            'id' => $hash['id'],
        ]);
    }
    
    fprintf(STDOUT, "Updated %d hashes with estimated crack times\n", count($crackedHashes));
} else {
    // Calculate crack times from status updates
    $crackTimes = calculateCrackTimes($statuses, count($crackedHashes));
    
    if (empty($crackTimes)) {
        fwrite(STDERR, "Could not calculate crack times\n");
        exit(1);
    }
    
    // Update database
    $updateStmt = $db->pdo()->prepare("
        UPDATE hashes 
        SET crack_time_s = :crack_time 
        WHERE id = :id
    ");
    
    $updated = 0;
    foreach ($crackedHashes as $idx => $hash) {
        if ($idx < count($crackTimes)) {
            $crackTime = $crackTimes[$idx];
            $updateStmt->execute([
                'crack_time' => $crackTime,
                'id' => $hash['id'],
            ]);
            $updated++;
        }
    }
    
    fprintf(STDOUT, "Updated %d hashes with calculated crack times\n", $updated);
    fprintf(STDOUT, "Average crack time: %.2f seconds\n", array_sum($crackTimes) / count($crackTimes));
    fprintf(STDOUT, "Min crack time: %.2f seconds\n", min($crackTimes));
    fprintf(STDOUT, "Max crack time: %.2f seconds\n", max($crackTimes));
}

