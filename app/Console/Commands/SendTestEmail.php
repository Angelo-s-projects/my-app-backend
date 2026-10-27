<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestEmail extends Command
{
    protected $signature = 'app:send-test-email';

    protected $description = 'Send a test email through Mailpit';

    public function handle(): int
    {
        Mail::raw('Hello from a Laravel Artisan command!', function ($message) {
            $message
                ->to('you@example.com')
                ->subject('Mailpit test');
        });

        $this->info('Test email sent successfully.');

        return self::SUCCESS;
    }
}