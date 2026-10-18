# Manual deploy from a Windows PC: same steps as the GitHub Actions deploy job.
# Usage (after git push):  powershell -ExecutionPolicy Bypass -File scripts/deploy.ps1
param(
    [string]$Server = 'scipark@20.193.50.132',
    [string]$AppPath = '/home/scipark/htdocs/scipark.australiaeast.cloudapp.azure.com'
)

$ErrorActionPreference = 'Stop'
Set-Location (Split-Path $PSScriptRoot)

function Invoke-Step([string]$Name, [scriptblock]$Command) {
    Write-Host "`n==> $Name" -ForegroundColor Cyan
    & $Command
    if ($LASTEXITCODE -ne 0) { throw "$Name failed (exit code $LASTEXITCODE)" }
}

Invoke-Step 'Check that main is pushed' {
    if (git status --porcelain) { throw 'Uncommitted changes: commit and push first.' }
    git fetch --quiet origin main
    if ((git rev-parse HEAD) -ne (git rev-parse origin/main)) { throw 'Local main differs from GitHub: commit and push first.' }
}
Invoke-Step 'Build assets' { npm run build }
Invoke-Step 'Update code, dependencies and database' {
    ssh $Server "cd '$AppPath' && git fetch --prune origin main && git reset --hard origin/main && bash scripts/deploy.sh"
}
Invoke-Step 'Upload assets' { scp -r -q public/build "${Server}:$AppPath/public/build-new" }
Invoke-Step 'Switch to new assets' { ssh $Server "cd '$AppPath/public' && rm -rf build && mv build-new build" }

Write-Host "`nDeployed." -ForegroundColor Green
