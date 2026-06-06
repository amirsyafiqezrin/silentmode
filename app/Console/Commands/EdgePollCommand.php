<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EdgePollCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'edge:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll the central server for download jobs (Client Mode)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('NODE_ROLE') !== 'client') {
            $this->error('This command can only be run when NODE_ROLE=client in .env');
            return Command::FAILURE;
        }

        $serverUrl = env('CENTRAL_SERVER_URL', 'http://localhost');
        $apiToken = env('CLIENT_API_TOKEN');

        if (!$apiToken) {
            $this->error('CLIENT_API_TOKEN is not set in .env');
            return Command::FAILURE;
        }

        $this->info("Starting edge polling daemon. Connecting to $serverUrl...");

        while (true) {
            try {
                // Poll for jobs
                $response = Http::withToken($apiToken)
                    ->timeout(10)
                    ->get(rtrim($serverUrl, '/') . '/api/agent/jobs');

                if ($response->successful()) {
                    $jobs = $response->json('jobs', []);
                    
                    if (count($jobs) > 0) {
                        $this->info("Found " . count($jobs) . " pending jobs.");
                    }

                    foreach ($jobs as $job) {
                        $this->processJob($serverUrl, $apiToken, $job);
                    }
                } else {
                    $this->error("Failed to fetch jobs: " . $response->status() . " " . $response->body());
                }
            } catch (\Exception $e) {
                $this->error("Connection error: " . $e->getMessage());
            }

            // Sleep before next poll to avoid hammering the server
            sleep(5);
        }
    }

    private function processJob($serverUrl, $apiToken, $job)
    {
        $this->info("Processing Job ID: {$job['id']}");
        
        // Define the target file (in a real scenario, this would be $HOME/file_to_download.txt)
        // For testing across OS, we use user's home dir or a fallback.
        $home = getenv('HOME') ?: getenv('USERPROFILE');
        $filePath = $home . DIRECTORY_SEPARATOR . 'file_to_download.txt';

        // Fallback for Docker testing: check the project root directory
        // because the host's home directory is not mounted into the Docker container.
        if (!file_exists($filePath) && file_exists(base_path('file_to_download.txt'))) {
            $filePath = base_path('file_to_download.txt');
        }

        if (!file_exists($filePath)) {
            $this->error("Target file does not exist: $filePath");
            // Optionally, we could notify the server that the job failed here.
            return;
        }

        $this->info("Uploading $filePath...");

        try {
            // Stream the file via HTTP POST. 
            // fopen() creates a stream resource, which Laravel's HTTP client handles efficiently without loading into RAM.
            $fileStream = fopen($filePath, 'r');
            
            $uploadResponse = Http::withToken($apiToken)
                ->timeout(300) // 5 minutes timeout for 100MB
                ->attach('file', $fileStream, 'file_to_download.txt')
                ->post(rtrim($serverUrl, '/') . "/api/agent/upload/{$job['id']}");

            if (is_resource($fileStream)) {
                fclose($fileStream);
            }

            if ($uploadResponse->successful()) {
                $this->info("Job ID {$job['id']} completed successfully.");
            } else {
                $this->error("Job ID {$job['id']} upload failed: " . $uploadResponse->status() . " " . $uploadResponse->body());
            }

        } catch (\Exception $e) {
            $this->error("Exception during upload: " . $e->getMessage());
        }
    }
}
