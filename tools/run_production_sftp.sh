#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 ]]; then
	echo "Usage: $0 BATCH_FILE" >&2
	exit 2
fi

readonly batch_file=$1
for variable_name in \
	SFTP_HOST \
	SFTP_USERNAME \
	SFTP_PASSWORD \
	SFTP_KNOWN_HOSTS \
	SFTP_PORT \
	SFTP_REMOTE_PATH; do
	if [[ -z "${!variable_name:-}" ]]; then
		echo "Required production setting is missing: ${variable_name}" >&2
		exit 2
	fi
done

if [[ ! -f "$batch_file" ]]; then
	echo "SFTP batch file does not exist: $batch_file" >&2
	exit 2
fi
if [[ ! "$SFTP_PORT" =~ ^[0-9]+$ ]] || ((SFTP_PORT < 1 || SFTP_PORT > 65535)); then
	echo 'SFTP_PORT must be an integer from 1 to 65535.' >&2
	exit 2
fi
if [[ ! "$SFTP_HOST" =~ ^[A-Za-z0-9.-]+$ || ! "$SFTP_USERNAME" =~ ^[A-Za-z0-9._-]+$ ]]; then
	echo 'SFTP host or username contains an unsupported character.' >&2
	exit 2
fi
if [[ "$SFTP_REMOTE_PATH" != '.' ]]; then
	echo 'SFTP_REMOTE_PATH must be the verified restricted-account root (.).' >&2
	exit 2
fi

readonly ssh_directory="$(mktemp --directory)"
cleanup() {
	rm -f "${ssh_directory}/askpass.sh" "${ssh_directory}/known_hosts"
	rmdir "$ssh_directory" 2>/dev/null || true
}
trap cleanup EXIT INT TERM

printf '%s\n' "$SFTP_KNOWN_HOSTS" > "${ssh_directory}/known_hosts"
cat > "${ssh_directory}/askpass.sh" <<'EOF'
#!/usr/bin/env bash
printf '%s\n' "$SFTP_PASSWORD"
EOF
chmod 600 "${ssh_directory}/known_hosts"
chmod 700 "${ssh_directory}/askpass.sh"

export SSH_ASKPASS="${ssh_directory}/askpass.sh"
export SSH_ASKPASS_REQUIRE=force
export DISPLAY=:0

setsid --wait sftp \
	-b "$batch_file" \
	-o BatchMode=no \
	-o PreferredAuthentications=password \
	-o PubkeyAuthentication=no \
	-o NumberOfPasswordPrompts=1 \
	-o ConnectTimeout=20 \
	-o ConnectionAttempts=1 \
	-o StrictHostKeyChecking=yes \
	-o "UserKnownHostsFile=${ssh_directory}/known_hosts" \
	-P "$SFTP_PORT" \
	"${SFTP_USERNAME}@${SFTP_HOST}:${SFTP_REMOTE_PATH}"
