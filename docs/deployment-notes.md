# Deployment Notes

## Phase goal

Document a simple deployment and local-demo path for the PHP SSR marketplace MVP.

## Stack

- PHP 8.2+
- Apache or PHP built-in server
- MySQL or MariaDB
- Plain PHP templates, CSS, and minimal JavaScript

## Local setup

1. Copy `.env.example` to `.env`.
2. Update database credentials in `.env`.
3. Create the database.
4. Import `database/schema.sql`.
5. Import `database/seed.sql`.
6. Start the app with the PHP built-in server or Apache/XAMPP.

### Local commands

```bash
cp .env.example .env
mysql -u root -p -e "CREATE DATABASE informal_marketplace CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p informal_marketplace < database/schema.sql
mysql -u root -p informal_marketplace < database/seed.sql
/Applications/XAMPP/xamppfiles/bin/php -S 127.0.0.1:8000 -t public
```

## Demo accounts

- `batsindakeynesbenoit10101@gmail.com / admin12345`
- `client@example.com / password123`
- `tasker@example.com / password123`

## Docker option

This repo includes a simple `Dockerfile` and `docker-compose.yml`.

### Start containers

```bash
docker compose up --build -d
```

### Import schema and seed

```bash
docker compose exec db mariadb -uinformal_user -pinformal_pass informal_marketplace < /var/lib/mysql-files/schema.sql
```

The simpler option is to copy/import from the host:

```bash
docker compose exec -T db mariadb -uinformal_user -pinformal_pass informal_marketplace < database/schema.sql
docker compose exec -T db mariadb -uinformal_user -pinformal_pass informal_marketplace < database/seed.sql
```

### Access app

- App: `http://127.0.0.1:8080`
- DB port on host: `3307`

## Migration approach

This MVP does not use a migration framework. Use this simple workflow:

1. Update `database/schema.sql`.
2. Add a new timestamped SQL file in `database/migrations/` if you want tracked incremental changes later.
3. Apply schema updates manually in staging/production after backup.
4. Re-run smoke tests after each schema change.

For this MVP, `schema.sql` remains the source of truth.

## Hosting options

### Small VPS

- Ubuntu VM
- Nginx or Apache
- PHP-FPM 8.2+
- MariaDB/MySQL on same host
- Good fit for low-cost MVP hosting

### Shared hosting with PHP/MySQL

- Works if it supports PHP 8.x and PDO MySQL
- Keep `/public` as the web root if the host allows it
- If not, copy public assets carefully and restrict direct access to non-public app files

### Docker-capable platform

- Small VM with Docker
- Render-style Docker host
- Railway/Fly.io style setup if persistent database is configured separately

## Production checklist

- Set strong database credentials
- Set `APP_ENV=production`
- Keep `APP_DEBUG=false`
- Serve over HTTPS
- Use a strong session cookie policy
- Back up the database before schema changes
- Restrict write permissions on uploads/logs
- Rotate seeded demo users out of production

## Smoke test checklist

1. Home page loads.
2. Register works for `client` and `tasker`.
3. Login/logout works.
4. Client can create a task.
5. Tasker can browse and bid.
6. Client can accept a bid and create a booking.
7. Client and tasker can message within a booking.
8. Client can complete the booking and leave a review.
9. Admin can deactivate a user and a task.
