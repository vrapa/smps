from __future__ import annotations

import unittest
from pathlib import Path


REPOSITORY_ROOT = Path(__file__).resolve().parents[2]
WORKFLOW_DIRECTORY = REPOSITORY_ROOT / ".github" / "workflows"


class WorkflowPolicyTest(unittest.TestCase):
    def test_production_label_is_confined_to_deployment_workflow(self) -> None:
        users = []
        for workflow in WORKFLOW_DIRECTORY.glob("*.yml"):
            if "smps-production" in workflow.read_text(encoding="utf-8"):
                users.append(workflow.name)
        self.assertEqual(["deploy-production.yml"], sorted(users))

    def test_deployment_workflow_cannot_be_started_by_pull_request(self) -> None:
        workflow = (WORKFLOW_DIRECTORY / "deploy-production.yml").read_text(encoding="utf-8")
        self.assertNotIn("pull_request:", workflow)
        self.assertIn("workflow_run:", workflow)
        self.assertIn("workflow_dispatch:", workflow)
        self.assertIn("runs-on: smps-production", workflow)
        self.assertIn("environment:\n      name: production", workflow)
        self.assertIn("vars.AUTO_DEPLOY_ENABLED == 'true'", workflow)

    def test_ci_jobs_stay_on_github_hosted_runners(self) -> None:
        workflow = (WORKFLOW_DIRECTORY / "ci.yml").read_text(encoding="utf-8")
        self.assertNotIn("runs-on: self-hosted", workflow)
        self.assertNotIn("runs-on: smps-production", workflow)


if __name__ == "__main__":
    unittest.main()
