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

Export config & migration files and then run the migration:

```bash
php artisan vendor:publish --tag=laravel-inbox-process
php artisan migrate
```

## Documentation & Usage

Checkout: [inbox.shipsaas.tech](https://inbox.shipsaas.tech)

## Testing

Run `composer test` 😆

Available Tests:

- Unit Testing
  - Integration Testing against MySQL & PostgreSQL for the `inbox:work` command

## Contributors
- Seth Phat

## Contributions & Support the Project

Feel free to submit any PR, please follow PSR-1/PSR-12 coding conventions and testing is a must.

If this package is helpful, please give it a ⭐️⭐️⭐️. Thank you!

## License
MIT License
