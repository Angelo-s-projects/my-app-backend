<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendTestEmail implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Mail::raw('Hello from a Laravel queued job!', function ($message) {
            $message
                ->to('you@example.com')
                ->subject('Mailpit test');
        });
    }
}