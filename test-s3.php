<?php

use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;

try {
    echo "=== S3 Configuration Test ===\n\n";
    
    // Check config
    echo "Bucket: " . config('filesystems.disks.s3.bucket') . "\n";
    echo "Region: " . config('filesystems.disks.s3.region') . "\n";
    echo "Access Key: " . substr(config('filesystems.disks.s3.key'), 0, 8) . "...\n\n";
    
    // Try to create S3 client directly
    echo "Creating S3 client...\n";
    $s3Client = new S3Client([
        'version' => 'latest',
        'region'  => config('filesystems.disks.s3.region'),
        'credentials' => [
            'key'    => config('filesystems.disks.s3.key'),
            'secret' => config('filesystems.disks.s3.secret'),
        ],
    ]);
    
    echo "✓ S3 Client created\n\n";
    
    // Test bucket access
    echo "Testing bucket access...\n";
    $result = $s3Client->headBucket([
        'Bucket' => config('filesystems.disks.s3.bucket')
    ]);
    echo "✓ Bucket exists and is accessible\n\n";
    
    // Try to upload via S3 client directly
    echo "Uploading test file...\n";
    $uploadResult = $s3Client->putObject([
        'Bucket' => config('filesystems.disks.s3.bucket'),
        'Key'    => 'test-direct-upload.txt',
        'Body'   => 'Test upload from S3 client',
        'ACL'    => 'public-read',
    ]);
    
    echo "✓ File uploaded successfully!\n";
    echo "ETag: " . $uploadResult['ETag'] . "\n";
    echo "URL: https://" . config('filesystems.disks.s3.bucket') . ".s3." . config('filesystems.disks.s3.region') . ".amazonaws.com/test-direct-upload.txt\n";
    
} catch (\Aws\S3\Exception\S3Exception $e) {
    echo "\n✗ AWS S3 Error:\n";
    echo "Code: " . $e->getAwsErrorCode() . "\n";
    echo "Message: " . $e->getAwsErrorMessage() . "\n";
    echo "Status: " . $e->getStatusCode() . "\n";
} catch (\Exception $e) {
    echo "\n✗ General Error:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
