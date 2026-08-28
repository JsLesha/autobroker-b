<?php

namespace App\Jobs;

use App\Infrastructure\Messaging\IngestBus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IngestExternalEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $provider,
        public array $payload,
    ) {
        $this->onQueue('ingest');
    }

    public function handle(IngestBus $bus): void
    {
        $bus->publish('ingest.'.$this->provider, $this->payload);
    }
}
