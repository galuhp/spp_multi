# Jalankan backend Go E-SPP secara lokal memakai salinan database SQLite Laravel.
#
#   .\run-local.ps1
#
# Jika PowerShell memblokir eksekusi script:
#   powershell -ExecutionPolicy Bypass -File .\run-local.ps1
#
$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $root

if (-not (Get-Command go -ErrorAction SilentlyContinue)) {
    $portableBin = Join-Path $env:LOCALAPPDATA "Temp\opencode\go-toolchain\go\bin"

    if (Test-Path (Join-Path $portableBin "go.exe")) {
        $env:PATH = "$portableBin;$env:PATH"
    } else {
        Write-Host "Go belum terpasang." -ForegroundColor Red
        Write-Host "Install dari https://go.dev/dl/ atau jalankan:" -ForegroundColor Yellow
        Write-Host "  winget install --id GoLang.Go -e" -ForegroundColor Yellow
        exit 1
    }
}

$sourceDb = Join-Path $root "..\database\database.sqlite"
$devDb = Join-Path $root "dev.sqlite"

if (-not (Test-Path $sourceDb)) {
    Write-Host "Database Laravel tidak ditemukan: $sourceDb" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $devDb)) {
    Copy-Item $sourceDb $devDb

    # Ikutkan file WAL/SHM jika Laravel baru saja menulis data.
    foreach ($suffix in @("-wal", "-shm")) {
        if (Test-Path "$sourceDb$suffix") {
            Copy-Item "$sourceDb$suffix" "$devDb$suffix"
        }
    }

    Write-Host "Salinan database dibuat: $devDb"
    Write-Host "Jika Laravel sedang berjalan, hentikan dulu agar salinan konsisten." -ForegroundColor Yellow
}

$env:DB_DRIVER = "sqlite"
$env:DB_DSN = ($devDb -replace "\\", "/")
$env:DB_AUTO_MIGRATE = "true"
$env:CACHE_DRIVER = "memory"
$env:APP_TIER = "basic"
$env:TRUSTED_PROXIES = "none"
$env:APP_PORT = "8080"
$env:GIN_MODE = "debug"
$env:LOG_LEVEL = "info"

if ([string]::IsNullOrWhiteSpace($env:JWT_SECRET)) {
    $env:JWT_SECRET = "dev-secret-lokal-minimal-32-karakter-123456"
}

Write-Host "Membuat token demo..." -ForegroundColor Cyan
$adminToken = (go run ./cmd/token -user 1 -username admin -role admin 2>$null)
$petugasToken = (go run ./cmd/token -user 2 -username petugas -role petugas 2>$null)

Write-Host ""
Write-Host "API     : http://127.0.0.1:8080"
Write-Host "Health  : http://127.0.0.1:8080/healthz"
Write-Host "Ready   : http://127.0.0.1:8080/readyz"
Write-Host ""
Write-Host "Token admin   :" -ForegroundColor Green
Write-Host $adminToken
Write-Host "Token petugas :" -ForegroundColor Green
Write-Host $petugasToken
Write-Host ""
Write-Host "Tekan Ctrl+C untuk berhenti." -ForegroundColor Yellow
Write-Host ""

go run ./cmd/api
