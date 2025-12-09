# Password Hashing & Cracking Experiments

## Overview

This document summarizes experiments conducted to measure and compare hashing performance and cracking resistance across multiple cryptographic hash algorithms: **MD5**, **SHA-3-256**, **BLAKE2b-512**, and **Argon2id**.

## Experiment Setup

- **Hardware**: Apple M3 (GPU/CPU)
- **Wordlist**: rockyou.txt (14,344,392 passwords)
- **Sample Size**: 10,000 passwords per algorithm (for initial testing)
- **Database**: MySQL (compsec_lab)
- **Cracking Tool**: Hashcat v7.1.2

## Hash Algorithms Tested

### 1. MD5 (Message Digest 5)
- **Hashcat Mode**: 0
- **Hash Length**: 32 hex characters (128 bits)
- **Status**: Legacy, cryptographically broken
- **Use Case**: Baseline comparison

### 2. SHA-3-256 (Secure Hash Algorithm 3)
- **Hashcat Mode**: 17400
- **Hash Length**: 64 hex characters (256 bits)
- **Status**: NIST standard, modern secure hash
- **Use Case**: Modern secure hashing

### 3. BLAKE2b-512
- **Hashcat Mode**: 600
- **Hash Length**: 128 hex characters (512 bits)
- **Status**: Fast, secure alternative to SHA-3
- **Use Case**: High-performance secure hashing

### 4. Argon2id
- **Hashcat Mode**: 9900 (may not be directly supported)
- **Hash Format**: `$argon2id$v=19$m=65536,t=4,p=1$salt$hash`
- **Status**: Password hashing competition winner (2015)
- **Use Case**: Password storage (memory-hard, slow by design)

## Implementation Details

### Separate Scripts Created

Each algorithm has dedicated scripts to avoid interference:

**MD5:**
- `php/bin/load_hashes.php`
- `php/bin/export_hashes.php`
- `php/bin/apply_hashcat_results.php`
- `hashcat/run_hashcat.sh`

**SHA-3:**
- `php/bin/load_hashes_sha3.php`
- `php/bin/export_hashes_sha3.php`
- `php/bin/apply_hashcat_results_sha3.php`
- `hashcat/run_hashcat_sha3.sh`

**BLAKE2b:**
- `php/bin/load_hashes_blake2b.php`
- `php/bin/export_hashes_blake2b.php`
- `php/bin/apply_hashcat_results_blake2b.php`
- `hashcat/run_hashcat_blake2b.sh`

**Argon2id:**
- `php/bin/load_hashes_argon2id.php`
- `php/bin/export_hashes_argon2id.php`
- `php/bin/apply_hashcat_results_argon2id.php`
- `hashcat/run_hashcat_argon2id.sh`

### Database Schema

The `hashes` table supports all algorithms simultaneously:
- `md5_hash` (CHAR(32))
- `sha3_hash` (CHAR(64))
- `blake2b_hash` (CHAR(128))
- `argon2id_hash` (VARCHAR(255))

Each hash type has its own unique constraint and can coexist in the same table.

## Analysis & Outputs

### Separate Outputs Per Algorithm

Each algorithm generates its own analysis files:

**JSON Summaries:**
- `out/summary_md5.json`
- `out/summary_sha3.json`
- `out/summary_blake2b.json`
- `out/summary_argon2id.json`

**Visualizations:**
- `out/plots/php_latency_md5.png` - MD5 hashing time distribution
- `out/plots/php_latency_sha3.png` - SHA-3 hashing time distribution
- `out/plots/php_latency_blake2b.png` - BLAKE2b hashing time distribution
- `out/plots/php_latency_argon2id.png` - Argon2id hashing time distribution
- `out/plots/hashcat_crack_times_md5.png` - MD5 cracking time distribution
- `out/plots/hashcat_crack_times_sha3.png` - SHA-3 cracking time distribution
- `out/plots/hashcat_crack_times_blake2b.png` - BLAKE2b cracking time distribution
- `out/plots/hashcat_crack_times_argon2id.png` - Argon2id cracking time distribution

### Analysis Scripts

**Individual Analysis:**
```bash
python python/analyze.py --hash-type md5 --plots out/plots --json out/summary_md5.json
python python/analyze.py --hash-type sha3 --plots out/plots --json out/summary_sha3.json
python python/analyze.py --hash-type blake2b --plots out/plots --json out/summary_blake2b.json
python python/analyze.py --hash-type argon2id --plots out/plots --json out/summary_argon2id.json
```

**Batch Analysis (All Algorithms):**
```bash
python python/analyze_all.py
```

## Actual Results Summary

### Hashing Performance (PHP) - Measured Results

| Algorithm | Total Hashes | Avg Time | Min Time | Max Time | Median Time | Relative Speed |
|-----------|--------------|----------|----------|----------|-------------|----------------|
| MD5       | 10,000       | 0.000175 ms | 0.0001 ms | 0.0468 ms | 0.0002 ms | Fastest (baseline) |
| SHA-3-256 | 10,000       | 0.000313 ms | 0.0002 ms | 0.0320 ms | 0.0003 ms | 1.79x slower than MD5 |
| BLAKE2b   | 10,000       | 0.000276 ms | 0.0002 ms | 0.2149 ms | 0.0002 ms | 1.58x slower than MD5 |
| Argon2id  | 1,000        | 126.786 ms | 118.82 ms | 214.32 ms | 124.35 ms | ~724,000x slower (by design) |

### Cracking Performance (Hashcat) - Actual Results

| Algorithm | Hashcat Mode | Total Hashes | Cracked | Success Rate | Status |
|-----------|--------------|--------------|---------|--------------|--------|
| MD5       | 0            | 10,000       | 10,000  | 100.0%       | ✅ All cracked |
| SHA-3-256 | 17400        | 10,000       | 10,000  | 100.0%       | ✅ All cracked |
| BLAKE2b   | 600          | 10,000       | 10,000  | 100.0%       | ✅ All cracked |
| Argon2id  | N/A          | 1,000        | 0       | 0.0%         | ❌ Not supported by Hashcat |

## Key Observations (Based on Actual Results)

1. **MD5**: 
   - Fastest to hash (0.000175 ms avg)
   - All 10,000 hashes cracked successfully (100%)
   - Cryptographically broken - should not be used in production

2. **SHA-3-256**: 
   - 1.79x slower than MD5 for hashing (0.000313 ms avg)
   - All 10,000 hashes cracked successfully (100%)
   - Modern secure hash with better security properties than MD5

3. **BLAKE2b-512**: 
   - 1.58x slower than MD5 for hashing (0.000276 ms avg)
   - All 10,000 hashes cracked successfully (100%)
   - Competitive with SHA-3, slightly faster, good security

4. **Argon2id**: 
   - Extremely slow by design (126.786 ms avg - ~724,000x slower than MD5)
   - 0% cracked (Hashcat does not support Argon2id format)
   - Memory-hard algorithm designed specifically for password storage
   - The slow hashing time is intentional and provides resistance against brute-force attacks

## Experiment Workflow

1. **Hash Generation**: Use algorithm-specific `load_hashes_*.php` scripts
2. **Export**: Use `export_hashes_*.php` to prepare Hashcat input
3. **Cracking**: Run `hashcat/run_hashcat_*.sh` scripts
4. **Results**: Apply results with `apply_hashcat_results_*.php`
5. **Analysis**: Generate metrics with `python/analyze.py --hash-type <algorithm>`

## Notes

- **Argon2id**: Hashcat v7.1.2 does not support Argon2id format. The algorithm is memory-hard by design and the slow hashing time (126.79 ms vs 0.0002 ms for others) is intentional to resist brute-force attacks.
- All algorithms can be tested simultaneously without interference
- Separate output files ensure clean comparison between algorithms
- MD5, SHA-3, and BLAKE2b all achieved 100% cracking success rate against rockyou.txt wordlist
- Argon2id's memory-hard design makes it unsuitable for fast hashing but ideal for password storage

## Future Experiments

- Compare cracking success rates across algorithms
- Measure memory usage during hashing
- Test with different Argon2id parameters (memory_cost, time_cost)
- Analyze correlation between password complexity and cracking time
- Compare GPU vs CPU performance for each algorithm
