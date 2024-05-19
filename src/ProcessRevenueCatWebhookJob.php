<?php

namespace PalauaAndSons\RevenueCatWebhooks;

use PalauaAndSons\RevenueCatWebhooks\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessRevenueCatWebhookJob extends ProcessWebhookJob
{
    public function handle()
    {
        $event = $this->webhookCall->payload['event'] ?? null;

        if (! $event || ! isset($event['type']) || $event['type'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        // Normalize the event type to lowercase, RevenueCat sends them as uppercase
        $eventType = strtolower($event['type']);

        event("revenuecat-webhooks::{$eventType}", $this->webhookCall);

        $jobClass = $this->determineJobClass($eventType);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    protected function determineJobClass(string $eventType): string
    {
        return config("revenuecat-webhooks.jobs.{$eventType}", '');
    }
}
