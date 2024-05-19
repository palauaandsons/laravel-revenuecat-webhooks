<?php

namespace PalauaAndSons\RevenueCatWebhooks\Tests;

use PalauaAndSons\RevenueCatWebhooks\ProcessRevenueCatWebhookJob;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Models\WebhookCall;

class RevenueCatWebhookCallTest extends TestCase
{
    /** @var \PalauaAndSons\RevenueCatWebhooks\ProcessRevenueCatWebhookJob */
    public $processRevenueCatWebhookJob;

    /** @var \Spatie\WebhookClient\Models\WebhookCall */
    public $webhookCall;

    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        config(['revenuecat-webhooks.jobs' => ['my_type' => DummyJob::class]]);

        $this->webhookCall = WebhookCall::create([
            'name'    => 'revenuecat',
            'payload' => ['event_type' => 'my_type', 'name' => 'value'],
            'url'     => 'https://example.com/revenuecat-webhooks',
        ]);

        $this->processRevenueCatWebhookJob = new ProcessRevenueCatWebhookJob($this->webhookCall);
    }

    /** @test */
    public function it_will_fire_off_the_configured_job()
    {
        $this->processRevenueCatWebhookJob->handle();

        $this->assertEquals($this->webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_will_not_dispatch_a_job_for_another_type()
    {
        config(['revenuecat-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processRevenueCatWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_not_dispatch_jobs_when_no_jobs_are_configured()
    {
        config(['revenuecat-webhooks.jobs' => []]);

        $this->processRevenueCatWebhookJob->handle();

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_will_dispatch_events_even_when_no_corresponding_job_is_configured()
    {
        config(['revenuecat-webhooks.jobs' => ['another_type' => DummyJob::class]]);

        $this->processRevenueCatWebhookJob->handle();

        $webhookCall = $this->webhookCall;

        Event::assertDispatched("revenuecat-webhooks::{$webhookCall->payload['event_type']}", function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertNull(cache('dummyjob'));
    }
}
