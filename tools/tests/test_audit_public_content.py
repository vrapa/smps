from __future__ import annotations

import importlib.util
import hashlib
import io
import tarfile
import tempfile
import unittest
from pathlib import Path


MODULE_PATH = Path(__file__).parents[1] / "audit_public_content.py"
SPEC = importlib.util.spec_from_file_location("audit_public_content", MODULE_PATH)
assert SPEC is not None and SPEC.loader is not None
audit = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(audit)


class PublicContentPolicyTest(unittest.TestCase):
    def test_approved_ui_image_is_allowed(self) -> None:
        audit.check_path(
            "www/jquery-ui-1.13.2/images/ui-icons_444444_256x240.png",
            artifact=False,
        )

    def test_private_content_and_runtime_paths_are_rejected(self) -> None:
        rejected = (
            "choir-photo.JPG",
            "scores/song.pdf",
            "backup/database.sql.gz",
            "www/dokumenty",
            "www/dokumenty/upload.txt",
            "config/local.neon",
            ".env.local",
        )
        for path in rejected:
            with self.subTest(path=path), self.assertRaises(audit.PolicyViolation):
                audit.check_path(path, artifact=False)

    def test_dependency_media_is_allowed_only_in_artifact(self) -> None:
        audit.check_path("vendor/package/docs/logo.png", artifact=True)
        with self.assertRaises(audit.PolicyViolation):
            audit.check_path("docs/logo.png", artifact=True)

    def test_safe_archive_is_accepted(self) -> None:
        archive = self._archive_path()
        with tarfile.open(archive, "w:gz") as tar:
            self._add_release_files(
                tar,
                {
                    ".htaccess": b"guard",
                    "composer.lock": b"lock",
                    "www/index.php": b"index",
                    "vendor/package/docs/logo.png": b"logo",
                },
            )
        audit.audit_archive(archive)

    def test_archive_requires_project_root_guard(self) -> None:
        archive = self._archive_path()
        with tarfile.open(archive, "w:gz") as tar:
            self._add_release_files(
                tar,
                {"composer.lock": b"lock", "www/index.php": b"index"},
            )
        with self.assertRaisesRegex(audit.PolicyViolation, r"required.*\.htaccess"):
            audit.audit_archive(archive)

    def test_archive_rejects_manifest_hash_mismatch(self) -> None:
        archive = self._archive_path()
        with tarfile.open(archive, "w:gz") as tar:
            files = {
                ".htaccess": b"guard",
                "composer.lock": b"lock",
                "RELEASE_SHA": (b"a" * 40) + b"\n",
                "www/index.php": b"index",
            }
            for name, content in files.items():
                self._add_file(tar, name, content)
            manifest = b"".join(
                f"{'0' * 64 if name == '.htaccess' else hashlib.sha256(content).hexdigest()}  {name}\n".encode(
                    "utf-8",
                )
                for name, content in sorted(files.items())
            )
            self._add_file(tar, "RELEASE_MANIFEST.sha256", manifest)
        with self.assertRaisesRegex(audit.PolicyViolation, r"hash mismatch.*\.htaccess"):
            audit.audit_archive(archive)

    def test_archive_rejects_file_missing_from_manifest(self) -> None:
        archive = self._archive_path()
        with tarfile.open(archive, "w:gz") as tar:
            self._add_release_files(
                tar,
                {
                    ".htaccess": b"guard",
                    "composer.lock": b"lock",
                    "www/index.php": b"index",
                },
            )
            self._add_file(tar, "app/unlisted.php", b"unlisted")
        with self.assertRaisesRegex(audit.PolicyViolation, r"manifest path mismatch"):
            audit.audit_archive(archive)

    def test_archive_rejects_traversal_and_links(self) -> None:
        cases = (
            ("../config/local.neon", tarfile.REGTYPE),
            ("www/link", tarfile.SYMTYPE),
        )
        for name, entry_type in cases:
            with self.subTest(name=name):
                archive = self._archive_path()
                with tarfile.open(archive, "w:gz") as tar:
                    self._add_release_files(
                        tar,
                        {
                            ".htaccess": b"guard",
                            "composer.lock": b"lock",
                            "www/index.php": b"index",
                        },
                    )
                    info = tarfile.TarInfo(name)
                    info.type = entry_type
                    if entry_type == tarfile.SYMTYPE:
                        info.linkname = "../config/local.neon"
                    tar.addfile(info, io.BytesIO(b"") if entry_type == tarfile.REGTYPE else None)
                with self.assertRaises(audit.PolicyViolation):
                    audit.audit_archive(archive)

    def test_archive_rejects_a_file_disguised_as_a_root_directory(self) -> None:
        archive = self._archive_path()
        with tarfile.open(archive, "w:gz") as tar:
            self._add_release_files(
                tar,
                {
                    ".htaccess": b"guard",
                    "composer.lock": b"lock",
                    "www/index.php": b"index",
                },
            )
            self._add_file(tar, "www")
        with self.assertRaises(audit.PolicyViolation):
            audit.audit_archive(archive)

    def _archive_path(self) -> Path:
        with tempfile.NamedTemporaryFile(
            dir=Path(__file__).parent,
            suffix=".tar.gz",
            delete=False,
        ) as temporary_file:
            path = Path(temporary_file.name)
        self.addCleanup(path.unlink, missing_ok=True)
        return path

    @classmethod
    def _add_release_files(cls, tar: tarfile.TarFile, files: dict[str, bytes]) -> None:
        release_files = {**files, "RELEASE_SHA": (b"a" * 40) + b"\n"}
        manifest = b"".join(
            f"{hashlib.sha256(content).hexdigest()}  {name}\n".encode("utf-8")
            for name, content in sorted(release_files.items())
        )
        for name, content in release_files.items():
            cls._add_file(tar, name, content)
        cls._add_file(tar, "RELEASE_MANIFEST.sha256", manifest)

    @staticmethod
    def _add_file(tar: tarfile.TarFile, name: str, content: bytes = b"test") -> None:
        info = tarfile.TarInfo(name)
        info.size = len(content)
        tar.addfile(info, io.BytesIO(content))


if __name__ == "__main__":
    unittest.main()
