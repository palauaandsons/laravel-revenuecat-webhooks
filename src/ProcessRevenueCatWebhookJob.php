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

        event("revenuecat-webhooks::{$event['type']}", $this->webhookCall);

        $jobClass = $this->determineJobClass($event['type']);

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
