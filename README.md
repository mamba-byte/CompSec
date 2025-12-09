## Password Hashing & Cracking Lab

This project automates an end-to-end workflow for experimenting with multiple password hashing algorithms (MD5, SHA-3-256, BLAKE2b-512, Argon2id) using `rockyou.txt`, storing results in MySQL, cracking with Hashcat, and analyzing performance metrics with Python.

### Stack
- PHP 8+ CLI for hashing and database ingestion (no external dependencies)
- MySQL 8 for persisting plaintexts, hashes, and timing metrics
- Hashcat v7.1.2+ for cracking experiments (modes: 0, 17400, 600, 70000)
- Python 3.11+ (pandas, SQLAlchemy, matplotlib, seaborn) for analytics

### Supported Algorithms
- **MD5** (Mode 0) - Legacy, cryptographically broken
- **SHA-3-256** (Mode 17400) - NIST standard, modern secure hash
- **BLAKE2b-512** (Mode 600) - Fast, secure alternative
- **Argon2id** (Mode 70000) - Memory-hard password hashing

### Repository Layout
- `sql/` – database schema and seed helpers
- `php/` – ingestion/export/update scripts plus shared helpers
- `hashcat/` – example command wrapper and status parser
- `python/` – analysis scripts and notebooks
- `docs/` – supplemental notes (e.g., experiment logs)

### Quick Start

**For MD5:**
1. `cp php/env.example php/.env` and set DB credentials/paths.
2. `mysql -u root -p < sql/schema.sql`
3. `php php/bin/load_hashes.php --file rockyou.txt --max-lines=10000`
4. `php php/bin/export_hashes.php --out hashcat/hashes.txt`
5. `bash hashcat/run_hashcat.sh`
6. `php php/bin/apply_hashcat_results.php --pot hashcat/hashcat.pot --run-name md5-test`

**For SHA-3, BLAKE2b, or Argon2id:**
- Use the corresponding `*_sha3.php`, `*_blake2b.php`, or `*_argon2id.php` scripts
- See `docs/EXPERIMENTS.md` for full workflow

**Analysis:**
7. `python -m venv .venv && source .venv/bin/activate`
8. `pip install -r python/requirements.txt`
9. `python python/analyze_all.py` (analyzes all algorithms)
   - Or individually: `python python/analyze.py --hash-type md5 --plots out/plots --json out/summary_md5.json`

### Actual Results

| Algorithm | Hashes | Cracked | Success % | Avg Hashing Time |
|-----------|--------|---------|-----------|------------------|
| MD5       | 10,000 | 10,000  | 100.0%    | 0.000175 ms      |
| SHA-3     | 10,000 | 10,000  | 100.0%    | 0.000313 ms      |
| BLAKE2b   | 10,000 | 10,000  | 100.0%    | 0.000276 ms      |
| Argon2id  | 1,000  | 50      | 5.0%      | 126.786 ms       |

See `docs/EXPERIMENTS.md` for detailed results and analysis.

