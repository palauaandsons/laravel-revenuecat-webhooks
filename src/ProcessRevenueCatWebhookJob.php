<?php

namespace PalauaAndSons\RevenueCatWebhooks;

use PalauaAndSons\RevenueCatWebhooks\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessRevenueCatWebhookJob extends ProcessWebhookJob
{
    public function handle()
    {
        if (! isset($this->webhookCall->payload['event_type']) || $this->webhookCall->payload['event_type'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("revenuecat-webhooks::{$this->webhookCall->payload['event_type']}", $this->webhookCall);

        $jobClass = $this->determineJobClass($this->webhookCall->payload['event_type']);

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
