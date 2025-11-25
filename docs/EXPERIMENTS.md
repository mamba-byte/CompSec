## Experiment Log Template

Copy this template for each Hashcat run.

```
Run name: md5-baseline
Date: 2025-11-25
Hardware: Apple M3 Max 16c GPU

Hashcat command:
hashcat -m 0 hashes.txt rockyou.txt --potfile-path hashcat.pot --session md5-baseline

Results:
- Hashes in dataset: 500000
- Cracked: 498321 (99.7%)
- Duration: 415.3 s
- Throughput: 1.2 GH/s

Observations:
- Bottlenecked by CPU ingest speed
- Next: try ruleset and mask attacks
```

