#!/bin/bash
# Argon2id cracking attempt
# Note: Hashcat may not directly support Argon2id
# This is a memory-hard algorithm designed to be slow

echo "Attempting to crack Argon2id hashes..."
echo "Note: Argon2id is memory-hard and may take a very long time or may not be supported"
echo "Starting at $(date)"

# Try mode 9700 (closest to password hashing)
/opt/homebrew/bin/hashcat \
  --session compsec-argon2id \
  -m 9700 \
  -d 1 \
  --status \
  --status-json \
  --status-timer 10 \
  --potfile-path hashcat/hashcat_argon2id.pot \
  --outfile hashcat/cracked_argon2id.txt \
  --outfile-format 2 \
  --logfile-disable \
  hashcat/hashes_argon2id.txt rockyou.txt 2>&1 | tee hashcat/argon2id_crack.log

echo "Finished at $(date)"
