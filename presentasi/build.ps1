param(
    [string]$OutFile = ""
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$chrome = @(
    'C:\Program Files\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $chrome) { throw 'Chrome atau Edge tidak ditemukan.' }

$css = [System.IO.File]::ReadAllText((Join-Path $root 'assets\deck.css'))
$js  = [System.IO.File]::ReadAllText((Join-Path $root 'assets\deck.js'))

$parts = Get-ChildItem (Join-Path $root 'slides') -Filter '*.html' | Sort-Object Name
$slides = ($parts | ForEach-Object { [System.IO.File]::ReadAllText($_.FullName) }) -join "`r`n"

$html = @"
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>API sebagai Integrasi &amp; Format Data — IFT5251P</title>
<style>
$css
</style>
</head>
<body>
$slides
<script>
$js
</script>
</body>
</html>
"@

$index = Join-Path $root 'index.html'
$utf8 = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($index, $html, $utf8)
Write-Host "HTML  -> $index"

if (-not $OutFile) {
    $OutFile = Join-Path (Split-Path -Parent $root) '2026_2027_1_IFT5251P_PA_Pemrograman Berbasis Kerangka Kerja_ API (revisi).pdf'
}

$uri = ([System.Uri]$index).AbsoluteUri
$chromeArgs = @(
    '--headless=new',
    '--disable-gpu',
    '--no-sandbox',
    '--no-pdf-header-footer',
    '--run-all-compositor-stages-before-draw',
    '--virtual-time-budget=8000',
    "--print-to-pdf=$OutFile",
    $uri
)

& $chrome @chromeArgs 2>&1 | Out-Null

if (-not (Test-Path $OutFile)) { throw "PDF gagal dibuat: $OutFile" }
Write-Host "PDF   -> $OutFile"
