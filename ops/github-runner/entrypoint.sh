#!/usr/bin/env bash
set -euo pipefail

readonly runner_home=/runner
readonly runner_dist=/opt/actions-runner

initialize_runner_home() {
	if [[ ! -x "${runner_home}/config.sh" ]]; then
		cp --archive "${runner_dist}/." "${runner_home}/"
	fi
}

require_registration_token() {
	if [[ -z "${RUNNER_TOKEN:-}" ]]; then
		echo 'RUNNER_TOKEN is required for this one-time operation.' >&2
		exit 2
	fi
}

initialize_runner_home
cd "$runner_home"

case "${1:-run}" in
	register)
		require_registration_token
		if [[ -f .runner ]]; then
			echo 'Runner state is already registered. Remove it before registering again.' >&2
			exit 2
		fi
		./config.sh \
			--unattended \
			--url "${RUNNER_URL:?RUNNER_URL is required}" \
			--token "$RUNNER_TOKEN" \
			--name "${RUNNER_NAME:-smps-production-docker}" \
			--labels "${RUNNER_LABELS:-smps-production}" \
			--no-default-labels \
			--work _work \
			--replace
		;;
	remove)
		require_registration_token
		if [[ ! -f .runner ]]; then
			echo 'Runner state is not registered.' >&2
			exit 2
		fi
		./config.sh remove --token "$RUNNER_TOKEN"
		;;
	run)
		if [[ ! -f .runner ]]; then
			echo 'Runner is not registered. Run register-runner.ps1 first.' >&2
			exit 2
		fi
		exec ./run.sh
		;;
	*)
		echo "Unknown runner command: $1" >&2
		exit 2
		;;
esac
