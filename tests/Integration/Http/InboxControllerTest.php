<?php

namespace ShipSaasInboxProcess\Tests\Integration\Http;

use Error;
use Illuminate\Http\JsonResponse;
use ShipSaasInboxProcess\Http\Requests\AbstractInboxRequest;
use ShipSaasInboxProcess\InboxProcessSetup;
use ShipSaasInboxProcess\Repositories\InboxMessageRepository;
use ShipSaasInboxProcess\Tests\TestCase;

class InboxControllerTest extends TestCase
{
    public function testRecordNewMessageUseCustomRequestResponse()
    {
        $body = json_decode(
            file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_payment_succeed.json'),
            true
        );

        InboxProcessSetup::addRequest('stripe', new class() extends AbstractInboxRequest {
            public function getInboxExternalId(): string
            {
                return $this->input('id');
            }
        });

        InboxProcessSetup::addResponse('stripe', function () {
            return new JsonResponse('OK MAN');
        });

        $this->json(
            'POST',
            route('inbox.topic', ['topic' => 'stripe']),
            $body
        )->assertOk()->assertSee('OK MAN');

        $this->assertDatabaseHas('inbox_messages', [
            'topic' => 'stripe',
            'external_id' => 'evt_1NWX0RBGIr5C5v4TpncL2sCf',
        ]);
    }

    public function testRecordNewMessageUseDefaultResponse()
    {
        $body = json_decode(
            file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_payment_succeed.json'),
            true
        );

        InboxProcessSetup::addRequest('stripe', new class() extends AbstractInboxRequest {
            public function getInboxExternalId(): string
            {
                return $this->input('id');
            }
        });

        $this->json(
            'POST',
            route('inbox.topic', ['topic' => 'stripe']),
            $body
        )->assertOk()->assertSee('OK');

        $this->assertDatabaseHas('inbox_messages', [
            'topic' => 'stripe',
            'external_id' => 'evt_1NWX0RBGIr5C5v4TpncL2sCf',
        ]);
    }

    public function testRecordDuplicatedMessageReturns409()
    {
        appendInboxMessage('stripe', 'evt_1NWX0RBGIr5C5v4TpncL2sCf', []);

        $body = json_decode(
            file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_payment_succeed.json'),
            true
        );

        InboxProcessSetup::addRequest('stripe', new class() extends AbstractInboxRequest {
            public function getInboxExternalId(): string
            {
                return $this->input('id');
            }
        });

        $this->json(
            'POST',
            route('inbox.topic', ['topic' => 'stripe']),
            $body
        )->assertStatus(409);
    }

    public function testRecordUnknownIssueReturns400()
    {
        appendInboxMessage('stripe', 'evt_1NWX0RBGIr5C5v4TpncL2sCf', []);

        $body = json_decode(
            file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_payment_succeed.json'),
            true
        );

        InboxProcessSetup::addRequest('stripe', new class() extends AbstractInboxRequest {
            public function getInboxExternalId(): string
            {
                return $this->input('id');
            }
        });

        $mockRepo = $this->createMock(InboxMessageRepository::class);
        $mockRepo->expects($this->once())
            ->method('append')
            ->willThrowException(new Error('Heehe'));
        $this->app->offsetSet(InboxMessageRepository::class, $mockRepo);

        $this->json(
            'POST',
            route('inbox.topic', ['topic' => 'stripe']),
            $body
        )->assertStatus(400);
    }

    public function testInboxWillNotRecordIfAuthorizeReturnsFalse()
    {
        $body = json_decode(
            file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_payment_succeed.json'),
            true
        );

        InboxProcessSetup::addRequest('stripe', new class() extends AbstractInboxRequest {
            public function authorize(): bool
            {
                return false;
            }

            public function getInboxExternalId(): string
            {
                return $this->input('id');
            }
        });

        InboxProcessSetup::addResponse('stripe', function () {
            return new JsonResponse('OK MAN');
        });

        $this->json(
            'POST',
            route('inbox.topic', ['topic' => 'stripe']),
            $body
        )->assertForbidden();

        $this->assertDatabaseMissing('inbox_messages', [
            'topic' => 'stripe',
            'external_id' => 'evt_1NWX0RBGIr5C5v4TpncL2sCf',
        ]);
    }

    public function testInboxWillNotRecordIfValidatesFail()
    {
        $body = json_decode(
            file_get_contents(__DIR__ . '/../__fixtures__/stripe_invoice_payment_succeed.json'),
            true
        );

        InboxProcessSetup::addRequest('stripe', new class() extends AbstractInboxRequest {
            public function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [
                    'this-is-a-fake-field-to-obtain-error' => 'required',
                ];
            }

            public function getInboxExternalId(): string
            {
                return $this->input('id');
            }
        });

        InboxProcessSetup::addResponse('stripe', function () {
            return new JsonResponse('OK MAN');
        });

        $this->json(
            'POST',
            route('inbox.topic', ['topic' => 'stripe']),
            $body
        )->assertUnprocessable();

        $this->assertDatabaseMissing('inbox_messages', [
            'topic' => 'stripe',
            'external_id' => 'evt_1NWX0RBGIr5C5v4TpncL2sCf',
        ]);
    }
}
