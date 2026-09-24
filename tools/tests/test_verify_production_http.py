from __future__ import annotations

import importlib.util
import sys
import unittest
from pathlib import Path
from unittest.mock import Mock, patch


MODULE_PATH = Path(__file__).resolve().parents[1] / "verify_production_http.py"
SPEC = importlib.util.spec_from_file_location("verify_production_http", MODULE_PATH)
assert SPEC is not None and SPEC.loader is not None
verify_production_http = importlib.util.module_from_spec(SPEC)
sys.modules[SPEC.name] = verify_production_http
SPEC.loader.exec_module(verify_production_http)


class FakeResponse:
    def __init__(self, status: int, location: str | None = None) -> None:
        self.status = status
        self.headers = {} if location is None else {"Location": location}
        self.closed = False

    def close(self) -> None:
        self.closed = True


class VerifyProductionHttpTest(unittest.TestCase):
    def test_same_origin_redirect_is_accepted(self) -> None:
        response = FakeResponse(302, "/sign/in")
        opener = Mock()
        opener.open.return_value = response

        with patch.object(verify_production_http.urllib.request, "build_opener", return_value=opener):
            verify_production_http.probe(
                "https://smps.example.test/", "/", {200, 302}, timeout=5
            )

        self.assertTrue(response.closed)

    def test_cross_origin_redirect_is_rejected(self) -> None:
        response = FakeResponse(302, "https://attacker.example/")
        opener = Mock()
        opener.open.return_value = response

        with patch.object(verify_production_http.urllib.request, "build_opener", return_value=opener):
            with self.assertRaisesRegex(RuntimeError, "leaves the production origin"):
                verify_production_http.probe(
                    "https://smps.example.test/", "/", {302}, timeout=5
                )

        self.assertTrue(response.closed)

    def test_verify_rejects_non_https_url(self) -> None:
        with self.assertRaisesRegex(ValueError, "HTTPS origin"):
            verify_production_http.verify("http://smps.example.test")

    def test_verify_checks_public_and_protected_routes(self) -> None:
        with patch.object(verify_production_http, "probe") as probe:
            verify_production_http.verify("https://smps.example.test")

        paths = [call.args[1] for call in probe.call_args_list]
        self.assertIn("/", paths)
        self.assertIn("/sign/in", paths)
        self.assertIn("/authentication/login", paths)
        self.assertIn("/config/local.neon", paths)
        self.assertIn("/RELEASE_MANIFEST.sha256", paths)


if __name__ == "__main__":
    unittest.main()
