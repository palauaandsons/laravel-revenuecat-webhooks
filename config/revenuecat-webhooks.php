<?php

return [

    /*
     * The Authorization Header value that will be used to validate incoming webhook requests.
     * RevenueCat will send an HTTP Authorization header with this value in each POST request
     * sent to your webhook server URL.
     */
    'token' => env('REVENUECAT_WEBHOOKS_TOKEN', ''),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the RevenueCat event_type.
     *
     * You can find a list of RevenueCat webhook events here:
     * https://www.revenuecat.com/docs/integrations/webhooks/event-types-and-fields
     */
    'jobs' => [
        // 'initial_purchase' => \App\Jobs\RevenueCatWebhooks\InitialPurchase::class,
        // 'cancellation' => \App\Jobs\RevenueCatWebhooks\Cancellation::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Edx\RevenueCatWebhooks\ProcessRevenueCatWebhookJob.
     */
    'model' => \PalauaAndSons\RevenueCatWebhooks\ProcessRevenueCatWebhookJob::class,
];
