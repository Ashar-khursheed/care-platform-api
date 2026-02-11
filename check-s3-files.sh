#!/bin/bash

echo "=== Checking All S3 Upload Files on Production ==="
echo ""

files=(
    "app/Services/S3StorageService.php"
    "app/Services/ImageUploadService.php"
    "app/Http/Controllers/Api/V1/MessageController.php"
    "app/Http/Controllers/Api/V1/Admin/AdminSliderController.php"
    "app/Http/Controllers/Api/V1/Admin/AdminCmsController.php"
    "app/Http/Controllers/Api/V1/User/ProfileController.php"
)

for file in "${files[@]}"; do
    echo "Checking: $file"
    result=$(grep -n "Storage::disk('s3')->put.*'public'" "$file" 2>/dev/null)
    
    if [ -z "$result" ]; then
        echo "  ✓ OK - No 'public' ACL parameter found"
    else
        echo "  ✗ ISSUE - Found 'public' ACL parameter:"
        echo "$result"
    fi
    echo ""
done

echo "=== Check Complete ==="
