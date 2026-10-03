#!/usr/bin/env python3

from __future__ import annotations

import hashlib
import json
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
MANIFEST = ROOT / "libs" / "helper" / "manifest.json"


def main() -> None:
    manifest = json.loads(MANIFEST.read_text(encoding="utf-8"))
    if manifest.get("source_repository") != "Burki24/Symcon_ModuleHelper":
        raise SystemExit("Unexpected helper source repository.")

    helpers = manifest.get("helpers")
    if not isinstance(helpers, dict) or set(helpers) != {"DataFlowHelper"}:
        raise SystemExit("Expected exactly the DataFlowHelper vendor contract.")

    contract = helpers["DataFlowHelper"]
    helper_path = ROOT / contract["path"]
    actual_hash = hashlib.sha256(helper_path.read_bytes()).hexdigest()
    if actual_hash != contract.get("sha256"):
        raise SystemExit("Vendored DataFlowHelper checksum mismatch.")

    if contract.get("version") != "1.0.0":
        raise SystemExit("Unexpected DataFlowHelper version.")

    print("Vendored helper integrity verified")


if __name__ == "__main__":
    main()
