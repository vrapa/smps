#Requires -Version 7.2

[CmdletBinding()]
param(
    [Parameter(Mandatory)]
    [ValidateRange(1, [long]::MaxValue)]
    [long] $CiRunId,

    [ValidateSet('Prepare', 'Rehearse', 'Preflight', 'WriteTest', 'Deploy')]
    [string] $Mode = 'Prepare',

    [string] $ConfigPath,

    [switch] $KeepWorkspace
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Invoke-NativeCommand {
    param(
        [Parameter(Mandatory)]
        [string] $FilePath,

        [Parameter()]
        [string[]] $Arguments = @(),

        [switch] $Capture
    )

    if ($Capture) {
        $output = @(& $FilePath @Arguments 2>&1)
        $exitCode = $LASTEXITCODE
        if ($exitCode -ne 0) {
            $message = ($output | ForEach-Object { $_.ToString() }) -join [Environment]::NewLine
            throw "$FilePath failed with exit code $exitCode.$([Environment]::NewLine)$message"
        }

        return (($output | ForEach-Object { $_.ToString() }) -join [Environment]::NewLine).Trim()
    }

    & $FilePath @Arguments
    if ($LASTEXITCODE -ne 0) {
        throw "$FilePath failed with exit code $LASTEXITCODE."
    }
}

function Resolve-RequiredCommand {
    param([Parameter(Mandatory)][string] $Name)

    $command = Get-Command $Name -ErrorAction SilentlyContinue
    if ($null -eq $command) {
        throw "Required command '$Name' was not found."
    }

    return $command.Source
}

function Resolve-LocalPath {
    param([Parameter(Mandatory)][string] $Path)

    if ($Path -eq '~') {
        return $HOME
    }
    if ($Path.StartsWith('~/') -or $Path.StartsWith('~\')) {
        return Join-Path $HOME $Path.Substring(2)
    }

    return $Path
}

function Remove-TemporaryWorkspace {
    param(
        [Parameter(Mandatory)][string] $Workspace,
        [Parameter(Mandatory)][string] $TemporaryRoot
    )

    if (-not (Test-Path -LiteralPath $Workspace)) {
        return
    }

    $resolvedWorkspace = [IO.Path]::GetFullPath($Workspace)
    $resolvedTemporaryRoot = [IO.Path]::GetFullPath($TemporaryRoot).TrimEnd(
        [IO.Path]::DirectorySeparatorChar,
        [IO.Path]::AltDirectorySeparatorChar
    ) + [IO.Path]::DirectorySeparatorChar

    if (-not $resolvedWorkspace.StartsWith($resolvedTemporaryRoot, [StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to remove workspace outside the temporary directory: $resolvedWorkspace"
    }
    if (-not ([IO.Path]::GetFileName($resolvedWorkspace)).StartsWith('smps-deploy-', [StringComparison]::Ordinal)) {
        throw "Refusing to remove an unexpected temporary directory: $resolvedWorkspace"
    }

    Remove-Item -LiteralPath $resolvedWorkspace -Recurse -Force
}

function Write-SyntheticFile {
    param(
        [Parameter(Mandatory)][string] $Root,
        [Parameter(Mandatory)][string] $RelativePath,
        [Parameter(Mandatory)][string] $Content
    )

    $path = Join-Path $Root $RelativePath
    New-Item -ItemType Directory -Path (Split-Path -Parent $path) -Force | Out-Null
    [IO.File]::WriteAllText($path, $Content, [Text.UTF8Encoding]::new($false))
}

function Get-FileManifest {
    param([Parameter(Mandatory)][string] $Root)

    $manifest = [ordered]@{}
    foreach ($file in Get-ChildItem -LiteralPath $Root -File -Recurse | Sort-Object FullName) {
        $relativePath = [IO.Path]::GetRelativePath($Root, $file.FullName).Replace('\', '/')
        $manifest[$relativePath] = (Get-FileHash -LiteralPath $file.FullName -Algorithm SHA256).Hash
    }

    return $manifest
}

function Assert-ManifestEqual {
    param(
        [Parameter(Mandatory)][Collections.IDictionary] $Expected,
        [Parameter(Mandatory)][Collections.IDictionary] $Actual
    )

    if ($Expected.Count -ne $Actual.Count) {
        throw "Rollback manifest count mismatch: expected $($Expected.Count), got $($Actual.Count)."
    }
    foreach ($path in $Expected.Keys) {
        if (-not $Actual.Contains($path) -or $Expected[$path] -ne $Actual[$path]) {
            throw "Rollback did not restore '$path' exactly."
        }
    }
}

function Copy-DeploymentOverlay {
    param(
        [Parameter(Mandatory)][string] $ReleaseDirectory,
        [Parameter(Mandatory)][string] $TargetDirectory
    )

    foreach ($file in Get-ChildItem -LiteralPath $ReleaseDirectory -File -Recurse) {
        $relativePath = [IO.Path]::GetRelativePath($ReleaseDirectory, $file.FullName)
        $targetPath = Join-Path $TargetDirectory $relativePath
        New-Item -ItemType Directory -Path (Split-Path -Parent $targetPath) -Force | Out-Null
        Copy-Item -LiteralPath $file.FullName -Destination $targetPath -Force
    }
}

function Invoke-LocalDeploymentRehearsal {
    param(
        [Parameter(Mandatory)][string] $Workspace,
        [Parameter(Mandatory)][string] $ReleaseDirectory
    )

    $rehearsalRoot = Join-Path $Workspace 'rehearsal'
    $liveDirectory = Join-Path $rehearsalRoot 'live'
    $snapshotDirectory = Join-Path $rehearsalRoot 'snapshot'
    New-Item -ItemType Directory -Path $liveDirectory -Force | Out-Null

    $syntheticFiles = [ordered]@{
        '.htaccess'                  = 'synthetic previous access rules'
        'app/legacy-release.php'     = '<?php // synthetic previous release'
        'obsolete-release-file.txt'  = 'synthetic file absent from the candidate'
        'config/local.neon'          = 'synthetic: local configuration'
        'config/phinx.php'           = '<?php return ["synthetic" => true];'
        'www/dokumenty/upload.txt'   = 'synthetic private upload'
        'www/images/carousel/a.jpg'  = 'synthetic private carousel image'
        'log/application.log'        = 'synthetic application log'
        'temp/cache/marker.txt'      = 'synthetic runtime cache'
    }
    foreach ($entry in $syntheticFiles.GetEnumerator()) {
        Write-SyntheticFile -Root $liveDirectory -RelativePath $entry.Key -Content $entry.Value
    }

    $beforeManifest = Get-FileManifest -Root $liveDirectory
    $protectedPaths = @(
        'config/local.neon',
        'config/phinx.php',
        'www/dokumenty/upload.txt',
        'www/images/carousel/a.jpg',
        'log/application.log',
        'temp/cache/marker.txt'
    )
    $protectedHashes = @{}
    foreach ($path in $protectedPaths) {
        $protectedHashes[$path] = (Get-FileHash -LiteralPath (Join-Path $liveDirectory $path) -Algorithm SHA256).Hash
    }

    Copy-Item -LiteralPath $liveDirectory -Destination $snapshotDirectory -Recurse
    Copy-DeploymentOverlay -ReleaseDirectory $ReleaseDirectory -TargetDirectory $liveDirectory

    foreach ($path in $protectedPaths) {
        $actualHash = (Get-FileHash -LiteralPath (Join-Path $liveDirectory $path) -Algorithm SHA256).Hash
        if ($actualHash -ne $protectedHashes[$path]) {
            throw "Deployment overlay changed protected synthetic path '$path'."
        }
    }
    if (-not (Test-Path -LiteralPath (Join-Path $liveDirectory 'obsolete-release-file.txt') -PathType Leaf)) {
        throw 'The rehearsal did not reproduce no-delete stale-file behavior.'
    }
    foreach ($candidatePath in @('.htaccess', 'composer.lock', 'www/index.php')) {
        $releaseHash = (Get-FileHash -LiteralPath (Join-Path $ReleaseDirectory $candidatePath) -Algorithm SHA256).Hash
        $liveHash = (Get-FileHash -LiteralPath (Join-Path $liveDirectory $candidatePath) -Algorithm SHA256).Hash
        if ($releaseHash -ne $liveHash) {
            throw "Deployment overlay did not install candidate path '$candidatePath'."
        }
    }

    $resolvedRoot = [IO.Path]::GetFullPath($rehearsalRoot).TrimEnd(
        [IO.Path]::DirectorySeparatorChar,
        [IO.Path]::AltDirectorySeparatorChar
    ) + [IO.Path]::DirectorySeparatorChar
    $resolvedLive = [IO.Path]::GetFullPath($liveDirectory)
    if (
        -not $resolvedLive.StartsWith($resolvedRoot, [StringComparison]::OrdinalIgnoreCase) -or
        [IO.Path]::GetFileName($resolvedLive) -ne 'live'
    ) {
        throw "Refusing to replace unexpected rehearsal path '$resolvedLive'."
    }

    Remove-Item -LiteralPath $liveDirectory -Recurse -Force
    Copy-Item -LiteralPath $snapshotDirectory -Destination $liveDirectory -Recurse
    $restoredManifest = Get-FileManifest -Root $liveDirectory
    Assert-ManifestEqual -Expected $beforeManifest -Actual $restoredManifest

    Write-Host 'Local rehearsal verified candidate overlay, protected-path preservation, no-delete stale-file behavior, and exact snapshot rollback.'
    Write-Host 'Only synthetic data inside the temporary deployment workspace was used.'
}

function Read-DeploymentConfiguration {
    param(
        [Parameter(Mandatory)][string] $Path,
        [Parameter(Mandatory)][string] $SshKeygen
    )

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "Local deployment configuration was not found. Copy config/deploy.example.psd1 to config/deploy.local.psd1 and fill only non-password connection values."
    }

    $configuration = Import-PowerShellDataFile -LiteralPath $Path
    if ($configuration.ContainsKey('Password')) {
        throw 'The local deployment configuration must not contain a password.'
    }

    foreach ($name in @('Host', 'UserName', 'Port', 'RemotePath', 'KnownHostsFile')) {
        if (-not $configuration.ContainsKey($name) -or [string]::IsNullOrWhiteSpace([string] $configuration[$name])) {
            throw "Local deployment configuration is missing '$name'."
        }
    }

    $hostName = [string] $configuration.Host
    $userName = [string] $configuration.UserName
    $port = [int] $configuration.Port
    $remotePath = [string] $configuration.RemotePath
    $knownHostsFile = [IO.Path]::GetFullPath((Resolve-LocalPath ([string] $configuration.KnownHostsFile)) )

    if ($hostName -notmatch '^[A-Za-z0-9.-]+$') {
        throw 'The SFTP host contains unsupported characters.'
    }
    if ($userName -notmatch '^[A-Za-z0-9._@-]+$') {
        throw 'The SFTP username contains unsupported characters.'
    }
    if ($port -lt 1 -or $port -gt 65535) {
        throw 'The SFTP port must be between 1 and 65535.'
    }
    if ($remotePath -ne '.') {
        throw "The restricted deployment account must use its verified account-relative target '.'."
    }
    if (-not (Test-Path -LiteralPath $knownHostsFile -PathType Leaf)) {
        throw "The pinned known_hosts file does not exist: $knownHostsFile"
    }

    $hostLookup = Invoke-NativeCommand -FilePath $SshKeygen -Arguments @(
        '-F', "[$hostName]:$port", '-f', $knownHostsFile
    ) -Capture
    if ([string]::IsNullOrWhiteSpace($hostLookup)) {
        throw 'The pinned known_hosts file has no entry for the configured host and port.'
    }

    return @{
        Host           = $hostName
        UserName       = $userName
        Port           = $port
        RemotePath     = $remotePath
        KnownHostsFile = $knownHostsFile
    }
}

function Invoke-SftpCommands {
    param(
        [Parameter(Mandatory)][string] $Sftp,
        [Parameter(Mandatory)][hashtable] $Configuration,
        [Parameter(Mandatory)][string[]] $Commands,
        [Parameter(Mandatory)][string] $LocalDirectory
    )

    $arguments = @(
        '-o', 'BatchMode=no',
        '-o', 'PreferredAuthentications=password',
        '-o', 'PubkeyAuthentication=no',
        '-o', 'NumberOfPasswordPrompts=1',
        '-o', 'ConnectTimeout=20',
        '-o', 'ConnectionAttempts=1',
        '-o', 'StrictHostKeyChecking=yes',
        '-o', "UserKnownHostsFile=$($Configuration.KnownHostsFile)",
        '-P', [string] $Configuration.Port,
        "$($Configuration.UserName)@$($Configuration.Host):$($Configuration.RemotePath)"
    )

    $output = [Collections.Generic.List[string]]::new()
    $exitCode = -1
    Push-Location $LocalDirectory
    try {
        $Commands | & $Sftp @arguments 2>&1 | ForEach-Object {
            $line = $_.ToString()
            $output.Add($line)
            Write-Host $line
        }
        $exitCode = $LASTEXITCODE
    } finally {
        Pop-Location
    }

    if ($exitCode -ne 0) {
        throw "SFTP failed with exit code $exitCode."
    }
    foreach ($line in $output) {
        if ($line -match '(?i)(permission denied|no such file|not found|couldn.t|failure|connection closed)') {
            throw "SFTP reported a failed command: $line"
        }
    }

    return $output.ToArray()
}

$git = Resolve-RequiredCommand 'git'
$gh = Resolve-RequiredCommand 'gh'
$python = Resolve-RequiredCommand 'python'
$tar = Resolve-RequiredCommand 'tar'

$repositoryRoot = Invoke-NativeCommand -FilePath $git -Arguments @(
    '-C', $PSScriptRoot, 'rev-parse', '--show-toplevel'
) -Capture
$repositoryRoot = [IO.Path]::GetFullPath($repositoryRoot)

if ([string]::IsNullOrWhiteSpace($ConfigPath)) {
    $ConfigPath = Join-Path $repositoryRoot 'config/deploy.local.psd1'
} elseif (-not [IO.Path]::IsPathRooted($ConfigPath)) {
    $ConfigPath = Join-Path $repositoryRoot $ConfigPath
}
$ConfigPath = [IO.Path]::GetFullPath($ConfigPath)

$repositoryData = (Invoke-NativeCommand -FilePath $gh -Arguments @(
    'repo', 'view', '--json', 'nameWithOwner,defaultBranchRef'
) -Capture | ConvertFrom-Json)
$repository = [string] $repositoryData.nameWithOwner
$defaultBranch = [string] $repositoryData.defaultBranchRef.name

$currentBranch = Invoke-NativeCommand -FilePath $git -Arguments @(
    '-C', $repositoryRoot, 'branch', '--show-current'
) -Capture
if ($currentBranch -ne $defaultBranch) {
    throw "Run deployment only from the default branch '$defaultBranch'. Current branch: '$currentBranch'."
}

$workingTreeStatus = Invoke-NativeCommand -FilePath $git -Arguments @(
    '-C', $repositoryRoot, 'status', '--porcelain'
) -Capture
if (-not [string]::IsNullOrWhiteSpace($workingTreeStatus)) {
    throw 'The working tree must be clean before preparing a production artifact.'
}

$localHead = Invoke-NativeCommand -FilePath $git -Arguments @(
    '-C', $repositoryRoot, 'rev-parse', 'HEAD'
) -Capture
$remoteHead = Invoke-NativeCommand -FilePath $gh -Arguments @(
    'api', "repos/$repository/commits/$defaultBranch", '--jq', '.sha'
) -Capture
if ($localHead -ne $remoteHead) {
    throw "Local $defaultBranch must match the current GitHub default-branch head before deployment."
}

$runData = (Invoke-NativeCommand -FilePath $gh -Arguments @(
    'api', "repos/$repository/actions/runs/$CiRunId"
) -Capture | ConvertFrom-Json)
if (
    $runData.repository.full_name -ne $repository -or
    $runData.name -ne 'CI' -or
    $runData.event -ne 'push' -or
    $runData.status -ne 'completed' -or
    $runData.conclusion -ne 'success' -or
    $runData.head_branch -ne $defaultBranch
) {
    throw 'Select a successful completed CI push run from this repository default branch.'
}

$deploySha = [string] $runData.head_sha
if ($deploySha -notmatch '^[0-9a-f]{40}$') {
    throw 'The selected CI run returned an invalid commit SHA.'
}

& $git -C $repositoryRoot merge-base --is-ancestor $deploySha $localHead
if ($LASTEXITCODE -ne 0) {
    throw 'The selected CI revision is not an ancestor of the current protected default branch.'
}

$temporaryRoot = [IO.Path]::GetFullPath([IO.Path]::GetTempPath())
$workspace = Join-Path $temporaryRoot "smps-deploy-$([guid]::NewGuid().ToString('N'))"
$artifactDirectory = Join-Path $workspace 'artifact'
$releaseDirectory = Join-Path $workspace 'release'

New-Item -ItemType Directory -Path $artifactDirectory, $releaseDirectory | Out-Null

try {
    $artifactName = "smps-$deploySha"
    Invoke-NativeCommand -FilePath $gh -Arguments @(
        'run', 'download', [string] $CiRunId,
        '--repo', $repository,
        '--name', $artifactName,
        '--dir', $artifactDirectory
    )

    $archive = Join-Path $artifactDirectory "$artifactName.tar.gz"
    if (-not (Test-Path -LiteralPath $archive -PathType Leaf)) {
        throw "The expected artifact archive was not downloaded: $archive"
    }

    Invoke-NativeCommand -FilePath $python -Arguments @(
        (Join-Path $repositoryRoot 'tools/audit_public_content.py'),
        'archive',
        $archive
    )

    $artifactDigest = (Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash.ToLowerInvariant()
    Invoke-NativeCommand -FilePath $tar -Arguments @(
        '--extract', '--gzip', '--file', $archive, '--directory', $releaseDirectory
    )

    foreach ($requiredPath in @('.htaccess', 'composer.lock', 'www/index.php')) {
        if (-not (Test-Path -LiteralPath (Join-Path $releaseDirectory $requiredPath))) {
            throw "The extracted artifact is missing '$requiredPath'."
        }
    }
    foreach ($protectedPath in @(
        'config/local.neon',
        'config/test.neon',
        'config/phinx.php',
        'config/phinx.yaml',
        'www/dokumenty',
        'www/images/carousel',
        'log',
        'temp'
    )) {
        if (Test-Path -LiteralPath (Join-Path $releaseDirectory $protectedPath)) {
            throw "The extracted artifact contains protected path '$protectedPath'."
        }
    }

    Write-Host "Repository: $repository"
    Write-Host "CI run:     $CiRunId"
    Write-Host "Commit:     $deploySha"
    Write-Host "Artifact:   $artifactName"
    Write-Host "SHA-256:    $artifactDigest"

    if ($Mode -eq 'Prepare') {
        Write-Host 'Preparation completed. No connection to production was attempted.'
        return
    }

    if ($Mode -eq 'Rehearse') {
        Invoke-LocalDeploymentRehearsal -Workspace $workspace -ReleaseDirectory $releaseDirectory
        return
    }

    $sftp = Resolve-RequiredCommand 'sftp'
    $sshKeygen = Resolve-RequiredCommand 'ssh-keygen'
    $configuration = Read-DeploymentConfiguration -Path $ConfigPath -SshKeygen $sshKeygen

    Write-Host 'Starting read-only SFTP preflight. OpenSSH will prompt for the password.'
    $preflightOutput = Invoke-SftpCommands `
        -Sftp $sftp `
        -Configuration $configuration `
        -Commands @('pwd', 'cd ..', 'pwd', 'quit') `
        -LocalDirectory $releaseDirectory

    $rootCount = @($preflightOutput | Where-Object { $_ -eq 'Remote working directory: /' }).Count
    if ($rootCount -ne 2) {
        throw 'The SFTP account did not remain at its expected restricted root.'
    }
    Write-Host 'Read-only SFTP target and restricted root verified.'

    if ($Mode -eq 'Preflight') {
        Write-Host 'Preflight completed. No remote write command was issued.'
        return
    }

    if ($Mode -eq 'WriteTest') {
        $writeTestId = [guid]::NewGuid().ToString('N').Substring(0, 12)
        $remoteTestDirectory = ".smps-deploy-check-$writeTestId"
        $localMarkerName = "smps-write-test-$writeTestId.txt"
        $localMarkerPath = Join-Path $releaseDirectory $localMarkerName
        [IO.File]::WriteAllText(
            $localMarkerPath,
            "Synthetic SMPS SFTP write test $writeTestId. No production data.",
            [Text.UTF8Encoding]::new($false)
        )

        Write-Host ''
        Write-Host "The next operation creates and completely removes isolated directory '$remoteTestDirectory'."
        Write-Host 'It uploads one synthetic marker, renames it, removes it, and removes the empty directory.'
        $expectedConfirmation = "WRITE TEST $writeTestId"
        $confirmation = Read-Host "Type '$expectedConfirmation' to run the isolated remote write test"
        if ($confirmation -cne $expectedConfirmation) {
            throw 'Isolated remote write test was not confirmed.'
        }

        Write-Host 'Starting isolated SFTP write test. OpenSSH will prompt for the password again.'
        Invoke-SftpCommands `
            -Sftp $sftp `
            -Configuration $configuration `
            -Commands @(
                "mkdir $remoteTestDirectory",
                "cd $remoteTestDirectory",
                "put $localMarkerName marker.txt",
                'rename marker.txt marker-renamed.txt',
                'rm marker-renamed.txt',
                'cd ..',
                "rmdir $remoteTestDirectory",
                'pwd',
                'quit'
            ) `
            -LocalDirectory $releaseDirectory | Out-Null

        Write-Host "Isolated write/create/rename/delete behavior verified; '$remoteTestDirectory' was removed."
        return
    }

    $shortSha = $deploySha.Substring(0, 12)
    Write-Host ''
    Write-Host 'The next operation overlays application files without remote deletion.'
    Write-Host 'It does not create backups, enable maintenance, run migrations, edit protected configuration, clear caches, or perform acceptance checks.'
    Write-Host 'Continue only after those production prerequisites have been completed for this exact revision.'
    $expectedConfirmation = "DEPLOY $shortSha"
    $confirmation = Read-Host "Type '$expectedConfirmation' to start the production upload"
    if ($confirmation -cne $expectedConfirmation) {
        throw 'Production upload was not confirmed.'
    }

    Write-Host 'Starting production overlay. OpenSSH will prompt for the password again.'
    Invoke-SftpCommands `
        -Sftp $sftp `
        -Configuration $configuration `
        -Commands @(
            'put .htaccess',
            'put -R app',
            'put -R bin',
            'put -R config',
            'put -R db',
            'put -R vendor',
            'put -R www',
            'put composer.json',
            'put composer.lock',
            'put README.md',
            'put THIRD_PARTY_NOTICES.md',
            'quit'
        ) `
        -LocalDirectory $releaseDirectory | Out-Null

    Write-Host "Upload completed for tested revision $deploySha. Production acceptance and any cache/configuration work remain separate manual steps."
} finally {
    if ($KeepWorkspace) {
        Write-Host "Temporary workspace retained at: $workspace"
    } else {
        Remove-TemporaryWorkspace -Workspace $workspace -TemporaryRoot $temporaryRoot
    }
}
