#Requires -Version 7.2

[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$runnerToken = Read-Host 'Paste the temporary GitHub runner removal token' -MaskInput
if ([string]::IsNullOrWhiteSpace($runnerToken)) {
	throw 'The removal token must not be empty.'
}

docker compose --file "$PSScriptRoot/compose.yaml" stop smps-production-runner
$previousToken = [Environment]::GetEnvironmentVariable('RUNNER_TOKEN', 'Process')
try {
	[Environment]::SetEnvironmentVariable('RUNNER_TOKEN', $runnerToken, 'Process')
	docker compose --file "$PSScriptRoot/compose.yaml" run --rm --no-deps --env RUNNER_TOKEN smps-production-runner remove
} finally {
	[Environment]::SetEnvironmentVariable('RUNNER_TOKEN', $previousToken, 'Process')
	$runnerToken = $null
}
