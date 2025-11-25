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


def fetch_data(engine_url: str) -> tuple[pd.DataFrame, pd.DataFrame]:
    engine = create_engine(engine_url)
    hashes = pd.read_sql_table("hashes", engine)
    runs = pd.read_sql_table("hashcat_runs", engine)
    return hashes, runs


def summarize(hashes: pd.DataFrame, runs: pd.DataFrame) -> dict:
    total = len(hashes)
    cracked = hashes["cracked_at"].notna().sum()
    avg_php = hashes["php_elapsed_ms"].mean()
    avg_crack = hashes["crack_time_s"].dropna().mean()

    latest_run = runs.sort_values("created_at").tail(1).to_dict(orient="records")
    return {
        "total_hashes": int(total),
        "cracked": int(cracked),
        "cracked_pct": float(cracked / total * 100) if total else 0.0,
        "avg_php_ms": float(avg_php or 0),
        "avg_crack_time_s": float(avg_crack or 0),
        "latest_run": latest_run[0] if latest_run else {},
    }


def plot_distributions(hashes: pd.DataFrame, outdir: Optional[Path]) -> list[str]:
    if outdir is None:
        return []
    outdir.mkdir(parents=True, exist_ok=True)
    plots = []

    fig, ax = plt.subplots()
    hashes["php_elapsed_ms"].hist(ax=ax, bins=50)
    ax.set_title("PHP MD5 latency (ms)")
    ax.set_xlabel("Milliseconds")
    ax.set_ylabel("Count")
    path = outdir / "php_latency_hist.png"
    fig.savefig(path, dpi=150, bbox_inches="tight")
    plots.append(str(path))
    plt.close(fig)

    cracked = hashes.dropna(subset=["crack_time_s"])
    if not cracked.empty:
        fig, ax = plt.subplots()
        cracked["crack_time_s"].plot(kind="hist", bins=50, ax=ax)
        ax.set_title("Hashcat crack times (s)")
        ax.set_xlabel("Seconds")
        ax.set_ylabel("Count")
        path = outdir / "hashcat_crack_times.png"
        fig.savefig(path, dpi=150, bbox_inches="tight")
        plots.append(str(path))
        plt.close(fig)

    return plots


def main() -> None:
    parser = argparse.ArgumentParser(description="Analyze MD5 hashing/cracking metrics")
    parser.add_argument("--plots", type=Path, help="Directory to store PNG plots")
    parser.add_argument("--json", type=Path, help="Write summary JSON to file")
    args = parser.parse_args()

    engine_url = build_engine(args)
    hashes, runs = fetch_data(engine_url)
    summary = summarize(hashes, runs)
    plots = plot_distributions(hashes, args.plots)

    print(json.dumps({"summary": summary, "plots": plots}, indent=2, default=str))
    if args.json:
        args.json.write_text(json.dumps({"summary": summary, "plots": plots}, indent=2, default=str))


if __name__ == "__main__":
    main()

