# Experiment Results Summary

## Overview

This document contains the **actual measured results** from password hashing and cracking experiments conducted on December 9, 2025.

## Experiment Configuration

- **Hardware**: Apple M3 (GPU/CPU)
- **Wordlist**: rockyou.txt (14,344,392 passwords)
- **Sample Size**: 
  - MD5, SHA-3, BLAKE2b: 10,000 passwords each
  - Argon2id: 1,000 passwords (limited due to slow hashing)
- **Database**: MySQL (compsec_lab)
- **Cracking Tool**: Hashcat v7.1.2

## Hashing Performance Results (PHP)

| Algorithm | Total Hashes | Avg Time (ms) | Min Time (ms) | Max Time (ms) | Median Time (ms) | Relative to MD5 |
|-----------|--------------|---------------|--------------|---------------|------------------|-----------------|
| **MD5**       | 10,000 | 0.000175 | 0.0001 | 0.0468 | 0.0002 | 1.00x (baseline) |
| **SHA-3-256** | 10,000 | 0.000313 | 0.0002 | 0.0320 | 0.0003 | 1.79x slower |
| **BLAKE2b**   | 10,000 | 0.000276 | 0.0002 | 0.2149 | 0.0002 | 1.58x slower |
| **Argon2id**  | 1,000  | 126.786  | 118.82 | 214.32 | 124.35 | ~724,000x slower |

### Key Findings

1. **MD5 is fastest**: 0.000175 ms average hashing time
2. **SHA-3 is 1.79x slower** than MD5 but still very fast (0.000313 ms)
3. **BLAKE2b is 1.58x slower** than MD5, competitive with SHA-3
4. **Argon2id is intentionally slow**: 126.79 ms average (724,000x slower than MD5) - this is by design for password security

## Cracking Performance Results (Hashcat)

| Algorithm | Hashcat Mode | Total Hashes | Cracked | Success Rate | Status |
|-----------|--------------|--------------|---------|--------------|--------|
| **MD5**       | 0      | 10,000 | 10,000 | 100.0% | ✅ All cracked |
| **SHA-3-256** | 17400  | 10,000 | 10,000 | 100.0% | ✅ All cracked |
| **BLAKE2b**   | 600    | 10,000 | 10,000 | 100.0% | ✅ All cracked |
| **Argon2id**  | N/A    | 1,000  | 0      | 0.0%   | ❌ Not supported |

### Key Findings

1. **All fast hashes were 100% cracked**: MD5, SHA-3, and BLAKE2b all achieved 100% success rate against rockyou.txt
2. **Argon2id not cracked**: Hashcat v7.1.2 does not support Argon2id format
3. **Cracking speed**: All three supported algorithms were cracked very quickly (within seconds)

## Performance Comparison

### Hashing Speed Ranking (Fastest to Slowest)

1. **MD5**: 0.000175 ms (fastest)
2. **BLAKE2b**: 0.000276 ms (1.58x slower)
3. **SHA-3**: 0.000313 ms (1.79x slower)
4. **Argon2id**: 126.786 ms (724,000x slower - by design)

### Cracking Resistance Ranking (Most to Least Resistant)

1. **Argon2id**: Not crackable with Hashcat (memory-hard design)
2. **SHA-3, BLAKE2b, MD5**: All cracked 100% (no significant difference in this test)

## Conclusions

1. **MD5 should not be used**: Despite being fastest, it's cryptographically broken and all hashes were cracked
2. **SHA-3 and BLAKE2b are similar**: Both are modern secure hashes with comparable performance
3. **Argon2id is for password storage**: The extreme slowness (126 ms vs 0.0002 ms) is intentional and provides resistance against brute-force attacks
4. **Wordlist attacks are effective**: All fast hashes were 100% cracked against rockyou.txt, demonstrating the importance of strong passwords

## Output Files

All results are available in:
- JSON summaries: `out/summary_*.json`
- Visualization plots: `out/plots/php_latency_*.png` and `out/plots/hashcat_crack_times_*.png`

See `docs/EXPERIMENTS.md` for detailed methodology and `docs/ANALYSIS_GUIDE.md` for analysis instructions.

