<?php

namespace App\Console\Commands;

use App\Models\LoginCode;
use Illuminate\Console\Command;

class DeleteExpiredLoginCodes extends Command
{
    protected $signature = 'login-codes:cleanup';

    protected $description = 'Delete expired login codes';

    public function handle(): void
    {
        $deleted = LoginCode::where('expires_at', '<=', now())->delete();

        $this->info("Deleted {$deleted} expired login codes.");
    }
}
