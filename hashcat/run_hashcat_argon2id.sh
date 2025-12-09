#!/usr/bin/env bash
set -euo pipefail

ROOT="/Users/ismailcandurak/Desktop/Dersler/CompSec/HW"
HASH_FILE="${HASH_FILE:-$ROOT/hashcat/hashes_argon2id.txt}"
WORDLIST="${WORDLIST:-$ROOT/rockyou.txt}"
POTFILE="${POTFILE:-$ROOT/hashcat/hashcat_argon2id.pot}"
STATUS_JSON="${STATUS_JSON:-$ROOT/hashcat/status_argon2id.json}"
HASHCAT_BIN="${HASHCAT_BIN:-/opt/homebrew/bin/hashcat}"
SESSION="${SESSION:-compsec-argon2id}"
# Note: Argon2id may not be directly supported in Hashcat
# Check hashcat --help for available Argon2 modes
HASH_MODE="${HASH_MODE:-9900}"

mkdir -p "$(dirname "$HASH_FILE")"

echo "Warning: Argon2id may not be directly supported by Hashcat."
echo "Check available modes with: hashcat --help | grep -i argon"
echo "Proceeding with mode $HASH_MODE..."

"$HASHCAT_BIN" \
  --session "$SESSION" \
  -m "$HASH_MODE" \
  --status --status-json \
  --status-timer 10 \
  --potfile-path "$POTFILE" \
  --outfile "$ROOT/hashcat/cracked_argon2id.txt" \
  --outfile-format 2 \
  --logfile-disable \
  "$HASH_FILE" "$WORDLIST" 2>&1 | tee "$ROOT/hashcat/hashcat_argon2id.log" || {
    echo "Hashcat failed. Argon2id may require a different mode or may not be supported."
    exit 1
  }

if command -v jq >/dev/null 2>&1; then
  "$HASHCAT_BIN" --session "$SESSION" --restore --status-json 2>/dev/null | jq '.' > "$STATUS_JSON" || true
fi

echo "Hashcat Argon2id run complete. Potfile: $POTFILE"

