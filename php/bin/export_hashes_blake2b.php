#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Env.php';
require_once __DIR__ . '/../src/Database.php';

Env::load(__DIR__ . '/../.env');
Env::load(__DIR__ . '/../env.local');

$options = getopt('', [
    'out:',
    'limit::',
]);

if (!isset($options['out'])) {
    fwrite(STDERR, "Usage: export_hashes_blake2b.php --out /path/to/hashes.txt [--limit 10000]\n");
    exit(1);
}

$outFile = $options['out'];
$limit = isset($options['limit']) ? (int)$options['limit'] : null;

$db = Database::fromEnv();
$hashes = $db->fetchHashesForExportBlake2b($limit);

// Hashcat mode 600 expects format: $BLAKE2$<hash>
$formattedHashes = array_map(fn($h) => "\$BLAKE2\${$h}", $hashes);

file_put_contents($outFile, implode(PHP_EOL, $formattedHashes) . PHP_EOL);

fprintf(STDOUT, "Wrote %d BLAKE2b hashes to %s\n", count($hashes), $outFile);

