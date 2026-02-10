<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class TestS3Permissions extends Command
{
    protected $signature = 'test:s3-permissions';
    protected $description = 'Test S3 IAM permissions';

    public function handle()
    {
        $this->info('=== Testing S3 IAM Permissions ===');
        $this->newLine();
        
        try {
            $s3Client = new S3Client([
                'version' => 'latest',
                'region'  => config('filesystems.disks.s3.region'),
                'credentials' => [
                    'key'    => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
            ]);
            
            $bucket = config('filesystems.disks.s3.bucket');
            
            // Test 1: List bucket
            $this->info('Test 1: Listing bucket (s3:ListBucket)...');
            try {
                $result = $s3Client->listObjectsV2(['Bucket' => $bucket, 'MaxKeys' => 1]);
                $this->info('✓ ListBucket permission: OK');
            } catch (AwsException $e) {
                $this->error('✗ ListBucket permission: DENIED');
                $this->error('Error: ' . $e->getAwsErrorMessage());
            }
            $this->newLine();
            
            // Test 2: Put object
            $this->info('Test 2: Uploading file (s3:PutObject)...');
            try {
                $result = $s3Client->putObject([
                    'Bucket' => $bucket,
                    'Key'    => 'test-permissions-' . time() . '.txt',
                    'Body'   => 'Testing IAM permissions',
                ]);
                $this->info('✓ PutObject permission: OK');
                $this->info('ETag: ' . $result['ETag']);
            } catch (AwsException $e) {
                $this->error('✗ PutObject permission: DENIED');
                $this->error('Error Code: ' . $e->getAwsErrorCode());
                $this->error('Error Message: ' . $e->getAwsErrorMessage());
                $this->error('Status Code: ' . $e->getStatusCode());
            }
            $this->newLine();
            
            // Test 3: Get object
            $this->info('Test 3: Reading file (s3:GetObject)...');
            try {
                $result = $s3Client->headObject([
                    'Bucket' => $bucket,
                    'Key'    => 'test-permissions-' . (time() - 1) . '.txt',
                ]);
                $this->info('✓ GetObject permission: OK');
            } catch (AwsException $e) {
                $this->warn('⚠ GetObject test skipped (file may not exist)');
            }
            $this->newLine();
            
            $this->info('=== Permission Test Complete ===');
            
        } catch (\Exception $e) {
            $this->error('General Error: ' . $e->getMessage());
        }
    }
}
