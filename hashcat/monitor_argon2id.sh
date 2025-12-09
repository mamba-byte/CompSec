#!/usr/bin/env bash
set -euo pipefail

ROOT="/Users/ismailcandurak/Desktop/Dersler/CompSec/HW"
CRACKED_FILE="${ROOT}/hashcat/cracked_argon2id.txt"
TARGET_COUNT=50
CHECK_INTERVAL=30  # Check every 30 seconds

echo "Monitoring Argon2id cracking progress..."
echo "Target: ${TARGET_COUNT} hashes"
echo "Current count: $(wc -l < "$CRACKED_FILE" 2>/dev/null || echo 0)"
echo "Will stop hashcat when ${TARGET_COUNT} hashes are cracked."
echo ""

while true; do
    CURRENT_COUNT=$(wc -l < "$CRACKED_FILE" 2>/dev/null || echo 0)
    
    if [ "$CURRENT_COUNT" -ge "$TARGET_COUNT" ]; then
        echo "$(date): Target reached! Found ${CURRENT_COUNT} hashes (target was ${TARGET_COUNT})"
        echo "Stopping hashcat process..."
        
        # Find and kill the hashcat process
        pkill -f "hashcat.*argon2id" || true
        
        # Wait a moment for graceful shutdown
        sleep 2
        
        # Force kill if still running
        pkill -9 -f "hashcat.*argon2id" 2>/dev/null || true
        
        echo "Hashcat stopped successfully."
        echo "Final count: $(wc -l < "$CRACKED_FILE") hashes"
        exit 0
    fi
    
    echo "$(date): Progress: ${CURRENT_COUNT}/${TARGET_COUNT} hashes (${CURRENT_COUNT} more needed)"
    sleep "$CHECK_INTERVAL"
done


