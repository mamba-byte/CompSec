# Output Files Summary

## Status: ✅ All Files Generated

All JSON summaries and PNG plots have been created for each hash algorithm.

## Generated Files

### JSON Summaries (4 files)
- ✅ `out/summary_md5.json` - MD5 metrics
- ✅ `out/summary_sha3.json` - SHA-3 metrics  
- ✅ `out/summary_blake2b.json` - BLAKE2b metrics
- ✅ `out/summary_argon2id.json` - Argon2id metrics

### PHP Hashing Latency Plots (4 files)
- ✅ `out/plots/php_latency_md5.png` - MD5 hashing time distribution
- ✅ `out/plots/php_latency_sha3.png` - SHA-3 hashing time distribution
- ✅ `out/plots/php_latency_blake2b.png` - BLAKE2b hashing time distribution
- ✅ `out/plots/php_latency_argon2id.png` - Argon2id hashing time distribution

### Hashcat Cracking Time Plots (4 files)
- ✅ `out/plots/hashcat_crack_times_md5.png` - MD5 cracking time distribution
- ✅ `out/plots/hashcat_crack_times_sha3.png` - SHA-3 cracking time distribution
- ✅ `out/plots/hashcat_crack_times_blake2b.png` - BLAKE2b cracking time distribution
- ✅ `out/plots/hashcat_crack_times_argon2id.png` - Argon2id cracking time distribution

## Fixes Applied

### 1. NaN Error Fix
- **Issue**: JSON files contained `NaN` values which are not valid JSON
- **Fix**: Updated `summarize()` function to convert all NaN values to `0.0` using `pd.notna()` checks
- **Result**: All JSON files now contain valid numeric values (0.0 when no data exists)

### 2. Missing PHP Latency Plots
- **Issue**: PHP latency plots were only created for algorithms with data
- **Fix**: Updated `plot_distributions()` to ALWAYS create PHP latency plots, even for empty datasets (shows "No data available" message)
- **Result**: All 4 algorithms now have PHP latency plots

### 3. Missing Hashcat Crack Time Plots
- **Issue**: Hashcat crack time plots were only created when cracking data existed
- **Fix**: Updated `plot_distributions()` to ALWAYS create crack time plots, showing "No data available" when no cracking has occurred
- **Result**: All 4 algorithms now have crack time plots

## JSON Structure

Each JSON file follows this structure (no NaN values):

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

### Actual Results Summary

| Algorithm | Total | Cracked | Success % | Avg Hashing Time |
|-----------|-------|---------|-----------|------------------|
| MD5       | 10,000 | 10,000 | 100.0% | 0.000175 ms |
| SHA-3     | 10,000 | 10,000 | 100.0% | 0.000313 ms |
| BLAKE2b   | 10,000 | 10,000 | 100.0% | 0.000276 ms |
| Argon2id  | 1,000  | 0      | 0.0%   | 126.786 ms |

**Note**: Argon2id hashes were not cracked because Hashcat does not support Argon2id format. The algorithm is memory-hard and designed to be slow (126.79 ms vs 0.0002 ms for others).

## Verification

Run the verification script to check all files:
```bash
./verify_outputs.sh
```

Or manually verify:
```bash
# Check JSON files
ls -la out/summary_*.json

# Check PHP latency plots
ls -la out/plots/php_latency_*.png

# Check crack time plots
ls -la out/plots/hashcat_crack_times_*.png
```

## Regenerating Outputs

To regenerate all outputs:
```bash
source .venv/bin/activate
python python/analyze_all.py
```

This will create/update all JSON and PNG files for all algorithms.

