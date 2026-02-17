<?php

use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\User\WelcomeEmail;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$log = "d:/care/care-platform-api/test_log.txt";
file_put_contents($log, "Attempting to send test email...\n", FILE_APPEND);

try {
    Mail::raw('This is a test email from Care Platform to verify SES SMTP credentials.', function ($message) {
        $message->to('asharkhursheed@gmail.com')
                ->subject('SES SMTP Test');
    });
    
    file_put_contents($log, "Test email sent successfully to asharkhursheed@gmail.com!\n", FILE_APPEND);
} catch (\Exception $e) {
    file_put_contents($log, "Failed to send test email: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($log, "Stack trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
}
