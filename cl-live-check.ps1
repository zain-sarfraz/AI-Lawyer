$ErrorActionPreference = 'Continue'
$base = 'https://www.courtlistener.com/api/rest/v4'
$token = ''
foreach ($line in Get-Content (Join-Path $PSScriptRoot '.env')) {
    if ($line -match '^\s*COURTLISTENER_API_TOKEN\s*=\s*(\S+)\s*$') { $token = $Matches[1] }
}
$headers = @{ Authorization = "Token $token" }

Write-Output "### /courts/ : which keys are published (first record)"
$c = Invoke-RestMethod -Uri "$base/courts/?page_size=10" -Headers $headers
$first = $c.results[0]
Write-Output ("  keys=" + (($first.PSObject.Properties | ForEach-Object { $_.Name }) -join ','))
Write-Output ("  root keys=" + (($c.PSObject.Properties | ForEach-Object { $_.Name }) -join ','))

Write-Output "### jurisdiction codes present in the courts directory, with sample court names"
$all = @()
$page = 1
while ($page -le 3) {
    $p = Invoke-RestMethod -Uri "$base/courts/?page=$page" -Headers $headers
    $all += $p.results
    if (-not $p.next) { break }
    $page++
    Start-Sleep -Seconds 14
}
Write-Output ("  courts fetched=" + $all.Count)
$all | Group-Object jurisdiction | Sort-Object Name | ForEach-Object {
    $names = ($_.Group | Select-Object -First 2 | ForEach-Object { $_.full_name }) -join ' / '
    Write-Output ("  code=[" + $_.Name + "] n=" + $_.Count + " e.g. " + $names)
}

Write-Output "### does /courts/ page_size work? (compare result counts)"
$a = Invoke-RestMethod -Uri "$base/courts/?page_size=3" -Headers $headers
Write-Output ("  page_size=3 returned=" + $a.results.Count)
