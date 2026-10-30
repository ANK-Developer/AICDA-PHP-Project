# Rebuilds the React site (AICDA/AICDA, unchanged) into public_html.
# Run from PowerShell:  .\build-frontend.ps1
# (Do not run the build from Git Bash: it rewrites "/api/v1" into a Windows path.)

$ErrorActionPreference = "Stop"
$root = $PSScriptRoot
$frontend = Join-Path $root "..\AICDA"
$out = Join-Path $root "build\frontend"
$public = Join-Path $root "public_html"

Push-Location $frontend
try {
    # Same-domain API: the site calls /api/v1, served by public_html/api.
    $env:VITE_API_BASE_URL = "/api/v1"
    # vite prints harmless notices (plugin timings) on stderr; PowerShell would treat
    # them as errors under "Stop", so only the real exit code decides success.
    $ErrorActionPreference = "Continue"
    npx vite build --outDir $out --emptyOutDir 2>&1 | ForEach-Object { "$_" }
    if ($LASTEXITCODE -ne 0) { throw "vite build failed (exit code $LASTEXITCODE)" }
    $ErrorActionPreference = "Stop"
} finally {
    Pop-Location
}

# Replace only the site files; api/ and uploads/ are left alone.
Remove-Item -Recurse -Force (Join-Path $public "assets") -ErrorAction SilentlyContinue
Remove-Item -Force (Join-Path $public "index.html"), (Join-Path $public "favicon.png") -ErrorAction SilentlyContinue
Copy-Item -Recurse -Force (Join-Path $out "*") $public
Remove-Item -Recurse -Force (Join-Path $root "build")

Write-Host "Frontend copied into public_html."
