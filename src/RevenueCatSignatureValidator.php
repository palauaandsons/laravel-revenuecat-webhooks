<?php

namespace PalauaAndSons\RevenueCatWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class RevenueCatSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $expectedToken = config('revenuecat-webhooks.token');
        $providedToken = $request->header('Authorization');

        if ($expectedToken && $providedToken !== "Bearer {$expectedToken}") {
            return false;
        }

        return true;
    }
}
