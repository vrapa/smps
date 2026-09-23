#Requires -Version 7.2

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repositoryRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../..'))
$scriptPath = Join-Path $repositoryRoot 'tools/verify-production-http.ps1'

$tokens = $null
$parseErrors = $null
[System.Management.Automation.Language.Parser]::ParseFile(
	$scriptPath,
	[ref] $tokens,
	[ref] $parseErrors
) | Out-Null
if ($parseErrors.Count -ne 0) {
	throw "HTTP verification script has PowerShell parse errors: $($parseErrors -join '; ')"
}

$scriptContent = Get-Content -Raw -LiteralPath $scriptPath
foreach ($requiredText in @(
	"[ValidateSet('Baseline', 'Acceptance')]",
	"requires an HTTPS base URL",
	'$handler.AllowAutoRedirect = $false',
	'[Net.Http.HttpCompletionOption]::ResponseHeadersRead',
	'Redirect response for',
	'leaves the production origin',
	"'/.git/HEAD'",
	"'/config/local.neon'",
	"'/config/phinx.php'",
	"'/vendor/autoload.php'",
	"'/www/dokumenty/'",
	"'/RELEASE_SHA'",
	"'/RELEASE_MANIFEST.sha256'",
	"'/sign/in'",
	"'/www/index.php'",
	'Response bodies were not read.'
)) {
	if (-not $scriptContent.Contains($requiredText)) {
		throw "HTTP verification script is missing required safety behavior: $requiredText"
	}
}

foreach ($forbiddenText in @('ReadAsString', 'ReadAsByteArray', 'ReadAsStream')) {
	if ($scriptContent.Contains($forbiddenText)) {
		throw "HTTP verification script must not read response bodies: $forbiddenText"
	}
}

$httpRejected = $false
try {
	& $scriptPath -BaseUrl 'http://localhost'
} catch {
	$httpRejected = $_.Exception.Message.Contains('requires an HTTPS base URL')
}
if (-not $httpRejected) {
	throw 'HTTP verification script must reject a non-HTTPS production URL before connecting.'
}

Write-Host 'Production HTTP verification script checks passed.'
