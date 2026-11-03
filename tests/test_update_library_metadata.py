#!/usr/bin/env python3

from __future__ import annotations

import json
import subprocess
import sys
import tempfile
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / ".github" / "scripts" / "update_library_metadata.py"
SOURCE_SHA = "1234567890abcdef1234567890abcdef12345678"


def run_update(
    file_path: Path, *, base_version: str, increment: int, changelog: Path | None = None
) -> dict[str, object]:
    extra = ["--prepared-beta-changelog", str(changelog)] if changelog else []
    subprocess.run(
        [
            sys.executable,
            str(SCRIPT),
            "--file",
            str(file_path),
            "--sha",
            SOURCE_SHA,
            "--date",
            "1785081600",
            "--increment",
            str(increment),
            "--base-version",
            base_version,
            *extra,
        ],
        check=True,
    )
    return json.loads(file_path.read_text(encoding="utf-8"))


def main() -> None:
    with tempfile.TemporaryDirectory() as temporary_directory:
        library_file = Path(temporary_directory) / "library.json"
        library_file.write_text(
            json.dumps(
                {
                    "id": "{00000000-0000-0000-0000-000000000000}",
                    "name": "SymconECharts",
                    "compatibility": {"version": "9.0"},
                    "version": "1.0",
                    "build": 0,
                    "date": 0,
                }
            ),
            encoding="utf-8",
        )

        updated = run_update(library_file, base_version="1.0", increment=1)
        if updated["version"] != "1.1":
            raise SystemExit("Expected base version 1.0 to advance to 1.1.")

        if updated["build"] != int(SOURCE_SHA[:7], 16):
            raise SystemExit("Build number was not derived from the first seven SHA characters.")

        if updated["date"] != 1785081600:
            raise SystemExit("Unix timestamp was not written correctly.")

        if updated.get("id") != "{00000000-0000-0000-0000-000000000000}":
            raise SystemExit("Library identity metadata was not preserved.")

        if updated.get("name") != "SymconECharts":
            raise SystemExit("Library name metadata was not preserved.")

        if updated.get("compatibility") != {"version": "9.0"}:
            raise SystemExit("Symcon compatibility metadata was not preserved.")

        updated = run_update(library_file, base_version="1.1", increment=2)
        if updated["version"] != "1.3":
            raise SystemExit("Expected two source commits to advance 1.1 to 1.3.")

        # A subsequent metadata-only run must never move an already newer
        # working-tree version backwards.
        updated = run_update(library_file, base_version="1.0", increment=1)
        if updated["version"] != "1.3":
            raise SystemExit("Expected an existing newer version to be preserved.")

        changelog = Path(temporary_directory) / "CHANGELOG.md"
        original = (
            "# Unveröffentlicht\n\n- Future change.\n\n"
            "<!-- symconecharts:prepared-beta -->\n"
            "# 29.10.2026 - Version 1.350 (Beta)\n\n"
            "## Neu\n\n- Prepared change with ünicode.\n\n"
            "# 01.10.2026 - Version 1.200 (Beta)\n\n- Historical change.\n"
        )
        changelog.write_text(original, encoding="utf-8", newline="\n")
        # A version-producing push must not leave the prepared release at the old version.
        updated = run_update(library_file, base_version="1.352", increment=1, changelog=changelog)
        expected = original.replace("Version 1.350", "Version 1.353")
        if updated["version"] != "1.353" or changelog.read_text(encoding="utf-8") != expected:
            raise SystemExit("Prepared beta must follow generated metadata without changing its date, entries or history.")

        repeated = changelog.read_bytes()
        run_update(library_file, base_version="1.352", increment=1, changelog=changelog)
        if changelog.read_bytes() != repeated:
            raise SystemExit("Repeating the same metadata plan must preserve the prepared changelog.")

        # Removing the explicit marker freezes the now historical release.
        released = expected.replace("<!-- symconecharts:prepared-beta -->\n", "")
        changelog.write_text(released, encoding="utf-8", newline="\n")
        run_update(library_file, base_version="1.353", increment=1, changelog=changelog)
        if changelog.read_text(encoding="utf-8") != released:
            raise SystemExit("Unmarked historical releases must never be rewritten.")

        # Invalid opt-in input must fail before either release file is modified.
        for invalid in (
            "<!-- symconecharts:prepared-beta -->\n# malformed release\n",
            original + "\n<!-- symconecharts:prepared-beta -->\n",
        ):
            changelog.write_text(invalid, encoding="utf-8", newline="\n")
            before_library = library_file.read_bytes()
            result = subprocess.run(
                [sys.executable, str(SCRIPT), "--file", str(library_file),
                 "--sha", SOURCE_SHA, "--date", "1785081600", "--increment", "1",
                 "--prepared-beta-changelog", str(changelog)],
                capture_output=True, text=True,
            )
            if result.returncode == 0 or library_file.read_bytes() != before_library or changelog.read_text(encoding="utf-8") != invalid:
                raise SystemExit("Invalid or ambiguous preparation markers must reject the entire metadata update.")

        # The workflow must commit both outputs together and skip its own bot commit.
        workflow = (ROOT / ".github/workflows/update-library-metadata.yml").read_text(encoding="utf-8")
        for required in (
            "--prepared-beta-changelog CHANGELOG.md",
            "git diff --quiet -- library.json CHANGELOG.md",
            "git add library.json CHANGELOG.md",
            "!startsWith(github.event.head_commit.message, 'CHORE: Update library metadata')",
        ):
            if required not in workflow:
                raise SystemExit("Metadata workflow must keep library and prepared changelog in one guarded bot commit.")

    print("Metadata updater regression tests passed")


if __name__ == "__main__":
    main()
