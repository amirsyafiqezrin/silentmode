<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the test client
        Client::firstOrCreate(
            ['api_token' => 'test-token-123'],
            ['name' => 'Test Client']
        );
    }
}
