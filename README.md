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
- Admin analytics and user/task moderation
- Marketing pages for home, about, pricing, and contact
- Stripe Checkout pricing flow with success/cancel pages
- Newsletter signup stub and contact form with spam honeypot protection

## Quick start

1. Copy `.env.example` to `.env`
2. Create the database
3. Import schema and seed
4. Start the PHP server

```bash
cp .env.example .env
mysql -u root -p -e "CREATE DATABASE informal_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p informal_marketplace < database/schema.sql
mysql -u root -p informal_marketplace < database/seed.sql
/Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8000 -t public
```

If your database already exists and you are updating an older local install, run:

```bash
mysql -u root -p informal_marketplace < database/migration_add_payments.sql
```

If you already created the first `payments` table version before the booking-payment update, also run:

```bash
mysql -u root -p informal_marketplace < database/migration_link_payments_to_bookings.sql
```

Open:

- `http://127.0.0.1:8000/index.php?route=home/index`

## Stripe setup

Add these values in `.env` before testing the payment flow:

- `APP_URL` should point to the URL where the app is served locally
- `STRIPE_SECRET_KEY`
- `STRIPE_PUBLISHABLE_KEY` (reserved for future client-side Stripe work)
- `STRIPE_WEBHOOK_SECRET` (reserved for future webhook fulfillment)
- `STRIPE_CURRENCY` defaults to `rwf`

The pricing page starts a Stripe Checkout Session server-side and redirects users to Stripe-hosted checkout. The success page can verify the returned session when Stripe keys are configured.

## Stripe webhooks

Use the webhook route below to confirm payments server-side:

- `http://127.0.0.1:8000/index.php?route=payments/webhook`

For local development with the Stripe CLI, a typical flow is:

```bash
stripe listen --forward-to http://127.0.0.1:8000/index.php?route=payments/webhook
```

Copy the returned signing secret into `STRIPE_WEBHOOK_SECRET`.

Handled events:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `checkout.session.expired`

## Completed task payments

Clients can now pay a completed booking directly from the booking detail page or completed-booking cards.

- Route: `payments/booking-checkout`
- Amount source: accepted bid amount, with task budget as fallback
- Post-payment return: `payments/success`

## Lead capture stubs

- Newsletter signups are appended to `storage/submissions/newsletter.jsonl`
- Contact form submissions are appended to `storage/submissions/contact.jsonl`

These are local launch-phase stubs intended to be replaced later with a real email, CRM, or helpdesk integration.

## Demo accounts

- `admin@example.com / admin12345`
- `client@example.com / password123`
- `tasker@example.com / password123`

## Main routes

- `home/index`
- `marketing/about`
- `marketing/pricing`
- `marketing/contact`
- `payments/webhook`
- `payments/success`
- `payments/cancel`
- `auth/login`
- `auth/register`
- `tasks/index`
- `tasks/browse`
- `bookings/index`
- `admin/dashboard`
- `admin/payments`

## Notes

- Public registration allows `client` and `tasker` only.
- Admin accounts are seeded/manual only.
- All POST forms use CSRF protection.
- All database queries use PDO prepared statements.
