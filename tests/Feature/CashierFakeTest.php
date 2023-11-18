<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\CashierFake;
use Laravel\Paddle\Exceptions\PaddleException;
use Laravel\Paddle\Transaction;

class CashierFakeTest extends FeatureTestCase
{
    public function test_a_user_may_overwrite_its_api_responses()
    {
        Cashier::fake([
            $endpoint = 'products' => $expected = [
                'data' => [['id' => 1, 'name' => 'Test Product']],
                'meta' => ['request_id' => 'xxx'],
            ],
        ]);

        $this->assertEquals(
            $expected,
            Http::get(CashierFake::getFormattedApiUrl($endpoint))->json()
        );
    }

    public function test_a_user_may_use_the_response_method_to_mock_an_endpoint()
    {
        Cashier::fake()->response(
            $endpoint = 'products',
            $expected = [['id' => 1, 'name' => 'Test Product']],
        );

        $this->assertEquals(
            ['data' => $expected],
            Http::get(CashierFake::getFormattedApiUrl($endpoint))->json()
        );
    }

    public function test_a_user_may_use_the_error_method_to_error_an_endpoint()
    {
        $this->expectException(PaddleException::class);
        Cashier::fake()->error('transactions/txn_123456789');
        $billable = $this->createBillable();

        $transaction = new Transaction([
            'id' => 12345,
            'paddle_id' => 'txn_123456789',
            'billable_id' => $billable->id,
            'billable_type' => get_class($billable),
            'status' => 'completed',
        ]);

        $transaction->refund('Incorrect order');
    }

    public function test_a_user_may_append_additional_events_to_mock()
    {
        Cashier::fake([], [CapturedTestEvent::class]);

        event(new CapturedTestEvent);
        event(new UncapturedTestEvent);

        Event::assertDispatched(CapturedTestEvent::class);
        Event::assertNotDispatched(UncapturedTestEvent::class);
    }
}

class CapturedTestEvent
{
}

class UncapturedTestEvent
{
}
