<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ListS3Files extends Command
{
    protected $signature = 'test:list-s3';
    protected $description = 'List all files in S3 bucket';

    public function handle()
    {
        $this->info('=== S3 Bucket Contents ===');
        $this->newLine();
        
        try {
            $files = Storage::disk('s3')->allFiles();
            
            if (empty($files)) {
                $this->warn('No files found in S3 bucket');
            } else {
                $this->info('Found ' . count($files) . ' files:');
                $this->newLine();
                
                foreach ($files as $file) {
                    $size = Storage::disk('s3')->size($file);
                    $url = Storage::disk('s3')->url($file);
                    
                    $this->line("📄 {$file}");
                    $this->line("   Size: " . number_format($size / 1024, 2) . " KB");
                    $this->line("   URL: {$url}");
                    $this->newLine();
                }
            }
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }
}
