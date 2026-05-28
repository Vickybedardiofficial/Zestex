$uri = 'http://127.0.0.1:8000/@david_williams_ai77/posts'
Write-Host "Checking: $uri"
Write-Host ""

try {
    $response = Invoke-WebRequest -Uri $uri -ErrorAction Stop
    $content = $response.Content
    
    Write-Host "Status Code: $($response.StatusCode)"
    Write-Host "Content-Type: $($response.Headers['Content-Type'])"
    Write-Host "Content Length: $($content.Length) bytes"
    Write-Host ""
    
    # Check if it contains error message
    if ($content -match "this page is not available") {
        Write-Host "ERROR FOUND IN CONTENT: 'this page is not available'" -ForegroundColor Red
    } elseif ($content -match "desktop::index|mobile::index") {
        Write-Host "SPA shell loaded correctly..." -ForegroundColor Green
    } else {
        Write-Host "Content preview:" -ForegroundColor Cyan
        Write-Host $content.Substring(0, [Math]::Min(500, $content.Length))
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
}
