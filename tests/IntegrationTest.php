<?php

namespace PalauaAndSons\RevenueCatWebhooks\Tests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
use Spatie\WebhookClient\Models\WebhookCall;

class IntegrationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Event::fake();

        Route::revenueCatWebhooks('revenuecat-webhooks');
        Route::revenueCatWebhooks('revenuecat-webhooks/{configKey}');

        config(['revenuecat-webhooks.jobs' => ['my_type' => DummyJob::class]]);
        cache()->clear();
    }

    /** @test */
    public function it_can_handle_a_valid_request()
    {
        $this->withoutExceptionHandling();

        $payload = [
            'event_type' => 'my_type',
            'key'        => 'value',
        ];

        $this->postJson('revenuecat-webhooks', $payload)
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my_type', $webhookCall->payload['event_type']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('revenuecat-webhooks::my_type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function a_request_with_an_invalid_payload_will_be_logged_but_events_and_jobs_will_not_be_dispatched()
    {
        $payload = ['invalid_payload'];

        $this->postJson('revenuecat-webhooks', $payload)
            ->assertStatus(400);

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertFalse(isset($webhookCall->payload['event_type']));
        $this->assertEquals(['invalid_payload'], $webhookCall->payload);

        $this->assertEquals('Webhook call id `1` did not contain a type. Valid RevenueCat webhook calls should always contain a type.', $webhookCall->exception['message']);

        Event::assertNotDispatched('revenuecat-webhooks::my_type');

        $this->assertNull(cache('dummyjob'));
    }

    /** @test */
    public function it_can_handle_a_valid_request_with_authorization_header()
    {
        config(['revenuecat-webhooks.token' => 'ABC']);

        $this->withoutExceptionHandling();

        $payload = [
            'event_type' => 'my_type',
            'key'        => 'value',
        ];

        $this->postJson('revenuecat-webhooks', $payload, ['Authorization' => 'Bearer ABC'])
            ->assertSuccessful();

        $this->assertCount(1, WebhookCall::get());

        $webhookCall = WebhookCall::first();

        $this->assertEquals('my_type', $webhookCall->payload['event_type']);
        $this->assertEquals($payload, $webhookCall->payload);
        $this->assertNull($webhookCall->exception);

        Event::assertDispatched('revenuecat-webhooks::my_type', function ($event, $eventPayload) use ($webhookCall) {
            $this->assertInstanceOf(WebhookCall::class, $eventPayload);
            $this->assertEquals($webhookCall->id, $eventPayload->id);

            return true;
        });

        $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
    }

    /** @test */
    public function it_rejects_a_request_with_invalid_authorization_header()
    {
        config(['revenuecat-webhooks.token' => 'ABC']);

        $this->withoutExceptionHandling();
        $this->expectException(InvalidWebhookSignature::class);

        $payload = [
            'event_type' => 'my_type',
            'key'        => 'value',
        ];

        $this->postJson('revenuecat-webhooks', $payload, ['Authorization' => 'Invalid Bearer Token'])
            ->assertStatus(500);

        $this->assertCount(0, WebhookCall::get());

        Event::assertNotDispatched('revenuecat-webhooks::my_type');

        $this->assertNull(cache('dummyjob'));
    }
}
