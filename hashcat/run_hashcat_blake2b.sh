#!/usr/bin/env bash
set -euo pipefail

ROOT="/Users/ismailcandurak/Desktop/Dersler/CompSec/HW"
HASH_FILE="${HASH_FILE:-$ROOT/hashcat/hashes_blake2b.txt}"
WORDLIST="${WORDLIST:-$ROOT/rockyou.txt}"
POTFILE="${POTFILE:-$ROOT/hashcat/hashcat_blake2b.pot}"
STATUS_JSON="${STATUS_JSON:-$ROOT/hashcat/status_blake2b.json}"
HASHCAT_BIN="${HASHCAT_BIN:-/opt/homebrew/bin/hashcat}"
SESSION="${SESSION:-compsec-blake2b}"
HASH_MODE="${HASH_MODE:-600}"

mkdir -p "$(dirname "$HASH_FILE")"

"$HASHCAT_BIN" \
  --session "$SESSION" \
  -m "$HASH_MODE" \
  --status --status-json \
  --status-timer 10 \
  --potfile-path "$POTFILE" \
  --outfile "$ROOT/hashcat/cracked_blake2b.txt" \
  --outfile-format 2 \
  --logfile-disable \
  "$HASH_FILE" "$WORDLIST" | tee "$ROOT/hashcat/hashcat_blake2b.log"

if command -v jq >/dev/null 2>&1; then
  "$HASHCAT_BIN" --session "$SESSION" --restore --status-json 2>/dev/null | jq '.' > "$STATUS_JSON" || true
fi

echo "Hashcat BLAKE2b run complete. Potfile: $POTFILE"

