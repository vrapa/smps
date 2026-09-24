from __future__ import annotations

import os
import shutil
import subprocess
import sys
import unittest
from pathlib import Path


REPOSITORY_ROOT = Path(__file__).resolve().parents[2]
HELPER = REPOSITORY_ROOT / "tools" / "run_production_sftp.sh"
HELPER_ARGUMENT = "tools/run_production_sftp.sh"


def find_bash() -> str:
    if sys.platform == "win32":
        git_bash = Path(os.environ.get("ProgramFiles", r"C:\Program Files")) / "Git" / "bin" / "bash.exe"
        if git_bash.is_file():
            return str(git_bash)

    bash = shutil.which("bash")
    if bash is None:
        raise RuntimeError("bash is required to test the SFTP helper")

    return bash


BASH = find_bash()


class SftpHelperTest(unittest.TestCase):
    def test_requires_batch_file_argument(self) -> None:
        result = subprocess.run(
            [BASH, HELPER_ARGUMENT],
            cwd=REPOSITORY_ROOT,
            capture_output=True,
            text=True,
            check=False,
        )
        self.assertEqual(2, result.returncode)
        self.assertIn("Usage:", result.stderr)

    def test_rejects_any_remote_path_outside_restricted_root(self) -> None:
        environment = os.environ.copy()
        environment.update(
            {
                "SFTP_HOST": "sftp.example.test",
                "SFTP_USERNAME": "deploy",
                "SFTP_PASSWORD": "not-printed-secret",
                "SFTP_KNOWN_HOSTS": "synthetic host key",
                "SFTP_PORT": "222",
                "SFTP_REMOTE_PATH": "/unexpected",
            }
        )
        result = subprocess.run(
            [BASH, HELPER_ARGUMENT, HELPER_ARGUMENT],
            cwd=REPOSITORY_ROOT,
            env=environment,
            capture_output=True,
            text=True,
            check=False,
        )
        self.assertEqual(2, result.returncode)
        self.assertIn("restricted-account root", result.stderr)
        self.assertNotIn("not-printed-secret", result.stdout + result.stderr)

    def test_uses_pinned_host_identity_and_disables_public_key_fallback(self) -> None:
        source = HELPER.read_text(encoding="utf-8")
        self.assertIn("StrictHostKeyChecking=yes", source)
        self.assertIn("PubkeyAuthentication=no", source)
        self.assertIn("UserKnownHostsFile=", source)

    def test_reports_whether_askpass_was_invoked_without_printing_password(self) -> None:
        source = HELPER.read_text(encoding="utf-8")
        self.assertIn("SSH_ASKPASS_AUDIT", source)
        self.assertIn("OpenSSH invoked SSH_ASKPASS", source)
        self.assertIn("OpenSSH did not invoke SSH_ASKPASS", source)


if __name__ == "__main__":
    unittest.main()
