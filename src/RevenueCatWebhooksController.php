<?php

namespace PalauaAndSons\RevenueCatWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Models\WebhookCall;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Spatie\WebhookClient\WebhookProfile\ProcessEverythingWebhookProfile;

class RevenueCatWebhooksController
{
    public function __invoke(Request $request, ?string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name'                => 'revenuecat',
            'signature_validator' => RevenueCatSignatureValidator::class,
            'webhook_profile'     => ProcessEverythingWebhookProfile::class,
            'webhook_model'       => WebhookCall::class,
            'process_webhook_job' => config('revenuecat-webhooks.model'),
        ]);

        (new WebhookProcessor($request, $webhookConfig))->process();

        return response()->json(['message' => 'ok']);
    }
}
