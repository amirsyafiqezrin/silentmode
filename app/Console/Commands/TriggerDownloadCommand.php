<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Client;
use App\Models\DownloadJob;

class TriggerDownloadCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'server:trigger {client_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger a download from a specific client (Server Mode)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (env('NODE_ROLE') !== 'server') {
            $this->error('This command can only be run when NODE_ROLE=server in .env');
            return Command::FAILURE;
        }

        $clientId = $this->argument('client_id');
        $client = Client::find($clientId);

        if (!$client) {
            $this->error("Client with ID $clientId not found.");
            return Command::FAILURE;
        }

        $job = DownloadJob::create([
            'client_id' => $client->id,
            'status' => 'pending'
        ]);

        $this->info("Download triggered successfully! Job ID: {$job->id}");
        $this->info("The edge client will pick it up on its next polling cycle.");

        return Command::SUCCESS;
    }
}
