#!/usr/bin/env python3
"""Audit public Git paths and deployment archives against the content policy."""

from __future__ import annotations

import argparse
import subprocess
import sys
import tarfile
from pathlib import Path, PurePosixPath
from typing import Iterable


PROTECTED_PATHS = {
    "config/local.neon",
    "config/test.neon",
    "config/phinx.php",
    "config/phinx.yaml",
}
PROTECTED_DIRECTORIES = (
    "log",
    "temp",
    "www/dokumenty",
    "www/images/carousel",
)
SECRET_FILE_NAMES = {
    ".env",
    ".npmrc",
    ".pypirc",
    "auth.json",
    "id_dsa",
    "id_ecdsa",
    "id_ed25519",
    "id_rsa",
    "known_hosts",
}
SECRET_SUFFIXES = (".key", ".kdbx", ".p12", ".pfx", ".pem")
PRIVATE_CONTENT_SUFFIXES = (
    ".7z",
    ".aac",
    ".avi",
    ".bmp",
    ".db",
    ".doc",
    ".docx",
    ".dump",
    ".flac",
    ".gif",
    ".heic",
    ".jpeg",
    ".jpg",
    ".ico",
    ".m4a",
    ".mid",
    ".midi",
    ".mkv",
    ".mov",
    ".mp3",
    ".mp4",
    ".mscz",
    ".musicxml",
    ".mxl",
    ".ods",
    ".odt",
    ".ogg",
    ".otf",
    ".pdf",
    ".png",
    ".ppt",
    ".pptx",
    ".rar",
    ".raw",
    ".rtf",
    ".sqlite",
    ".sqlite3",
    ".sql",
    ".sql.bz2",
    ".sql.gz",
    ".svg",
    ".tif",
    ".tiff",
    ".wav",
    ".webm",
    ".webp",
    ".wma",
    ".woff",
    ".woff2",
    ".xls",
    ".xlsx",
    ".zip",
)
APPROVED_FIRST_PARTY_MEDIA = {
    "www/favicon.ico",
    "www/bootstrap-icons-1.10.5/font/fonts/bootstrap-icons.woff",
    "www/bootstrap-icons-1.10.5/font/fonts/bootstrap-icons.woff2",
    "www/jquery-ui-1.13.2/images/ui-icons_444444_256x240.png",
    "www/jquery-ui-1.13.2/images/ui-icons_555555_256x240.png",
    "www/jquery-ui-1.13.2/images/ui-icons_777620_256x240.png",
    "www/jquery-ui-1.13.2/images/ui-icons_777777_256x240.png",
    "www/jquery-ui-1.13.2/images/ui-icons_cc0000_256x240.png",
    "www/jquery-ui-1.13.2/images/ui-icons_ffffff_256x240.png",
}
APPROVED_SOURCE_PLACEHOLDERS = {"log/.gitignore", "temp/.gitignore"}
ARTIFACT_ROOT_DIRECTORIES = {"app", "bin", "config", "db", "vendor", "www"}
ARTIFACT_ROOT_FILES = {
    "composer.json",
    "composer.lock",
    "README.md",
    "THIRD_PARTY_NOTICES.md",
}
ALLOWED_GIT_MODES = {"100644", "100755"}


class PolicyViolation(ValueError):
    pass


def normalize_path(raw_path: str) -> str:
    if not raw_path:
        raise PolicyViolation("empty path")
    if "\\" in raw_path:
        raise PolicyViolation(f"ambiguous backslash in path: {raw_path!r}")
    if any(ord(character) < 32 or ord(character) == 127 for character in raw_path):
        raise PolicyViolation(f"control character in path: {raw_path!r}")

    path = PurePosixPath(raw_path.rstrip("/"))
    if path.is_absolute() or not path.parts or any(part in {"", ".", ".."} for part in path.parts):
        raise PolicyViolation(f"unsafe path: {raw_path!r}")

    return path.as_posix()


def check_path(path: str, *, artifact: bool) -> None:
    normalized = normalize_path(path)
    lower = normalized.lower()

    protected_runtime_path = lower in PROTECTED_PATHS or any(
        lower == directory or lower.startswith(f"{directory}/")
        for directory in PROTECTED_DIRECTORIES
    )
    if protected_runtime_path and (artifact or lower not in APPROVED_SOURCE_PLACEHOLDERS):
        raise PolicyViolation(f"protected runtime path: {normalized}")

    name = PurePosixPath(lower).name
    if (
        name in SECRET_FILE_NAMES
        or name.endswith(".env")
        or ".env." in name
        or lower.endswith(SECRET_SUFFIXES)
    ):
        raise PolicyViolation(f"secret-bearing file name: {normalized}")

    if artifact:
        root = normalized.split("/", 1)[0]
        if root not in ARTIFACT_ROOT_DIRECTORIES and normalized not in ARTIFACT_ROOT_FILES:
            raise PolicyViolation(f"unexpected artifact path: {normalized}")

    dependency_path = artifact and lower.startswith("vendor/")
    if (
        not dependency_path
        and lower not in APPROVED_FIRST_PARTY_MEDIA
        and lower.endswith(PRIVATE_CONTENT_SUFFIXES)
    ):
        raise PolicyViolation(f"private or unreviewed content type: {normalized}")


def _run_git(root: Path, arguments: list[str]) -> bytes:
    result = subprocess.run(
        ["git", *arguments],
        cwd=root,
        check=False,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
    )
    if result.returncode != 0:
        message = result.stderr.decode("utf-8", "replace").strip()
        raise PolicyViolation(f"Git inspection failed: {message}")
    return result.stdout


def _parse_git_tree(output: bytes) -> Iterable[tuple[str, str]]:
    for record in output.split(b"\0"):
        if not record:
            continue
        try:
            metadata, raw_path = record.split(b"\t", 1)
            mode = metadata.split(b" ", 1)[0].decode("ascii")
            path = raw_path.decode("utf-8")
        except (UnicodeDecodeError, ValueError) as exception:
            raise PolicyViolation("invalid Git tree record") from exception
        yield mode, path


def audit_git(root: Path, *, history: bool) -> None:
    trees: list[tuple[str, bytes]] = [
        ("index", _run_git(root, ["ls-files", "--stage", "-z"])),
    ]

    if history:
        commits = _run_git(root, ["rev-list", "--all"]).decode("ascii").splitlines()
        trees.extend(
            (
                commit,
                _run_git(root, ["ls-tree", "-rz", "--full-tree", commit]),
            )
            for commit in commits
        )

    inspected: set[tuple[str, str]] = set()
    for tree_name, output in trees:
        for mode, path in _parse_git_tree(output):
            identity = (mode, path)
            if identity in inspected:
                continue
            inspected.add(identity)
            if mode not in ALLOWED_GIT_MODES:
                raise PolicyViolation(f"unsupported Git mode {mode} at {path} ({tree_name})")
            try:
                check_path(path, artifact=False)
            except PolicyViolation as exception:
                raise PolicyViolation(f"{exception} ({tree_name})") from exception


def audit_archive(archive: Path) -> None:
    seen: set[str] = set()
    total_size = 0

    try:
        with tarfile.open(archive, "r:gz") as tar:
            for member in tar:
                normalized = normalize_path(member.name)
                if normalized in seen:
                    raise PolicyViolation(f"duplicate archive path: {normalized}")
                seen.add(normalized)

                if not member.isdir() and not member.isreg():
                    raise PolicyViolation(f"link or special archive entry: {normalized}")
                if normalized in ARTIFACT_ROOT_DIRECTORIES and not member.isdir():
                    raise PolicyViolation(f"artifact root must be a directory: {normalized}")
                if normalized in ARTIFACT_ROOT_FILES and not member.isreg():
                    raise PolicyViolation(f"artifact root must be a regular file: {normalized}")
                if member.isreg():
                    total_size += member.size
                    if member.size > 512 * 1024 * 1024:
                        raise PolicyViolation(f"oversized archive entry: {normalized}")
                    if total_size > 1024 * 1024 * 1024:
                        raise PolicyViolation("uncompressed archive exceeds 1 GiB")

                check_path(normalized, artifact=True)
    except (tarfile.TarError, OSError) as exception:
        raise PolicyViolation(f"cannot inspect archive: {exception}") from exception

    required = {"composer.lock", "www/index.php"}
    missing = sorted(required - seen)
    if missing:
        raise PolicyViolation(f"required artifact paths missing: {', '.join(missing)}")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    subparsers = parser.add_subparsers(dest="command", required=True)

    source_parser = subparsers.add_parser("source", help="audit Git-tracked paths")
    source_parser.add_argument("--root", type=Path, default=Path.cwd())
    source_parser.add_argument("--history", action="store_true")

    archive_parser = subparsers.add_parser("archive", help="audit a .tar.gz deployment artifact")
    archive_parser.add_argument("archive", type=Path)

    arguments = parser.parse_args()
    try:
        if arguments.command == "source":
            audit_git(arguments.root.resolve(), history=arguments.history)
        else:
            audit_archive(arguments.archive.resolve())
    except PolicyViolation as exception:
        print(f"Public content audit failed: {exception}", file=sys.stderr)
        return 1

    print("Public content audit passed.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
