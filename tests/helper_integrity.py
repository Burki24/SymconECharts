#!/usr/bin/env python3

from __future__ import annotations

import hashlib
import json
import re
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
CONFIG = ROOT / ".helper-sync.json"
MANIFEST = ROOT / "libs" / "helper" / "manifest.json"
README = ROOT / "libs" / "helper" / "README.md"
VERSION_PATTERN = re.compile(r"@version\s+([0-9]+\.[0-9]+\.[0-9]+)")


def main() -> None:
    config = json.loads(CONFIG.read_text(encoding="utf-8"))
    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
    if config.get("source_repository") != "Burki24/Symcon_ModuleHelper":
        raise SystemExit("Unexpected helper subscription source repository.")
    if manifest.get("source_repository") != "Burki24/Symcon_ModuleHelper":
        raise SystemExit("Unexpected helper source repository.")

    subscriptions = config.get("helpers")
    helpers = manifest.get("helpers")
    if not isinstance(subscriptions, dict) or set(subscriptions) != {"DataFlowHelper"}:
        raise SystemExit("Expected exactly the DataFlowHelper subscription.")
    if not isinstance(helpers, dict) or set(helpers) != set(subscriptions):
        raise SystemExit("Expected exactly the DataFlowHelper vendor contract.")

    contract = helpers["DataFlowHelper"]
    target = subscriptions["DataFlowHelper"].get("target")
    if contract.get("path") != target:
        raise SystemExit("DataFlowHelper subscription and manifest paths differ.")

    helper_path = ROOT / contract["path"]
    source = helper_path.read_text(encoding="utf-8")
    version = VERSION_PATTERN.search(source)
    if version is None or version.group(1) != contract.get("version"):
        raise SystemExit("Vendored DataFlowHelper version mismatch.")

    actual_hash = hashlib.sha256(helper_path.read_bytes()).hexdigest()
    if actual_hash != contract.get("sha256"):
        raise SystemExit("Vendored DataFlowHelper checksum mismatch.")

    readme = README.read_text(encoding="utf-8")
    for value in (helper_path.name, contract.get("version"), contract.get("sha256")):
        if not isinstance(value, str) or value not in readme:
            raise SystemExit("Vendored helper README is incomplete.")

    print("Vendored helper subscription and integrity verified")


if __name__ == "__main__":
    main()
