<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Aws\S3\S3Client;

class TestS3Connection extends Command
{
    protected $signature = 'test:s3';
    protected $description = 'Test S3 connection and upload';

    public function handle()
    {
        $this->info('=== S3 Configuration Test ===');
        $this->newLine();
        
        try {
            // Check config
            $this->info('Bucket: ' . config('filesystems.disks.s3.bucket'));
            $this->info('Region: ' . config('filesystems.disks.s3.region'));
            $this->info('Access Key: ' . substr(config('filesystems.disks.s3.key'), 0, 8) . '...');
            $this->newLine();
            
            // Create S3 client
            $this->info('Creating S3 client...');
            $s3Client = new S3Client([
                'version' => 'latest',
                'region'  => config('filesystems.disks.s3.region'),
                'credentials' => [
                    'key'    => config('filesystems.disks.s3.key'),
                    'secret' => config('filesystems.disks.s3.secret'),
                ],
            ]);
            
            $this->info('✓ S3 Client created');
            $this->newLine();
            
            // Test bucket access
            $this->info('Testing bucket access...');
            $s3Client->headBucket([
                'Bucket' => config('filesystems.disks.s3.bucket')
            ]);
            $this->info('✓ Bucket exists and is accessible');
            $this->newLine();
            
            // Try upload
            $this->info('Uploading test file...');
            $uploadResult = $s3Client->putObject([
                'Bucket' => config('filesystems.disks.s3.bucket'),
                'Key'    => 'test-upload-' . time() . '.txt',
                'Body'   => 'Test upload from Laravel',
            ]);
            
            $this->info('✓ File uploaded successfully!');
            $this->info('ETag: ' . $uploadResult['ETag']);
            $this->newLine();
            
            $this->info('SUCCESS: S3 is working correctly!');
            
        } catch (\Aws\S3\Exception\S3Exception $e) {
            $this->error('AWS S3 Error:');
            $this->error('Code: ' . $e->getAwsErrorCode());
            $this->error('Message: ' . $e->getAwsErrorMessage());
            $this->error('Status: ' . $e->getStatusCode());
        } catch (\Exception $e) {
            $this->error('General Error:');
            $this->error($e->getMessage());
        }
    }
}
