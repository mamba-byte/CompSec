#!/usr/bin/env python3
"""
Analyze all hash algorithms and generate separate outputs for each.
"""
import subprocess
import sys
from pathlib import Path


def main():
    hash_types = ["md5", "sha3", "blake2b", "argon2id"]
    base_dir = Path(__file__).parent.parent
    out_dir = base_dir / "out"
    plots_dir = out_dir / "plots"
    
    print("Analyzing all hash algorithms...")
    print("=" * 60)
    
    for hash_type in hash_types:
        print(f"\nProcessing {hash_type.upper()}...")
        json_file = out_dir / f"summary_{hash_type}.json"
        
        cmd = [
            sys.executable,
            str(base_dir / "python" / "analyze.py"),
            "--hash-type", hash_type,
            "--plots", str(plots_dir),
            "--json", str(json_file),
        ]
        
        try:
            result = subprocess.run(cmd, check=True, capture_output=True, text=True)
            print(f"✓ Generated {json_file}")
            print(f"✓ Plots saved to {plots_dir}")
        except subprocess.CalledProcessError as e:
            print(f"✗ Error processing {hash_type}: {e.stderr}")
            continue
    
    print("\n" + "=" * 60)
    print("Analysis complete!")
    print(f"\nOutput files:")
    print(f"  - JSON summaries: {out_dir}/summary_*.json")
    print(f"  - Plots: {plots_dir}/php_latency_*.png")
    print(f"  - Plots: {plots_dir}/hashcat_crack_times_*.png")


if __name__ == "__main__":
    main()

