#Requires -Version 7.2

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repositoryRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '../..'))
$scriptPath = Join-Path $repositoryRoot 'tools/deploy-production.ps1'
$examplePath = Join-Path $repositoryRoot 'config/deploy.example.psd1'

$tokens = $null
$parseErrors = $null
[System.Management.Automation.Language.Parser]::ParseFile(
    $scriptPath,
    [ref] $tokens,
    [ref] $parseErrors
) | Out-Null
if ($parseErrors.Count -ne 0) {
    throw "Deployment script has PowerShell parse errors: $($parseErrors -join '; ')"
}

$configuration = Import-PowerShellDataFile -LiteralPath $examplePath
foreach ($name in @('Host', 'UserName', 'Port', 'RemotePath', 'KnownHostsFile')) {
    if (-not $configuration.ContainsKey($name)) {
        throw "Example deployment configuration is missing '$name'."
    }
}
if ($configuration.ContainsKey('Password')) {
    throw 'Example deployment configuration must not contain a password.'
}
if ($configuration.RemotePath -ne '.') {
    throw "Example deployment configuration must use the restricted target '.'."
}

$scriptContent = Get-Content -Raw -LiteralPath $scriptPath
foreach ($requiredText in @(
    "[ValidateSet('Prepare', 'Rehearse', 'Preflight', 'WriteTest', 'MaintenanceOn', 'MaintenanceOff', 'Deploy')]",
    'Invoke-LocalDeploymentRehearsal',
    'Copy-DeploymentOverlay',
    'Assert-ManifestEqual',
    "'obsolete-release-file.txt'",
    "'config/local.neon'",
    "'www/dokumenty/upload.txt'",
    "'www/images/carousel/a.jpg'",
    'Only synthetic data inside the temporary deployment workspace was used.',
    '$Commands | & $Sftp @arguments',
    "'StrictHostKeyChecking=yes'",
    "'PreferredAuthentications=password'",
    "'PubkeyAuthentication=no'",
    'mkdir $remoteTestDirectory',
    "'rename marker.txt marker-renamed.txt'",
    "'rm marker-renamed.txt'",
    'rmdir $remoteTestDirectory',
    "'put -R app'",
    "'put -R vendor'",
    "'put -R www'",
    "'put RELEASE_MANIFEST.sha256'",
    "'put RELEASE_SHA'",
    'does not match CI revision',
    'smps-production-deployment.lock',
    '[IO.FileShare]::None',
    'Another SMPS deployment operation is already running',
    'get RELEASE_SHA $readBackShaName',
    'get RELEASE_MANIFEST.sha256 $readBackManifestName',
    'Remote release manifest does not match',
    "'www/maintenance.html'",
    'MAINTENANCE ON',
    'MAINTENANCE OFF',
    "'mkdir .maintenance'",
    'put $maintenanceMarkerName release',
    'get .maintenance/release $maintenanceReadBackName',
    "@('MaintenanceOff', 'Deploy')",
    "@('rm .maintenance/release', 'rmdir .maintenance', 'quit')",
    'Production maintenance marker is missing or belongs to a different release',
    'tools/audit_public_content.py'
)) {
    if (-not $scriptContent.Contains($requiredText)) {
        throw "Deployment script is missing required safety behavior: $requiredText"
    }
}

$maintenanceCheckPosition = $scriptContent.IndexOf("if (`$Mode -in @('MaintenanceOff', 'Deploy'))")
$uploadPosition = $scriptContent.IndexOf("The next operation overlays application files without remote deletion.")
if ($maintenanceCheckPosition -lt 0 -or $uploadPosition -lt 0 -or $maintenanceCheckPosition -ge $uploadPosition) {
    throw 'Deployment must verify the candidate-bound maintenance marker before offering the upload.'
}

& git -C $repositoryRoot check-ignore --quiet config/deploy.local.psd1
if ($LASTEXITCODE -ne 0) {
    throw 'config/deploy.local.psd1 must be ignored by Git.'
}

& git -C $repositoryRoot check-ignore --quiet .maintenance/
if ($LASTEXITCODE -ne 0) {
    throw 'The production maintenance marker must be ignored by Git.'
}

Write-Host 'Local deployment script checks passed.'
