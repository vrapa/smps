#!/usr/bin/env python3
"""Plan and render a manifest-bound production deployment."""

from __future__ import annotations

import argparse
import hashlib
import json
import re
import sys
from dataclasses import asdict, dataclass
from pathlib import Path, PurePosixPath
from typing import Iterable


SHA256_RE = re.compile(r"^[0-9a-f]{64}$")
COMMIT_RE = re.compile(r"^[0-9a-f]{40}$")

ALLOWED_FILES = {
    ".htaccess",
    "composer.json",
    "composer.lock",
    "README.md",
    "THIRD_PARTY_NOTICES.md",
    "RELEASE_SHA",
}
ALLOWED_ROOTS = {"app", "bin", "config", "db", "vendor", "www"}
PROTECTED_EXACT = {
    "config/local.neon",
    "config/test.neon",
    "config/phinx.php",
    "config/phinx.yaml",
}
PROTECTED_PREFIXES = {
    "log/",
    "temp/",
    "www/dokumenty/",
    "www/images/carousel/",
}
METADATA_FILES = {"RELEASE_SHA"}


class DeploymentPlanError(ValueError):
    """Raised for unsafe or inconsistent deployment metadata."""


@dataclass(frozen=True)
class DeploymentPlan:
    candidate_sha: str
    remote_sha: str | None
    mode: str
    bootstrap: bool
    upload: list[str]
    delete: list[str]


def _normalize_path(raw_path: str) -> str:
    if not raw_path or "\x00" in raw_path or "\n" in raw_path or "\r" in raw_path:
        raise DeploymentPlanError("Manifest contains an empty or control-character path.")
    if "\\" in raw_path:
        raise DeploymentPlanError(f"Backslashes are not allowed in manifest path: {raw_path!r}")

    path = PurePosixPath(raw_path)
    if path.is_absolute() or path.parts != tuple(part for part in path.parts if part not in {"", ".", ".."}):
        raise DeploymentPlanError(f"Unsafe manifest path: {raw_path!r}")

    normalized = path.as_posix()
    if normalized in PROTECTED_EXACT or any(normalized.startswith(prefix) for prefix in PROTECTED_PREFIXES):
        raise DeploymentPlanError(f"Protected path is not deployable: {normalized}")

    root = path.parts[0]
    if normalized not in ALLOWED_FILES and root not in ALLOWED_ROOTS:
        raise DeploymentPlanError(f"Path is outside the deployment allowlist: {normalized}")
    return normalized


def parse_manifest(path: Path) -> dict[str, str]:
    entries: dict[str, str] = {}
    try:
        lines = path.read_text(encoding="utf-8").splitlines()
    except OSError as exc:
        raise DeploymentPlanError(f"Cannot read manifest {path}: {exc}") from exc

    if not lines:
        raise DeploymentPlanError(f"Manifest is empty: {path}")

    for line_number, line in enumerate(lines, start=1):
        if line.startswith("\\"):
            raise DeploymentPlanError(
                f"Escaped sha256sum paths are not supported ({path}:{line_number})."
            )
        match = re.fullmatch(r"([0-9a-f]{64}) [ *](.+)", line)
        if match is None:
            raise DeploymentPlanError(f"Malformed manifest line {path}:{line_number}.")
        digest, raw_path = match.groups()
        normalized = _normalize_path(raw_path)
        if normalized in entries:
            raise DeploymentPlanError(f"Duplicate manifest path: {normalized}")
        entries[normalized] = digest
    return entries


def read_release_sha(path: Path) -> str:
    try:
        value = path.read_text(encoding="ascii").strip()
    except OSError as exc:
        raise DeploymentPlanError(f"Cannot read release SHA {path}: {exc}") from exc
    if COMMIT_RE.fullmatch(value) is None:
        raise DeploymentPlanError(f"Release SHA must be a lowercase 40-character commit hash: {path}")
    return value


def _sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def verify_candidate(root: Path, manifest: dict[str, str], expected_sha: str | None) -> str:
    release_sha = read_release_sha(root / "RELEASE_SHA")
    if expected_sha is not None and release_sha != expected_sha:
        raise DeploymentPlanError(
            f"Artifact SHA {release_sha} does not match requested SHA {expected_sha}."
        )
    if "RELEASE_SHA" not in manifest:
        raise DeploymentPlanError("Candidate manifest does not contain RELEASE_SHA.")

    for relative_path, expected_digest in manifest.items():
        candidate_file = root / Path(relative_path)
        if not candidate_file.is_file():
            raise DeploymentPlanError(f"Candidate file is missing: {relative_path}")
        actual_digest = _sha256_file(candidate_file)
        if actual_digest != expected_digest:
            raise DeploymentPlanError(f"Candidate checksum mismatch: {relative_path}")
    return release_sha


def build_plan(
    candidate_root: Path,
    remote_manifest_path: Path | None,
    remote_sha_path: Path | None,
    mode: str,
    expected_sha: str | None = None,
) -> DeploymentPlan:
    candidate_manifest = parse_manifest(candidate_root / "RELEASE_MANIFEST.sha256")
    candidate_sha = verify_candidate(candidate_root, candidate_manifest, expected_sha)

    remote_manifest_exists = remote_manifest_path is not None and remote_manifest_path.is_file()
    remote_sha_exists = remote_sha_path is not None and remote_sha_path.is_file()
    bootstrap = not remote_manifest_exists
    remote_manifest: dict[str, str] = {}
    remote_sha: str | None = None

    if remote_manifest_exists:
        remote_manifest = parse_manifest(remote_manifest_path)
        if not remote_sha_exists:
            raise DeploymentPlanError("Remote manifest exists but RELEASE_SHA is missing.")
        remote_sha = read_release_sha(remote_sha_path)
        remote_sha_digest = _sha256_file(remote_sha_path)
        manifest_sha_digest = remote_manifest.get("RELEASE_SHA")
        if manifest_sha_digest is None:
            raise DeploymentPlanError("Remote manifest does not contain RELEASE_SHA.")
        if remote_sha_digest != manifest_sha_digest and remote_sha != candidate_sha:
            raise DeploymentPlanError("Remote RELEASE_SHA does not match its manifest.")
    elif remote_sha_exists:
        remote_sha = read_release_sha(remote_sha_path)
        if remote_sha != candidate_sha:
            raise DeploymentPlanError("Remote RELEASE_SHA exists without a manifest for another release.")

    candidate_files = {
        path: digest for path, digest in candidate_manifest.items() if path not in METADATA_FILES
    }
    remote_files = {
        path: digest for path, digest in remote_manifest.items() if path not in METADATA_FILES
    }

    if mode == "force-full" or bootstrap:
        upload = sorted(candidate_files)
    else:
        upload = sorted(
            path for path, digest in candidate_files.items() if remote_files.get(path) != digest
        )

    delete = [] if bootstrap else sorted(set(remote_files) - set(candidate_files), reverse=True)
    return DeploymentPlan(
        candidate_sha=candidate_sha,
        remote_sha=remote_sha,
        mode=mode,
        bootstrap=bootstrap,
        upload=upload,
        delete=delete,
    )


def _quote_sftp_path(path: str) -> str:
    if any(character in path for character in {'"', "\n", "\r", "\x00"}):
        raise DeploymentPlanError(f"Path cannot be represented safely in an SFTP batch: {path!r}")
    return f'"{path}"'


def _parent_directories(paths: Iterable[str]) -> list[str]:
    directories: set[str] = set()
    for raw_path in paths:
        parent = PurePosixPath(raw_path).parent
        while parent.as_posix() not in {".", ""}:
            directories.add(parent.as_posix())
            parent = parent.parent
    return sorted(directories, key=lambda value: (value.count("/"), value))


def write_sftp_batch(plan: DeploymentPlan, candidate_root: Path, output: Path) -> None:
    candidate_root = candidate_root.resolve()
    lines: list[str] = []
    for directory in _parent_directories(plan.upload):
        lines.append(f"-mkdir {_quote_sftp_path(directory)}")
    for relative_path in plan.upload:
        local_path = (candidate_root / Path(relative_path)).as_posix()
        lines.append(
            f"put -p {_quote_sftp_path(local_path)} {_quote_sftp_path(relative_path)}"
        )
    for relative_path in plan.delete:
        lines.append(f"-rm {_quote_sftp_path(relative_path)}")

    # RELEASE_MANIFEST is the final commit marker. A retry can safely repair a
    # transfer interrupted before either metadata file has been fully written.
    for metadata_path in ("RELEASE_SHA", "RELEASE_MANIFEST.sha256"):
        local_path = (candidate_root / metadata_path).as_posix()
        lines.append(f"put -p {_quote_sftp_path(local_path)} {_quote_sftp_path(metadata_path)}")
    lines.append("quit")
    output.write_text("\n".join(lines) + "\n", encoding="utf-8", newline="\n")


def _load_plan(path: Path) -> DeploymentPlan:
    try:
        raw = json.loads(path.read_text(encoding="utf-8"))
        return DeploymentPlan(
            candidate_sha=raw["candidate_sha"],
            remote_sha=raw.get("remote_sha"),
            mode=raw["mode"],
            bootstrap=bool(raw["bootstrap"]),
            upload=list(raw["upload"]),
            delete=list(raw["delete"]),
        )
    except (OSError, KeyError, TypeError, json.JSONDecodeError) as exc:
        raise DeploymentPlanError(f"Cannot load deployment plan {path}: {exc}") from exc


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    subparsers = parser.add_subparsers(dest="command", required=True)

    plan_parser = subparsers.add_parser("plan", help="Validate manifests and create a delta plan")
    plan_parser.add_argument("--candidate-root", required=True, type=Path)
    plan_parser.add_argument("--remote-manifest", type=Path)
    plan_parser.add_argument("--remote-sha", type=Path)
    plan_parser.add_argument("--mode", choices=("normal", "force-full"), default="normal")
    plan_parser.add_argument("--expected-sha")
    plan_parser.add_argument("--output", required=True, type=Path)

    batch_parser = subparsers.add_parser("batch", help="Render an OpenSSH SFTP batch")
    batch_parser.add_argument("--plan", required=True, type=Path)
    batch_parser.add_argument("--candidate-root", required=True, type=Path)
    batch_parser.add_argument("--output", required=True, type=Path)

    args = parser.parse_args(argv)
    try:
        if args.command == "plan":
            plan = build_plan(
                args.candidate_root,
                args.remote_manifest,
                args.remote_sha,
                args.mode,
                args.expected_sha,
            )
            args.output.write_text(
                json.dumps(asdict(plan), indent=2, sort_keys=True) + "\n",
                encoding="utf-8",
                newline="\n",
            )
            print(
                f"Deployment plan: mode={plan.mode} bootstrap={str(plan.bootstrap).lower()} "
                f"upload={len(plan.upload)} delete={len(plan.delete)}"
            )
        else:
            plan = _load_plan(args.plan)
            write_sftp_batch(plan, args.candidate_root, args.output)
    except DeploymentPlanError as exc:
        print(f"Deployment plan rejected: {exc}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
