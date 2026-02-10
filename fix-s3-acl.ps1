$files = @(
    "app\Services\S3StorageService.php",
    "app\Services\ImageUploadService.php",
    "app\Http\Controllers\Api\V1\MessageController.php",
    "app\Http\Controllers\Api\V1\Admin\AdminSliderController.php",
    "app\Http\Controllers\Api\V1\Admin\AdminCmsController.php"
)

foreach ($file in $files) {
    $content = Get-Content $file -Raw
    $content = $content -replace ", 'public'\)", ")"
    Set-Content $file $content -NoNewline
    Write-Host "Updated: $file"
}

Write-Host "`nAll files updated successfully!"
