# Informal Marketplace MVP

Simple Taskrabbit-style marketplace MVP built with:

- PHP 8.x
- MySQL or MariaDB
- HTML, CSS, and minimal vanilla JavaScript
- Server-rendered PHP templates

## Features

- Register/login/logout with PHP sessions
- Client task CRUD
- Tasker browse/search/filter for open tasks
- One bid per tasker per task
- Client bid acceptance and booking creation
- Booking-scoped messaging
- Client completion and review flow
- Hiring Agreement generation on hire confirmation
- Dual agreement acceptance with audit logging
- Public agreement verification
- Dispute capture for non-payment, no-shows, access issues, and scope changes
- Admin analytics and user/task moderation
- Marketing pages for home, about, pricing, and contact
- Newsletter signup stub and contact form with spam honeypot protection
- MTN MoMo subscription billing with trials, promos, and admin-managed plans

## Quick start

1. Create the database
2. Import schema and seed
3. Apply the agreement migration if you are updating an older install
4. Apply the subscription migration if you are enabling billing
5. Start the PHP server

```bash
mysql -u root -p -e "CREATE DATABASE informal_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p informal_marketplace < database/schema.sql
mysql -u root -p informal_marketplace < database/seed.sql
mysql -u root -p informal_marketplace < database/migration_add_hiring_agreements.sql
mysql -u root -p informal_marketplace < database/migration_add_subscriptions_momo.sql
/Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8000 -t public
```

Open:

- `http://127.0.0.1:8000/index.php?route=home/index`

## Payments model

- The platform does not process client-to-tasker card, bank, wallet, or cash payments.
- Clients and taskers arrange payment offline.
- Platform subscriptions are billed separately through MTN MoMo.
- The platform's value is matching, hiring records, agreement verification, and dispute evidence.
- The legacy `payments` table can remain in the database as an unused historical table, but no runtime flow writes to it.

## Subscription operations

- Cron maintenance script: `/Applications/XAMPP/xamppfiles/bin/php /Applications/XAMPP/xamppfiles/htdocs/informal/scripts/subscription_maintenance.php`
- Suggested cron: `*/15 * * * * /Applications/XAMPP/xamppfiles/bin/php /Applications/XAMPP/xamppfiles/htdocs/informal/scripts/subscription_maintenance.php`
- Reminder stubs are appended to `storage/submissions/subscription_reminders.jsonl`
- Grace period is managed in Admin via `admin/settings` and stored in `app_settings.subscription_grace_days`
- Optional production callback IP allowlist env var: `MOMO_CALLBACK_ALLOWLIST`

## Integration tests

- Run: `/Applications/XAMPP/xamppfiles/bin/php tests/integration/subscription_flows.php`
- These tests run inside a DB transaction on the current configured database and roll back when complete.

## Hiring Agreement flow

1. Client accepts a bid.
2. Booking is created.
3. A draft hiring agreement is generated automatically from the task and booking data.
4. Client and tasker review and accept the agreement.
5. Once both accept, the agreement can be printed to PDF and verified publicly by UID.
6. If something goes wrong, either party can open a dispute attached to the agreement.

## Lead capture stubs

- Newsletter signups are appended to `storage/submissions/newsletter.jsonl`
- Contact form submissions are appended to `storage/submissions/contact.jsonl`

These are local launch-phase stubs intended to be replaced later with a real email, CRM, or helpdesk integration.

## Demo accounts

- `batsindakeynesbenoit10101@gmail.com / admin12345`
- `client@example.com / password123`
- `tasker@example.com / password123`

## Main routes

- `home/index`
- `marketing/about`
- `marketing/pricing`
- `marketing/contact`
- `auth/login`
- `auth/register`
- `tasks/index`
- `tasks/browse`
- `bookings/index`
- `agreements/review`
- `agreements/verify`
- `disputes/show`
- `admin/dashboard`

## Notes

- Public registration allows `client` and `tasker` only.
- Admin accounts are seeded/manual only.
- All POST forms use CSRF protection.
- All database queries use PDO prepared statements.
