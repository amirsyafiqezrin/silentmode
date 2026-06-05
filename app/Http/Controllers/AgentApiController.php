<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\DownloadJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AgentApiController extends Controller
{
    // Server API: Manually trigger a download
    public function triggerDownload($client_id)
    {
        $client = Client::findOrFail($client_id);
        
        $job = DownloadJob::create([
            'client_id' => $client->id,
            'status' => 'pending'
        ]);

        return response()->json(['message' => 'Download triggered successfully', 'job' => $job]);
    }

    // Client API: Poll for pending jobs
    public function getJobs(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $client = Client::where('api_token', $token)->first();
        if (!$client) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $jobs = DownloadJob::where('client_id', $client->id)
                           ->where('status', 'pending')
                           ->get();

        return response()->json(['jobs' => $jobs]);
    }

    // Client API: Upload the 100MB file
    public function uploadFile(Request $request, $job_id)
    {
        $token = $request->bearerToken();
        $client = Client::where('api_token', $token)->first();
        if (!$client) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $job = DownloadJob::where('id', $job_id)->where('client_id', $client->id)->firstOrFail();
        
        if (!$request->hasFile('file')) {
            $job->update(['status' => 'failed']);
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        try {
            $job->update(['status' => 'uploading']);

            // Efficient streaming upload: 
            // Laravel's storeAs method will stream the uploaded tmp file to the destination without loading it fully into RAM.
            $file = $request->file('file');
            $filename = 'client_' . $client->id . '_job_' . $job->id . '_' . time() . '.txt';
            
            $path = $file->storeAs('downloads', $filename, 'local');

            $job->update([
                'status' => 'completed',
                'file_path' => $path
            ]);

            return response()->json(['message' => 'Upload successful', 'path' => $path]);

        } catch (\Exception $e) {
            Log::error('Upload failed: ' . $e->getMessage());
            $job->update(['status' => 'failed']);
            return response()->json(['error' => 'Upload failed'], 500);
        }
    }
}
