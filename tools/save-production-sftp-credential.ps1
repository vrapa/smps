[CmdletBinding()]
param(
    [string] $ConfigPath,
    [string] $CredentialPath,
    [switch] $Gui
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if (-not $IsWindows) {
    throw 'The local credential must be stored on Windows so Export-Clixml uses DPAPI protection.'
}

$repositoryRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
if ([string]::IsNullOrWhiteSpace($ConfigPath)) {
    $ConfigPath = Join-Path $repositoryRoot 'config/deploy.local.psd1'
} elseif (-not [IO.Path]::IsPathRooted($ConfigPath)) {
    $ConfigPath = Join-Path $repositoryRoot $ConfigPath
}
$ConfigPath = [IO.Path]::GetFullPath($ConfigPath)

if (-not (Test-Path -LiteralPath $ConfigPath -PathType Leaf)) {
    throw "Local deployment configuration does not exist: $ConfigPath"
}

$credentialDirectory = [IO.Path]::GetFullPath(
    (Join-Path $repositoryRoot 'config/credentials')
)
if ([string]::IsNullOrWhiteSpace($CredentialPath)) {
    $CredentialPath = Join-Path $credentialDirectory 'production-sftp.credential.xml'
} elseif (-not [IO.Path]::IsPathRooted($CredentialPath)) {
    $CredentialPath = Join-Path $repositoryRoot $CredentialPath
}
$CredentialPath = [IO.Path]::GetFullPath($CredentialPath)

if (-not $CredentialPath.StartsWith(
    $credentialDirectory + [IO.Path]::DirectorySeparatorChar,
    [StringComparison]::OrdinalIgnoreCase
)) {
    throw 'The credential file must stay inside the ignored config/credentials directory.'
}

$relativeCredentialPath = [IO.Path]::GetRelativePath(
    $repositoryRoot,
    $CredentialPath
).Replace([IO.Path]::DirectorySeparatorChar, '/')
& git -C $repositoryRoot check-ignore --quiet -- $relativeCredentialPath
if ($LASTEXITCODE -ne 0) {
    throw "Git does not ignore the credential path: $relativeCredentialPath"
}

$configuration = Import-PowerShellDataFile -LiteralPath $ConfigPath
$userName = [string] $configuration.UserName
if ([string]::IsNullOrWhiteSpace($userName)) {
    throw "Local deployment configuration is missing 'UserName'."
}

New-Item -ItemType Directory -Path $credentialDirectory -Force | Out-Null
if ($Gui) {
    Add-Type -AssemblyName System.Drawing
    Add-Type -AssemblyName System.Windows.Forms

    $form = [Windows.Forms.Form]::new()
    $form.Text = 'Save SMPS production SFTP credential'
    $form.ClientSize = [Drawing.Size]::new(520, 165)
    $form.StartPosition = [Windows.Forms.FormStartPosition]::CenterScreen
    $form.FormBorderStyle = [Windows.Forms.FormBorderStyle]::FixedDialog
    $form.MaximizeBox = $false
    $form.MinimizeBox = $false
    $form.TopMost = $true

    $label = [Windows.Forms.Label]::new()
    $label.AutoSize = $true
    $label.Location = [Drawing.Point]::new(20, 20)
    $label.Text = "SFTP password for $userName"
    $form.Controls.Add($label)

    $passwordBox = [Windows.Forms.TextBox]::new()
    $passwordBox.Location = [Drawing.Point]::new(20, 50)
    $passwordBox.Size = [Drawing.Size]::new(480, 28)
    $passwordBox.UseSystemPasswordChar = $true
    $form.Controls.Add($passwordBox)

    $okButton = [Windows.Forms.Button]::new()
    $okButton.DialogResult = [Windows.Forms.DialogResult]::OK
    $okButton.Location = [Drawing.Point]::new(330, 105)
    $okButton.Size = [Drawing.Size]::new(80, 32)
    $okButton.Text = 'Save'
    $form.Controls.Add($okButton)

    $cancelButton = [Windows.Forms.Button]::new()
    $cancelButton.DialogResult = [Windows.Forms.DialogResult]::Cancel
    $cancelButton.Location = [Drawing.Point]::new(420, 105)
    $cancelButton.Size = [Drawing.Size]::new(80, 32)
    $cancelButton.Text = 'Cancel'
    $form.Controls.Add($cancelButton)

    $form.AcceptButton = $okButton
    $form.CancelButton = $cancelButton
    $form.Add_Shown({ $passwordBox.Focus() })

    try {
        if ($form.ShowDialog() -ne [Windows.Forms.DialogResult]::OK) {
            throw 'Credential storage was cancelled.'
        }
        if ([string]::IsNullOrEmpty($passwordBox.Text)) {
            throw 'The SFTP password cannot be empty.'
        }
        $password = ConvertTo-SecureString $passwordBox.Text -AsPlainText -Force
        $passwordBox.Clear()
    } finally {
        $form.Dispose()
    }
} else {
    $password = Read-Host -Prompt "SFTP password for $userName" -AsSecureString
}
$credential = [PSCredential]::new($userName, $password)
$credential | Export-Clixml -LiteralPath $CredentialPath -Force

Write-Host "DPAPI-protected SFTP credential saved to $relativeCredentialPath."
Write-Host 'It can be decrypted only by this Windows account on this workstation.'
