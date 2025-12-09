# Analysis Guide - Separate Outputs Per Algorithm

## Overview

Each hash algorithm (MD5, SHA-3, BLAKE2b, Argon2id) generates **separate output files** to avoid confusion and enable clean comparisons.

## Output File Structure

```
out/
├── summary_md5.json          # MD5 metrics
├── summary_sha3.json         # SHA-3 metrics
├── summary_blake2b.json      # BLAKE2b metrics
├── summary_argon2id.json     # Argon2id metrics
└── plots/
    ├── php_latency_md5.png
    ├── php_latency_sha3.png
    ├── php_latency_blake2b.png
    ├── php_latency_argon2id.png
    ├── hashcat_crack_times_md5.png
    ├── hashcat_crack_times_sha3.png
    ├── hashcat_crack_times_blake2b.png
    └── hashcat_crack_times_argon2id.png
```

## Usage

### Analyze Individual Algorithm

```bash
# MD5
python python/analyze.py --hash-type md5 --plots out/plots --json out/summary_md5.json

# SHA-3
python python/analyze.py --hash-type sha3 --plots out/plots --json out/summary_sha3.json

# BLAKE2b
python python/analyze.py --hash-type blake2b --plots out/plots --json out/summary_blake2b.json

# Argon2id
python python/analyze.py --hash-type argon2id --plots out/plots --json out/summary_argon2id.json
```

### Analyze All Algorithms at Once

```bash
python python/analyze_all.py
```

This script automatically generates separate outputs for all four algorithms.

## JSON Output Format

Each `summary_<algorithm>.json` contains:

```json
{
  "summary": {
    "hash_type": "MD5",
    "total_hashes": 10000,
    "cracked": 10000,
    "cracked_pct": 100.0,
    "avg_php_ms": 0.00017535,
    "min_php_ms": 0.0001,
    "max_php_ms": 0.0468,
    "median_php_ms": 0.0002,
    "avg_crack_time_s": 0.0,
    "latest_run": { ... }
  },
  "plots": [
    "out/plots/php_latency_md5.png",
    "out/plots/hashcat_crack_times_md5.png"
  ]
}
```

### Actual Results from Experiments

| Algorithm | Total | Cracked | Success % | Avg Hashing | Min | Max | Median |
|-----------|-------|---------|-----------|-------------|-----|-----|--------|
| MD5       | 10,000 | 10,000 | 100.0% | 0.000175 ms | 0.0001 ms | 0.0468 ms | 0.0002 ms |
| SHA-3     | 10,000 | 10,000 | 100.0% | 0.000313 ms | 0.0002 ms | 0.0320 ms | 0.0003 ms |
| BLAKE2b   | 10,000 | 10,000 | 100.0% | 0.000276 ms | 0.0002 ms | 0.2149 ms | 0.0002 ms |
| Argon2id  | 1,000  | 0      | 0.0%   | 126.786 ms | 118.82 ms | 214.32 ms | 124.35 ms |

**Note**: Argon2id was not cracked because Hashcat does not support Argon2id format. The algorithm is intentionally slow (memory-hard) for password security.

## Plot Files

Each algorithm gets two plots:

1. **PHP Hashing Latency** (`php_latency_<algorithm>.png`)
   - Distribution of hashing times in milliseconds
   - Shows how fast each algorithm is to compute

2. **Hashcat Cracking Times** (`hashcat_crack_times_<algorithm>.png`)
   - Distribution of cracking times in seconds
   - Shows how resistant each algorithm is to brute-force

## Comparing Results

To compare algorithms side-by-side:

1. **Hashing Speed**: Compare `avg_php_ms` values in JSON files
2. **Cracking Resistance**: Compare `avg_crack_time_s` and `cracked_pct` values
3. **Visual Comparison**: Open plot files side-by-side

Example comparison script:
```bash
# Compare hashing speeds
for algo in md5 sha3 blake2b argon2id; do
  echo "$algo: $(jq '.summary.avg_php_ms' out/summary_${algo}.json) ms"
done
```

## Notes

- Each algorithm's data is completely separate
- No interference between different hash types
- All outputs are timestamped and can be versioned
- Plots use algorithm-specific titles and filenames

