#!/usr/bin/env bash
set -euo pipefail

ROOT="/Users/ismailcandurak/Desktop/Dersler/CompSec/HW"
HASH_FILE="${HASH_FILE:-$ROOT/hashcat/hashes.txt}"
WORDLIST="${WORDLIST:-$ROOT/rockyou.txt}"
POTFILE="${POTFILE:-$ROOT/hashcat/hashcat.pot}"
STATUS_JSON="${STATUS_JSON:-$ROOT/hashcat/status.json}"
HASHCAT_BIN="${HASHCAT_BIN:-hashcat}"
SESSION="${SESSION:-compsec-md5}"

mkdir -p "$(dirname "$HASH_FILE")"

/usr/bin/time -v "$HASHCAT_BIN" \
  --session "$SESSION" \
  -m 0 \
  --status --status-json \
  --status-timer 10 \
  --potfile-path "$POTFILE" \
  --outfile "$ROOT/hashcat/cracked.txt" \
  --outfile-format 2 \
  --logfile-disable \
  "$HASH_FILE" "$WORDLIST" | tee "$ROOT/hashcat/hashcat.log"

if command -v jq >/dev/null 2>&1; then
  "$HASHCAT_BIN" --session "$SESSION" --restore --status-json 2>/dev/null | jq '.' > "$STATUS_JSON" || true
fi

echo "Hashcat run complete. Potfile: $POTFILE"

