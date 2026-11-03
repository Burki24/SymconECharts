#!/usr/bin/env python3

from __future__ import annotations

import argparse
import json
import re
from pathlib import Path
from typing import Any


VERSION_PATTERN = re.compile(r"^(\d+)\.(\d+)$")
SHA_PATTERN = re.compile(r"^[0-9a-fA-F]{40}$")
PREPARED_BETA_MARKER = "<!-- symconecharts:prepared-beta -->"
PREPARED_BETA_PATTERN = re.compile(
    r"^" + re.escape(PREPARED_BETA_MARKER)
    + r"\n(# \d{2}\.\d{2}\.\d{4} - Version )(\d+\.\d+)( \(Beta\))$",
    re.MULTILINE,
)


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Update Symcon library version, build and date metadata."
    )
    parser.add_argument("--file", required=True, type=Path)
    parser.add_argument("--sha", required=True)
    parser.add_argument("--date", required=True, type=int)
    parser.add_argument("--increment", required=True, type=int)
    parser.add_argument(
        "--prepared-beta-changelog", type=Path,
        help="Align only the explicitly marked prepared beta heading; leave history unchanged.",
    )
    parser.add_argument(
        "--base-version",
        help=(
            "Version to increment instead of the possibly overwritten version "
            "from the working-tree library.json."
        ),
    )
    return parser.parse_args()


def load_library(path: Path) -> dict[str, Any]:
    try:
        content = path.read_text(encoding="utf-8")
        data = json.loads(content)
    except FileNotFoundError as exc:
        raise SystemExit(f"Library file not found: {path}") from exc
    except json.JSONDecodeError as exc:
        raise SystemExit(f"Invalid JSON in {path}: {exc}") from exc

    if not isinstance(data, dict):
        raise SystemExit(f"{path} must contain a JSON object.")

    return data


def parse_version(version: object) -> tuple[int, int]:
    if not isinstance(version, str):
        raise SystemExit("library.json field 'version' must be a string.")

    match = VERSION_PATTERN.fullmatch(version)
    if match is None:
        raise SystemExit(
            "library.json field 'version' must use the format major.minor, "
            "for example 1.0."
        )

    return int(match.group(1)), int(match.group(2))


def calculate_version(current_version: object, increment: int) -> str:
    if increment < 1:
        raise SystemExit("--increment must be at least 1.")

    major, minor = parse_version(current_version)
    return f"{major}.{minor + increment}"


def calculate_build(commit_sha: str) -> int:
    if SHA_PATTERN.fullmatch(commit_sha) is None:
        raise SystemExit("--sha must be a complete 40-character Git commit SHA.")

    return int(commit_sha[:7], 16)


def prepare_beta_changelog(path: Path, version: str) -> str | None:
    try:
        content = path.read_text(encoding="utf-8")
    except OSError as exc:
        raise SystemExit(f"Cannot read prepared beta changelog: {path}") from exc

    if PREPARED_BETA_MARKER not in content:
        return None
    matches = list(PREPARED_BETA_PATTERN.finditer(content))
    if content.count(PREPARED_BETA_MARKER) != 1 or len(matches) != 1:
        raise SystemExit("Prepared beta changelog requires one marker directly above a dated beta heading.")
    match = matches[0]
    # Only replace the version token; user-approved date, content and history stay intact.
    return content[:match.start(2)] + version + content[match.end(2):]


def main() -> None:
    args = parse_arguments()

    if args.date < 0:
        raise SystemExit("--date must be a non-negative Unix timestamp.")

    library = load_library(args.file)

    file_version = library.get("version")
    base_version = args.base_version if args.base_version is not None else file_version
    new_version = calculate_version(base_version, args.increment)
    if parse_version(file_version) > parse_version(new_version):
        new_version = file_version
    new_build = calculate_build(args.sha)
    # Validate the optional second output before changing either file.
    changelog = (
        prepare_beta_changelog(args.prepared_beta_changelog, new_version)
        if args.prepared_beta_changelog is not None else None
    )

    library["version"] = new_version
    library["build"] = new_build
    library["date"] = args.date

    args.file.write_text(
        json.dumps(library, ensure_ascii=False, indent=4) + "\n",
        encoding="utf-8",
        newline="\n",
    )
    if changelog is not None:
        args.prepared_beta_changelog.write_text(changelog, encoding="utf-8", newline="\n")

    print(
        f"Updated {args.file}: "
        f"file version {file_version}, base version {base_version} -> {new_version}, "
        f"build {new_build}, date {args.date}"
    )


if __name__ == "__main__":
    main()
