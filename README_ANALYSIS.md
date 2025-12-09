# Quick Start - Separate Analysis Outputs

## Generate Separate Outputs for Each Algorithm

```bash
# Activate virtual environment
source .venv/bin/activate

# Analyze all algorithms (generates separate files)
python python/analyze_all.py

# Or analyze individually:
python python/analyze.py --hash-type md5 --plots out/plots --json out/summary_md5.json
python python/analyze.py --hash-type sha3 --plots out/plots --json out/summary_sha3.json
python python/analyze.py --hash-type blake2b --plots out/plots --json out/summary_blake2b.json
python python/analyze.py --hash-type argon2id --plots out/plots --json out/summary_argon2id.json
```

## Output Files Generated

- **JSON**: `out/summary_<algorithm>.json` (separate for each)
- **Plots**: `out/plots/php_latency_<algorithm>.png` (separate for each)
- **Plots**: `out/plots/hashcat_crack_times_<algorithm>.png` (separate for each)

Each algorithm has its own files - no mixing or overwriting!

## Actual Results

| Algorithm | Hashes | Cracked | Success % | Avg Hashing Time |
|-----------|--------|---------|-----------|------------------|
| MD5       | 10,000 | 10,000  | 100.0%    | 0.000175 ms      |
| SHA-3     | 10,000 | 10,000  | 100.0%    | 0.000313 ms      |
| BLAKE2b   | 10,000 | 10,000  | 100.0%    | 0.000276 ms      |
| Argon2id  | 1,000  | 0       | 0.0%      | 126.786 ms       |

See `docs/EXPERIMENTS.md` for full experiment documentation and detailed results.
