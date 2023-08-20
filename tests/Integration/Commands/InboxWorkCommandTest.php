<?php

namespace ShipSaasInboxProcess\Tests\Integration\Commands;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use ShipSaasInboxProcess\InboxProcessSetup;
use ShipSaasInboxProcess\Tests\TestCase;

class InboxWorkCommandTest extends TestCase
{
    public function testCommandPullsTheOrderedMsgAndProcessThem()
    {
        Event::fake();

        // 1. register handlers
        InboxProcessSetup::addProcessor('stripe', function (array $payload) {
            if ($payload['type'] !== 'invoice.payment_succeeded') {
                return;
            }

            $invoiceId = data_get($payload, 'data.object.id');
            Event::dispatch(new InvoicePaymentSucceedEvent($invoiceId));
        });

        InboxProcessSetup::addProcessor('stripe', StripeInvoicePaidHandler::class);
        InboxProcessSetup::addProcessor('stripe', StripCustomerUpdatedHandler::class);

        // 2. append msgs
        appendInboxMessage(
            'stripe',
            'evt_1NWX0RBGIr5C5v4TpncL2sCf',
            json_decode(file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_payment_succeed.json'), true)
        );
        appendInboxMessage(
            'stripe',
            'evt_1NWUFiBGIr5C5v4TptQhGyW3',
            json_decode(file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_paid.json'), true)
        );
        appendInboxMessage(
            'stripe',
            'evt_1Nh2fp2eZvKYlo2CzbNockEM',
            json_decode(file_get_contents(__DIR__ . '/../__fixtures__/stripe_customer_updated.json'), true)
        );

        // 3. run
        $code = Artisan::call('inbox:work stripe --stop-on-empty');
        $result = Artisan::output();

        // 4. validate
        $this->assertSame(0, $code);
        $this->assertStringContainsString('Processed 3 inbox messages', $result);
        $this->assertStringContainsString('[Info] No message found. Stopping...', $result);

        Event::assertDispatched(
            InvoicePaymentSucceedEvent::class,
            fn (InvoicePaymentSucceedEvent $event) => $event->invoiceId === 'in_1NVRYnBGIr5C5v4T6gwKxkt9'
        );
        Event::assertDispatched(
            InvoicePaidEvent::class,
            fn (InvoicePaidEvent $event) => $event->invoiceId === 'in_1NVOkaBGIr5C5v4TZgwGUo0Q'
        );
        Event::assertDispatched(
            CustomerUpdatedEvent::class,
            fn (CustomerUpdatedEvent $event) => $event->customerId === 'cus_9s6XKzkNRiz8i3'
        );
    }

    public function testCommandDoNothingWhenThereIsNoMessage()
    {
        $code = Artisan::call('inbox:work test --stop-on-empty');
        $result = Artisan::output();

        $this->assertSame(0, $code);

        $this->assertStringContainsString('Locked topic: test', $result);
        $this->assertStringContainsString('[Info] No message found. Stopping...', $result);
    }

    public function testCommandStopsWhenUnableToAcquireLock()
    {
        DB::table('running_inboxes')
            ->insert(['topic' => 'seth']);

        $code = Artisan::call('inbox:work seth --stop-on-empty');
        $result = Artisan::output();

        $this->assertSame(1, $code);

        $this->assertStringContainsString('Unable to lock the "seth" topic', $result);
    }

    public function testCommandShouldUnlockTopicAfterStopped()
    {
        $code = Artisan::call('inbox:work testlock --stop-on-empty');

        $this->assertSame(0, $code);

        /**
         * A bit tricky because we can't hit the SIGINT or SIGQUIT
         * So here we'll terminate, but the closing has been registered to the application's lifecycle
         * => it will unlock the topic
         */
        $this->app->terminate();

        $this->assertDatabaseMissing('running_inboxes', [
            'topic' => 'testlock',
        ]);
    }
}

class InvoicePaymentSucceedEvent
{
    public function __construct(public string $invoiceId)
    {
    }
}

class InvoicePaidEvent
{
    public function __construct(public string $invoiceId)
    {
    }
}

class StripeInvoicePaidHandler
{
    public function handle(array $payload): void
    {
        if ($payload['type'] !== 'invoice.paid') {
            return;
        }

        $invoiceId = data_get($payload, 'data.object.id');
        Event::dispatch(new InvoicePaidEvent($invoiceId));
    }
}

class CustomerUpdatedEvent
{
    public function __construct(public string $customerId)
    {
    }
}

class StripCustomerUpdatedHandler
{
    public function __invoke(array $payload): void
    {
        if ($payload['type'] !== 'customer.updated') {
            return;
        }

        $cusId = data_get($payload, 'data.object.id');
        Event::dispatch(new CustomerUpdatedEvent($cusId));
    }
}
