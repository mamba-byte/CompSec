#!/usr/bin/env python3
import argparse
import json
import os
from pathlib import Path
from typing import Optional

import matplotlib.pyplot as plt
import pandas as pd
from sqlalchemy import create_engine


def build_engine(args) -> str:
    host = os.environ.get("DB_HOST", "127.0.0.1")
    port = os.environ.get("DB_PORT", "3306")
    name = os.environ.get("DB_NAME", "compsec_lab")
    user = os.environ.get("DB_USER", "compsec")
    pwd = os.environ.get("DB_PASS", "compsec_password")
    return f"mysql+mysqldb://{user}:{pwd}@{host}:{port}/{name}"


def fetch_data(engine_url: str, hash_type: str) -> tuple[pd.DataFrame, pd.DataFrame]:
    engine = create_engine(engine_url)
    all_hashes = pd.read_sql_table("hashes", engine)
    
    # Filter by hash type
    hash_column_map = {
        "md5": "md5_hash",
        "sha3": "sha3_hash",
        "blake2b": "blake2b_hash",
        "argon2id": "argon2id_hash",
    }
    
    if hash_type not in hash_column_map:
        raise ValueError(f"Unknown hash type: {hash_type}. Choose from: {list(hash_column_map.keys())}")
    
    hash_col = hash_column_map[hash_type]
    hashes = all_hashes[all_hashes[hash_col].notna()].copy()
    
    # Filter runs by hash mode if possible
    runs = pd.read_sql_table("hashcat_runs", engine)
    mode_map = {"md5": 0, "sha3": 17400, "blake2b": 600, "argon2id": 70000}
    if hash_type in mode_map:
        runs = runs[runs["hash_mode"] == mode_map[hash_type]]
    
    return hashes, runs


def summarize(hashes: pd.DataFrame, runs: pd.DataFrame, hash_type: str) -> dict:
    total = len(hashes)
    if total == 0:
        return {
            "hash_type": hash_type.upper(),
            "total_hashes": 0,
            "cracked": 0,
            "cracked_pct": 0.0,
            "avg_php_ms": 0.0,
            "min_php_ms": 0.0,
            "max_php_ms": 0.0,
            "median_php_ms": 0.0,
            "avg_crack_time_s": 0.0,
            "latest_run": {},
        }
    
    cracked = hashes["cracked_at"].notna().sum()
    avg_php = hashes["php_elapsed_ms"].mean()
    avg_crack = hashes["crack_time_s"].dropna().mean() if hashes["crack_time_s"].notna().any() else 0.0
    min_php = hashes["php_elapsed_ms"].min()
    max_php = hashes["php_elapsed_ms"].max()
    median_php = hashes["php_elapsed_ms"].median()

    latest_run = runs.sort_values("created_at").tail(1).to_dict(orient="records")
    return {
        "hash_type": hash_type.upper(),
        "total_hashes": int(total),
        "cracked": int(cracked),
        "cracked_pct": float(cracked / total * 100) if total else 0.0,
        "avg_php_ms": float(avg_php) if pd.notna(avg_php) else 0.0,
        "min_php_ms": float(min_php) if pd.notna(min_php) else 0.0,
        "max_php_ms": float(max_php) if pd.notna(max_php) else 0.0,
        "median_php_ms": float(median_php) if pd.notna(median_php) else 0.0,
        "avg_crack_time_s": float(avg_crack) if pd.notna(avg_crack) else 0.0,
        "latest_run": latest_run[0] if latest_run else {},
    }


def plot_distributions(hashes: pd.DataFrame, outdir: Optional[Path], hash_type: str) -> list[str]:
    if outdir is None:
        return []
    outdir.mkdir(parents=True, exist_ok=True)
    plots = []

    # PHP hashing latency histogram - ALWAYS create this plot
    fig, ax = plt.subplots(figsize=(10, 6))
    if len(hashes) > 0 and hashes["php_elapsed_ms"].notna().any():
        hashes["php_elapsed_ms"].hist(ax=ax, bins=50, edgecolor="black", alpha=0.7)
        ax.set_title(f"PHP {hash_type.upper()} Hashing Latency Distribution", fontsize=14, fontweight="bold")
        ax.set_xlabel("Hashing Time (milliseconds)", fontsize=12)
        ax.set_ylabel("Frequency", fontsize=12)
    else:
        ax.text(0.5, 0.5, f"No {hash_type.upper()} hashing data available", 
                ha='center', va='center', fontsize=14, transform=ax.transAxes)
        ax.set_title(f"PHP {hash_type.upper()} Hashing Latency Distribution", fontsize=14, fontweight="bold")
        ax.set_xlabel("Hashing Time (milliseconds)", fontsize=12)
        ax.set_ylabel("Frequency", fontsize=12)
    ax.grid(True, alpha=0.3)
    path = outdir / f"php_latency_{hash_type}.png"
    fig.savefig(path, dpi=150, bbox_inches="tight")
    plots.append(str(path))
    plt.close(fig)

    # Hashcat crack times histogram - create if we have cracked hashes
    cracked = hashes.dropna(subset=["crack_time_s"])
    if not cracked.empty and cracked["crack_time_s"].notna().any():
        fig, ax = plt.subplots(figsize=(10, 6))
        cracked["crack_time_s"].plot(kind="hist", bins=50, ax=ax, edgecolor="black", alpha=0.7)
        ax.set_title(f"Hashcat {hash_type.upper()} Cracking Time Distribution", fontsize=14, fontweight="bold")
        ax.set_xlabel("Cracking Time (seconds)", fontsize=12)
        ax.set_ylabel("Frequency", fontsize=12)
        ax.grid(True, alpha=0.3)
        path = outdir / f"hashcat_crack_times_{hash_type}.png"
        fig.savefig(path, dpi=150, bbox_inches="tight")
        plots.append(str(path))
        plt.close(fig)
    else:
        # Create empty plot to show no cracking data
        fig, ax = plt.subplots(figsize=(10, 6))
        ax.text(0.5, 0.5, f"No {hash_type.upper()} cracking data available", 
                ha='center', va='center', fontsize=14, transform=ax.transAxes)
        ax.set_title(f"Hashcat {hash_type.upper()} Cracking Time Distribution", fontsize=14, fontweight="bold")
        ax.set_xlabel("Cracking Time (seconds)", fontsize=12)
        ax.set_ylabel("Frequency", fontsize=12)
        ax.grid(True, alpha=0.3)
        path = outdir / f"hashcat_crack_times_{hash_type}.png"
        fig.savefig(path, dpi=150, bbox_inches="tight")
        plots.append(str(path))
        plt.close(fig)

    return plots


def main() -> None:
    parser = argparse.ArgumentParser(description="Analyze hashing/cracking metrics by algorithm")
    parser.add_argument("--hash-type", choices=["md5", "sha3", "blake2b", "argon2id"], required=True,
                        help="Hash algorithm to analyze")
    parser.add_argument("--plots", type=Path, help="Directory to store PNG plots")
    parser.add_argument("--json", type=Path, help="Write summary JSON to file")
    args = parser.parse_args()

    engine_url = build_engine(args)
    hashes, runs = fetch_data(engine_url, args.hash_type)
    summary = summarize(hashes, runs, args.hash_type)
    plots = plot_distributions(hashes, args.plots, args.hash_type)

    result = {"summary": summary, "plots": plots}
    print(json.dumps(result, indent=2, default=str))
    if args.json:
        args.json.write_text(json.dumps(result, indent=2, default=str))


if __name__ == "__main__":
    main()

