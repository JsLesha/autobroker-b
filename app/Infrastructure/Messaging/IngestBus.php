<?php

namespace App\Infrastructure\Messaging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Throwable;

class IngestBus
{
    public function publish(string $topic, array $payload): void
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        try {
            Redis::xadd('ingest:'.$topic, '*', [
                'payload' => $body ?: '{}',
                'at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            Log::info('ingest.'.$topic, $payload);
            Log::debug('Ingest bus fallback: '.$e->getMessage());
        }

        $brokers = (string) config('services.kafka.brokers');
        if ($brokers !== '' && extension_loaded('rdkafka')) {
            $this->publishKafka($brokers, $topic, $body ?: '{}');
        }
    }

    private function publishKafka(string $brokers, string $topic, string $body): void
    {
        $conf = new \RdKafka\Conf;
        $conf->set('bootstrap.servers', $brokers);
        $producer = new \RdKafka\Producer($conf);
        $producer->newTopic($topic)->produce(RD_KAFKA_PARTITION_UA, 0, $body);
        $producer->flush(1000);
    }
}
