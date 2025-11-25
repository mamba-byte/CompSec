## Password Hashing & Cracking Lab

This project automates an end-to-end workflow for experimenting with MD5 password hashing using `rockyou.txt`, storing results in MySQL, cracking with Hashcat, and analyzing performance metrics with Python.

### Stack
- PHP 8+ CLI for hashing and database ingestion (no external dependencies)
- MySQL 8 for persisting plaintexts, hashes, and timing metrics
- Hashcat for cracking experiments (`-m 0` MD5 mode)
- Python 3.11+ (pandas, SQLAlchemy, matplotlib, seaborn) for analytics

### Repository Layout
- `sql/` – database schema and seed helpers
- `php/` – ingestion/export/update scripts plus shared helpers
- `hashcat/` – example command wrapper and status parser
- `python/` – analysis scripts and notebooks
- `docs/` – supplemental notes (e.g., experiment logs)

### Quick Start
1. `cp php/env.example php/.env` and set DB credentials/paths.
2. `mysql -u root -p < sql/schema.sql`
3. `php php/bin/load_hashes.php --file rockyou.txt`
4. `php php/bin/export_hashes.php --out hashcat/hashes.txt`
5. `bash hashcat/run_hashcat.sh`
6. `php php/bin/apply_hashcat_results.php --pot hashcat/hashcat.pot`
7. `python -m venv .venv && source .venv/bin/activate`
8. `pip install -r python/requirements.txt`
9. `python python/analyze.py --plots out/plots`

Refer to inline script help (`--help`) for advanced options such as chunking, resume offsets, and custom Hashcat configurations.

