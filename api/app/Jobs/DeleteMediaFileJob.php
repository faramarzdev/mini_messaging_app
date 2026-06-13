<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DeleteMediaFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $disk,
        public string $path
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! Storage::disk($this->disk)->exists($this->path)) {
            // File already gone, job succeeded
            return;
        }

        if (! Storage::disk($this->disk)->delete($this->path)) {
            throw new \RuntimeException("Failed to delete file: {$this->path}");
        }

        Log::info('Media file deleted', [
            'disk' => $this->disk,
            'path' => $this->path,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Media file deletion failed after retries', [
            'disk' => $this->disk,
            'path' => $this->path,
            'error' => $exception->getMessage(),
        ]);

        // todo: dispatch to a dead-letter queue or alert system
    }
}
