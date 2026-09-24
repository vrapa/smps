#Requires -Version 7.2

[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$runnerToken = Read-Host 'Paste the temporary GitHub runner registration token' -MaskInput
if ([string]::IsNullOrWhiteSpace($runnerToken)) {
	throw 'The registration token must not be empty.'
}

$previousToken = [Environment]::GetEnvironmentVariable('RUNNER_TOKEN', 'Process')
try {
	[Environment]::SetEnvironmentVariable('RUNNER_TOKEN', $runnerToken, 'Process')
	docker compose --file "$PSScriptRoot/compose.yaml" build
	docker compose --file "$PSScriptRoot/compose.yaml" run --rm --no-deps --env RUNNER_TOKEN smps-production-runner register
} finally {
	[Environment]::SetEnvironmentVariable('RUNNER_TOKEN', $previousToken, 'Process')
	$runnerToken = $null
}

docker compose --file "$PSScriptRoot/compose.yaml" up --detach
