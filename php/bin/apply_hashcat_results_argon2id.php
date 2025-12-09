#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Database.php';

Env::load(__DIR__ . '/../.env');
Env::load(__DIR__ . '/../env.local');

$options = getopt('', [
    'pot:',
    'run-name:',
    'wordlist::',
    'hashes::',
    'status::',
    'duration::',
    'mode::',
]);

foreach (['pot', 'run-name'] as $required) {
    if (!isset($options[$required])) {
        fwrite(STDERR, "Usage: apply_hashcat_results_argon2id.php --pot hashcat.pot --run-name exp1 [--wordlist rockyou.txt --hashes hashes.txt --duration 123.4 --mode 9900]\n");
        fwrite(STDERR, "Note: Argon2id may not be directly supported by Hashcat. This script is for completeness.\n");
        exit(1);
    }
}

$potfile = $options['pot'];
$runName = $options['run-name'];
$wordlist = $options['wordlist'] ?? 'rockyou.txt';
$hashFile = $options['hashes'] ?? 'hashcat/hashes_argon2id.txt';
$statusPath = $options['status'] ?? null;
$duration = isset($options['duration']) ? (float)$options['duration'] : null;
$mode = isset($options['mode']) ? (int)$options['mode'] : 9900;

$db = Database::fromEnv();
$now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

$runId = $db->insertHashcatRun([
    'run_name' => $runName,
    'hash_mode' => $mode,
    'wordlist' => $wordlist,
    'hash_file' => $hashFile,
    'options_json' => json_encode(['potfile' => $potfile]),
    'status_json_path' => $statusPath,
    'started_at' => $now,
    'completed_at' => $now,
    'duration_s' => $duration,
    'hashes_total' => null,
    'hashes_cracked' => null,
]);

$updated = $db->applyPotfileArgon2id($potfile, $runId, $duration);

fprintf(STDOUT, "Applied Argon2id potfile %s. Updated %d rows (run id %d).\n", $potfile, $updated, $runId);

