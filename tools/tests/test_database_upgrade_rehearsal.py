from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / "tools" / "rehearse-database-upgrade.php"


class DatabaseUpgradeRehearsalSafetyTest(unittest.TestCase):
    def test_rehearsal_is_restricted_to_local_test_database(self) -> None:
        source = SCRIPT.read_text(encoding="utf-8")

        self.assertIn("str_ends_with($databaseName, '_test')", source)
        self.assertIn("['127.0.0.1', 'localhost']", source)
        self.assertIn("deprecated_role", source)
        self.assertIn("BIT(1)", source)
        self.assertIn("TINYINT(1)", source.upper())
        self.assertIn("upgrade-sentinel@example.test", source)
        self.assertNotIn("smps.rkcomputer.cz", source)


if __name__ == "__main__":
    unittest.main()
