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
    expected = {
        "DataFlowHelper",
        "IPSViewHTMLPageHelper",
        "ResponsiveVisualizationHelper",
        "VisualizationAssetHelper",
        "VisualizationThemeHelper",
    }
    if not isinstance(subscriptions, dict) or set(subscriptions) != expected:
        raise SystemExit("Unexpected helper subscriptions.")
    if not isinstance(helpers, dict) or set(helpers) != set(subscriptions):
        raise SystemExit("Helper subscriptions and vendor manifest differ.")

    readme = README.read_text(encoding="utf-8")

    def verify_contract(name: str, contract: dict[str, object]) -> None:
        path_value = contract.get("path")
        if not isinstance(path_value, str):
            raise SystemExit(f"Vendored {name} path is missing.")

        helper_path = ROOT / path_value
        source = helper_path.read_text(encoding="utf-8")
        version = VERSION_PATTERN.search(source)
        if version is None or version.group(1) != contract.get("version"):
            raise SystemExit(f"Vendored {name} version mismatch.")

        actual_hash = hashlib.sha256(helper_path.read_bytes()).hexdigest()
        if actual_hash != contract.get("sha256"):
            raise SystemExit(f"Vendored {name} checksum mismatch.")

        for value in (helper_path.name, contract.get("version"), contract.get("sha256")):
            if not isinstance(value, str) or value not in readme:
                raise SystemExit("Vendored helper README is incomplete.")

        assets = contract.get("assets", [])
        if not isinstance(assets, list):
            raise SystemExit(f"Vendored {name} assets are invalid.")
        for asset in assets:
            if not isinstance(asset, dict) or not isinstance(asset.get("path"), str):
                raise SystemExit(f"Vendored {name} asset contract is invalid.")
            asset_path = ROOT / asset["path"]
            if hashlib.sha256(asset_path.read_bytes()).hexdigest() != asset.get("sha256"):
                raise SystemExit(f"Vendored {name} asset checksum mismatch: {asset_path.name}.")

        dependencies = contract.get("dependencies", [])
        if not isinstance(dependencies, list):
            raise SystemExit(f"Vendored {name} dependencies are invalid.")
        for dependency in dependencies:
            if not isinstance(dependency, dict) or not isinstance(dependency.get("name"), str):
                raise SystemExit(f"Vendored {name} dependency contract is invalid.")
            verify_contract(dependency["name"], dependency)

    for name, contract in helpers.items():
        if not isinstance(contract, dict):
            raise SystemExit(f"Vendored {name} contract is invalid.")
        target = subscriptions[name].get("target")
        if contract.get("path") != target:
            raise SystemExit(f"{name} subscription and manifest paths differ.")
        verify_contract(name, contract)

    print("Vendored helper subscriptions and integrity verified")


if __name__ == "__main__":
    main()
