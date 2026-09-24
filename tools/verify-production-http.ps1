#Requires -Version 7.2

[CmdletBinding()]
param(
	[Parameter(Mandatory = $true)]
	[uri] $BaseUrl,

	[ValidateSet('Baseline', 'Maintenance', 'Acceptance')]
	[string] $Mode = 'Baseline',

	[ValidateRange(1, 120)]
	[int] $TimeoutSeconds = 20
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

if ($BaseUrl.Scheme -ne 'https') {
	throw 'Production HTTP verification requires an HTTPS base URL.'
}
if (-not [string]::IsNullOrEmpty($BaseUrl.Query) -or -not [string]::IsNullOrEmpty($BaseUrl.Fragment)) {
	throw 'BaseUrl must not contain a query string or fragment.'
}

$normalizedBaseUrl = [uri] ($BaseUrl.AbsoluteUri.TrimEnd('/') + '/')
$handler = [Net.Http.HttpClientHandler]::new()
$handler.AllowAutoRedirect = $false
$client = [Net.Http.HttpClient]::new($handler)
$client.Timeout = [TimeSpan]::FromSeconds($TimeoutSeconds)
$client.DefaultRequestHeaders.UserAgent.ParseAdd('SMPS-production-verifier/1.0')

function Invoke-StatusProbe {
	param(
		[Parameter(Mandatory = $true)]
		[string] $Path,

		[Parameter(Mandatory = $true)]
		[int[]] $AllowedStatus,

		[switch] $RequireSameOriginRedirect
	)

	$requestUri = [uri]::new($normalizedBaseUrl, $Path.TrimStart('/'))
	$response = $null
	try {
		$request = [Net.Http.HttpRequestMessage]::new([Net.Http.HttpMethod]::Get, $requestUri)
		try {
			$response = $client.SendAsync(
				$request,
				[Net.Http.HttpCompletionOption]::ResponseHeadersRead
			).GetAwaiter().GetResult()
		} finally {
			$request.Dispose()
		}

		$status = [int] $response.StatusCode
		if ($AllowedStatus -notcontains $status) {
			throw "Unexpected HTTP status $status for '$Path'; expected one of $($AllowedStatus -join ', ')."
		}

		if ($RequireSameOriginRedirect -and $status -ge 300 -and $status -lt 400) {
			$location = $response.Headers.Location
			if ($null -eq $location) {
				throw "Redirect response for '$Path' has no Location header."
			}
			$redirectUri = [uri]::new($normalizedBaseUrl, $location)
			if (
				$redirectUri.Scheme -ne $normalizedBaseUrl.Scheme -or
				$redirectUri.Host -ne $normalizedBaseUrl.Host -or
				$redirectUri.Port -ne $normalizedBaseUrl.Port
			) {
				throw "Redirect response for '$Path' leaves the production origin."
			}
		}

		Write-Host "HTTP $status $Path"
	} finally {
		if ($null -ne $response) {
			$response.Dispose()
		}
	}
}

try {
	if ($Mode -eq 'Maintenance') {
		Invoke-StatusProbe -Path '/' -AllowedStatus @(503)
		Invoke-StatusProbe -Path '/maintenance.html' -AllowedStatus @(200)
	} else {
		Invoke-StatusProbe -Path '/' -AllowedStatus @(200, 301, 302, 303, 307, 308) -RequireSameOriginRedirect
		Invoke-StatusProbe -Path '/favicon.ico' -AllowedStatus @(200)
	}

	$protectedPaths = @(
		'/.git/HEAD',
		'/composer.json',
		'/config/common.neon',
		'/config/local.neon',
		'/config/phinx.php',
		'/db/',
		'/log/',
		'/temp/',
		'/vendor/autoload.php',
		'/www/dokumenty/',
		'/RELEASE_SHA',
		'/RELEASE_MANIFEST.sha256'
	)
	$protectedStatus = if ($Mode -eq 'Maintenance') { @(403, 404, 503) } else { @(403, 404) }
	foreach ($path in $protectedPaths) {
		Invoke-StatusProbe -Path $path -AllowedStatus $protectedStatus
	}

	if ($Mode -eq 'Acceptance') {
		Invoke-StatusProbe -Path '/sign/in' -AllowedStatus @(301, 302, 303, 307, 308) -RequireSameOriginRedirect
		Invoke-StatusProbe -Path '/authentication/login' -AllowedStatus @(200)
		Invoke-StatusProbe -Path '/www/index.php' -AllowedStatus @(403, 404)
	}

	Write-Host "Production HTTP $Mode verification passed. Response bodies were not read."
} finally {
	$client.Dispose()
	$handler.Dispose()
}
