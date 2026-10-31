<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Psy\Exception\RuntimeException;

class UserSeeder extends Seeder
{
  public function run(): void
{
    User::factory()->count(10)->create();

    $this->createLocalUser();
}


    private function createLocalUser(): void
    {
        $email = env('USER_EMAIL');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException(
                'USER_EMAIL must be set to a valid email address in .env for local development.'
            );
        }

        User::firstOrCreate(
            ['email' => $email],
            [
                'first_name' => 'Local',
                'last_name' => 'Developer',
            ],
        );
    }
}
