# ShipSaaS - Laravel Inbox Process

The inbox pattern is a popular design pattern that ensures:

- High availability ✅
- Guaranteed webhook deliverance, no msg lost ✅
- Guaranteed **exactly-once/unique** webhook requests ✅
- Execute webhook requests **in ORDER** ✅
- Trace all prev requests in DB ✅

Laravel Inbox Process (powered by ShipSaaS) takes care of everything and 
helps you to roll out the inbox process in no time 😎.

## Supports
- Laravel 10+
- PHP 8.2+
- MySQL 8/Postgres 13+

## Architecture

![ShipSaaS - Laravel Inbox Process](./.github/arch.png)

## Installation

Install the library:

```bash
composer require shipsaas/laravel-inbox-process
```

Export and run the migration:

```bash
php artisan vendor:publish --tag=laravel-inbox-process
php artisan migrate
```

## Usage

## Glossary

### Topic
`topic`: the term topic is vague and really depend on your use cases.

For instance, you can use a single topic (using the 3rd service name) for everything.

Or you can use multiple topics as well if you are confidence that there won't be any conflicts.

Example:

- `inbox/stripe`
- `inbox/currency-cloud`
- ...

### Unique External Id

In order to ensure the msgs are unique, you must determine the unique external id.

Something like: the unique ID of the 3rd-party entity. 

If the entity changes the stage frequently, you can add a prefix.

I had a real use case back then. The Payment entity status would be updated frequently, so that I introduced:

- `payment-received-{paymentUuid}`
- `payment-handling-{paymentUuid}`
- `payment-sending-{paymentUuid}`
- `payment-sent-{paymentUuid}`

## Receive Webhook Requests

### Use the default route

Laravel Inbox Process ships the `/inbox/{topic}` route for you, so basically you can use this route
to register with your 3rd-party service.

To add a custom Request for your specific topic (eg: `stripe`), first you need to create a
FormRequest and extend the `AbstractInboxProcess`. 

Additionally, if you want to transform a bit of the payload, you can override the `AbstractInboxProcess@getInboxPayload` method.

```php
class StripeInvoiceCreatedWebhookRequest extends AbstractInboxRequest
{
    public function getInboxExternalId(): string | null
    {
        return 'stripe_invoice_created-' . $this->input('id');
    }
}
```

Then register it to Inbox Process:

```php
// AppServiceProvider.php
use ShipSaasInboxProcess\InboxProcessSetup;

public function boot(): void
{
    //...
    InboxProcessSetup::addRequest('stripe', new StripeInvoiceCreatedWebhookRequest());
}
```

Lastly, if you wish to have a specific response for your services, you can use `addResponse`

```php
// AppServiceProvider.php
use ShipSaasInboxProcess\InboxProcessSetup;

// AppServiceProvider@boot
InboxProcessSetup::addResponse('stripe', function (StripeInvoiceCreatedWebhookRequest $request) {
    // return a response object here
    
    return new JsonResponse();
});
```

### Use your own route

Laravel Inbox Process ships the `appendInboxMsg($topic, $uniqueId, $payload)` function for manual usage.

Simply invoke the function, the msg will be appended into the table and ready to be resolved
under background work.

```php
public function webhook(WebhookRequest $request): JsonResponse
{
    appendInboxMsg(
        'currency-cloud',
        $request->getId(),
        $request->all()
    );
    
    return new JsonResponse();
}
```

## Create Inbox Processor / Handler

Simply bind your own Handler class using `addProcessor` method. 
If you add more than 1 processor, it will loop thru all the processors to process.

```php
use ShipSaasInboxProcess\InboxProcessSetup;

// AppServiceProvider@boot
InboxProcessSetup::addProcessor(
    'stripe', 
    StripeInvoiceWebhookHandler::class
);
InboxProcessSetup::addProcessor(
    'stripe', 
    StripePaymentWebhookHandler::class
);
```

The Inbox Process will either use the `handle` method or `__invoke`. 

Your processor class will be resolved using the super cool dependency injection powered by Laravel.

```php
class StripeInvoiceWebhookHandler 
{
    public function __construct(
        private StripeClient $stripeClient
    ) {}

    public function handle(array $payload): void
    {
        if ($payload['type'] !== 'invoice') {
            return;
        }
        
        // resolve this payload
    }
}
```

## Run The Inbox Process

```bash
php artisan inbox:work {topic} --limit=100
```

Same as Laravel Queue Worker, you can set up the `supervisor` to manage the process.

## Notes

### Use Inbox Process when
- You care about data integrity.
- Process your webhook requests **uniquely/exactly-once**.
- Process your webhook requests **in ORDER**.
- Your 3rd-party service doesn't want specific responses, simply `200` OK is enough
  - eg: you have to run some biz logic then return the response.

### Avoid using Inbox Process when
- You don't care about the ordering of the webhook requests, can be YOLO-ordering 😌.
- You have to do some biz logic then **specific responses** to your 3rd-party service.
  - For this, you might need to implement your own Idempotency layer.
- You want high throughput

## Process until success
If there is a message that fails to process (throwing an Exception). Inbox Process will stop and
always retry until success.

This helps us to ensure the data integrity, especially the mission-critical applications.

Remember to add a proper alert (Sentry, DataDog,...) when the Inbox Process is stopped working, so you can
review the issue, deploy the fix and re-run the inbox process again.

## Why Inbox Process over Queue?

Queue is super easy to use, and built-in in Laravel. But there are several problems:

- The msg ordering won't be 100% guaranteed, even though we can run the worker in 1 process only.
- Using MQ (eg: SQS) won't be 100% guaranteed exactly-once.
- Once your job is resolved, the job will be purged while Inbox would still keep them.
  - Helpful to tracing PROD issues, incident tracking,...
  - Inbox provides high visibility.
- (Additional) Tightly-coupled to Laravel while Inbox can be used by any lang or framework in general.
  - With Inbox, Laravel can stand alone to collect msgs and defer the execution to other microservices.

## Why relational databases over others?

We chose the RDMS because:

- It is like the default knowledge nowadays, everybody can get into it quickly.
- Built-in & Supported by Laravel & PHP 100%, stable & battle-tested.
- Perform ordering & locking are simple and efficient.

## Best practices

### Secondary database connection for inbox messages

Instead of bulking everything under 1 database, consider using another database for inbox.

PROs:
- Inbox is independent & isolated.
- High availability for inbox
  - Just in case you have an incident from the application, Inbox would still collect webhook requests 
    - => recovery is much faster and less "manual" work.

### Dedicated instance for inbox

To achieve the "High Availability", consider running the Inbox Process (at least the HTTP) in a dedicated server/docker instance.

This would isolate the Inbox Process from the Application process.

## Testing

Run `composer test` 😆

Available Tests:

- Unit Testing
- Integration Testing against MySQL and `inbox:work` command

## Contributors
- Seth Phat

## Contributions & Support the Project

Feel free to submit any PR, please follow PSR-1/PSR-12 coding conventions and testing is a must.

If this package is helpful, please give it a ⭐️⭐️⭐️. Thank you!

## License
MIT License
