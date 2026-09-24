from __future__ import annotations

import hashlib
import importlib.util
import json
import sys
import tempfile
import unittest
from pathlib import Path


MODULE_PATH = Path(__file__).resolve().parents[1] / "production_deploy.py"
SPEC = importlib.util.spec_from_file_location("production_deploy", MODULE_PATH)
assert SPEC is not None and SPEC.loader is not None
production_deploy = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = production_deploy
SPEC.loader.exec_module(production_deploy)


class ProductionDeployTest(unittest.TestCase):
    def setUp(self) -> None:
        workspace_tmp = Path(__file__).resolve().parents[2] / ".tmp"
        workspace_tmp.mkdir(exist_ok=True)
        self.temp_directory = tempfile.TemporaryDirectory(dir=workspace_tmp)
        self.root = Path(self.temp_directory.name)
        self.candidate = self.root / "candidate"
        self.remote = self.root / "remote"
        self.candidate.mkdir()
        self.remote.mkdir()

    def tearDown(self) -> None:
        self.temp_directory.cleanup()

    @staticmethod
    def _digest(content: bytes) -> str:
        return hashlib.sha256(content).hexdigest()

    def _write_release(self, root: Path, sha: str, files: dict[str, bytes]) -> None:
        entries = dict(files)
        entries["RELEASE_SHA"] = (sha + "\n").encode("ascii")
        manifest_lines = []
        for relative_path, content in sorted(entries.items()):
            target = root / relative_path
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(content)
            manifest_lines.append(f"{self._digest(content)}  {relative_path}")
        (root / "RELEASE_MANIFEST.sha256").write_text(
            "\n".join(manifest_lines) + "\n", encoding="utf-8"
        )

    def _plan(self, mode: str = "normal"):
        remote_manifest = self.remote / "RELEASE_MANIFEST.sha256"
        remote_sha = self.remote / "RELEASE_SHA"
        return production_deploy.build_plan(
            self.candidate,
            remote_manifest if remote_manifest.exists() else None,
            remote_sha if remote_sha.exists() else None,
            mode,
        )

    def test_bootstrap_uploads_every_candidate_file_without_deletion(self) -> None:
        self._write_release(
            self.candidate,
            "a" * 40,
            {"app/new.php": b"new", "www/style.css": b"style"},
        )

        plan = self._plan()

        self.assertTrue(plan.bootstrap)
        self.assertEqual(["app/new.php", "www/style.css"], plan.upload)
        self.assertEqual([], plan.delete)

    def test_delta_contains_new_changed_and_removed_but_not_unchanged(self) -> None:
        self._write_release(
            self.remote,
            "a" * 40,
            {
                "app/changed.php": b"old",
                "app/removed.php": b"removed",
                "www/same.css": b"same",
            },
        )
        self._write_release(
            self.candidate,
            "b" * 40,
            {
                "app/changed.php": b"new",
                "app/new.php": b"new",
                "www/same.css": b"same",
            },
        )

        plan = self._plan()

        self.assertFalse(plan.bootstrap)
        self.assertEqual(["app/changed.php", "app/new.php"], plan.upload)
        self.assertEqual(["app/removed.php"], plan.delete)

    def test_force_full_uploads_all_candidate_files(self) -> None:
        self._write_release(self.remote, "a" * 40, {"app/same.php": b"same"})
        self._write_release(
            self.candidate,
            "b" * 40,
            {"app/same.php": b"same", "www/new.css": b"new"},
        )

        plan = self._plan("force-full")

        self.assertEqual(["app/same.php", "www/new.css"], plan.upload)

    def test_protected_candidate_path_is_rejected(self) -> None:
        self._write_release(
            self.candidate,
            "a" * 40,
            {"config/local.neon": b"secret"},
        )

        with self.assertRaisesRegex(production_deploy.DeploymentPlanError, "Protected path"):
            self._plan()

    def test_malformed_remote_manifest_is_rejected(self) -> None:
        self._write_release(self.candidate, "a" * 40, {"app/new.php": b"new"})
        (self.remote / "RELEASE_SHA").write_text("b" * 40 + "\n", encoding="ascii")
        (self.remote / "RELEASE_MANIFEST.sha256").write_text("not a manifest\n", encoding="utf-8")

        with self.assertRaisesRegex(production_deploy.DeploymentPlanError, "Malformed"):
            self._plan()

    def test_partial_metadata_for_same_candidate_can_be_retried_as_bootstrap(self) -> None:
        self._write_release(self.candidate, "a" * 40, {"app/new.php": b"new"})
        (self.remote / "RELEASE_SHA").write_text("a" * 40 + "\n", encoding="ascii")

        plan = self._plan()

        self.assertTrue(plan.bootstrap)
        self.assertEqual(["app/new.php"], plan.upload)

    def test_batch_writes_metadata_last_and_only_exact_deletions(self) -> None:
        self._write_release(
            self.candidate,
            "b" * 40,
            {"app/new.php": b"new", "www/assets/style.css": b"style"},
        )
        plan = production_deploy.DeploymentPlan(
            candidate_sha="b" * 40,
            remote_sha="a" * 40,
            mode="normal",
            bootstrap=False,
            upload=["app/new.php", "www/assets/style.css"],
            delete=["app/removed.php"],
        )
        output = self.root / "deploy.sftp"

        production_deploy.write_sftp_batch(plan, self.candidate, output)
        lines = output.read_text(encoding="utf-8").splitlines()

        self.assertIn('-rm "app/removed.php"', lines)
        self.assertEqual(
            [
                f'put -p "{(self.candidate / "RELEASE_SHA").as_posix()}" "RELEASE_SHA"',
                f'put -p "{(self.candidate / "RELEASE_MANIFEST.sha256").as_posix()}" "RELEASE_MANIFEST.sha256"',
                "quit",
            ],
            lines[-3:],
        )

    def test_cli_writes_deterministic_json(self) -> None:
        self._write_release(self.candidate, "a" * 40, {"app/new.php": b"new"})
        output = self.root / "plan.json"

        result = production_deploy.main(
            [
                "plan",
                "--candidate-root",
                str(self.candidate),
                "--expected-sha",
                "a" * 40,
                "--output",
                str(output),
            ]
        )

        self.assertEqual(0, result)
        self.assertEqual("a" * 40, json.loads(output.read_text())["candidate_sha"])


if __name__ == "__main__":
    unittest.main()
