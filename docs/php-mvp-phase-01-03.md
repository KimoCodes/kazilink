# Phase 1-3: PHP Marketplace MVP Scope, Schema, and Structure

## Phase goal

Define a clean, beginner-friendly PHP SSR MVP scope, database schema, and folder structure that we can implement incrementally without frameworks.

## Plan

- Confirm the exact MVP scope for a two-sided marketplace.
- Record the simplest safe assumptions for underspecified behavior.
- Design a MySQL/MariaDB schema using plain SQL.
- Keep the schema aligned with PHP sessions and server-rendered flows.
- Propose an MVC-lite folder structure with clear responsibilities.
- Keep messaging, reviews, and admin moderation minimal but extensible.
- Ensure future milestones can ship as working increments.

## Files to create/change

- `docs/php-mvp-phase-01-03.md`

## Code

No application code is added in this phase. This phase defines the product contract and implementation structure for the PHP build.

## SQL

```sql
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'tasker', 'admin') NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
    last_failed_login_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL,
    city VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    bio TEXT NULL,
    avatar_path VARCHAR(255) NULL,
    skills_summary TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profiles_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_profiles_user_id (user_id)
);

CREATE TABLE tasks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    region VARCHAR(100) NULL,
    country VARCHAR(100) NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    status ENUM('open', 'booked', 'completed', 'cancelled', 'deactivated') NOT NULL DEFAULT 'open',
    scheduled_for DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tasks_client
        FOREIGN KEY (client_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_tasks_category
        FOREIGN KEY (category_id) REFERENCES categories(id)
        ON DELETE RESTRICT,
    KEY idx_tasks_status (status),
    KEY idx_tasks_category (category_id),
    KEY idx_tasks_city (city),
    KEY idx_tasks_client (client_id)
);

CREATE TABLE bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    tasker_id INT UNSIGNED NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    message TEXT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bids_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bids_tasker
        FOREIGN KEY (tasker_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_bids_task_tasker (task_id, tasker_id),
    KEY idx_bids_status (status),
    KEY idx_bids_task (task_id),
    KEY idx_bids_tasker (tasker_id)
);

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    task_id INT UNSIGNED NOT NULL,
    bid_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    tasker_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'completed', 'cancelled') NOT NULL DEFAULT 'active',
    booked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bookings_task
        FOREIGN KEY (task_id) REFERENCES tasks(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bookings_bid
        FOREIGN KEY (bid_id) REFERENCES bids(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bookings_client
        FOREIGN KEY (client_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_bookings_tasker
        FOREIGN KEY (tasker_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY uq_bookings_task_id (task_id),
    UNIQUE KEY uq_bookings_bid_id (bid_id),
    KEY idx_bookings_client (client_id),
    KEY idx_bookings_tasker (tasker_id),
    KEY idx_bookings_status (status)
);

CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_messages_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_messages_booking (booking_id),
    KEY idx_messages_created (created_at)
);

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    reviewee_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewer
        FOREIGN KEY (reviewer_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_reviews_reviewee
        FOREIGN KEY (reviewee_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    UNIQUE KEY uq_reviews_booking_reviewer (booking_id, reviewer_id),
    KEY idx_reviews_reviewee (reviewee_id)
);

CREATE TABLE admin_audit (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT UNSIGNED NOT NULL,
    target_type ENUM('user', 'task') NOT NULL,
    target_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    notes VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_audit_admin
        FOREIGN KEY (admin_user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    KEY idx_admin_audit_target (target_type, target_id)
);
```

## Manual tests

1. Review the schema and confirm every MVP requirement maps to at least one table.
2. Check that one task can only have one booking using `uq_bookings_task_id`.
3. Check that one tasker can only bid once per task using `uq_bids_task_tasker`.
4. Check that reviews can only be left once per booking per reviewer.
5. Check that admin deactivation can be handled by `users.is_active` and `tasks.is_active` plus status fields.

## Security notes

- Passwords must be stored only with `password_hash`, never plaintext.
- All future database access must use PDO prepared statements.
- Add CSRF tokens to every POST form in later phases.
- Escape all rendered output with `htmlspecialchars`.
- Restrict uploaded avatars to safe MIME types and generated filenames in later phases.
- Use login rate limiting based on `failed_login_attempts` and `last_failed_login_at`.

## Next milestone

Phase 4: implement the app foundation with config, database connection, front controller routing, layouts, and auth.

---

## 1) MVP scope

This MVP is a server-rendered PHP marketplace where clients can register, create service tasks, receive bids from taskers, accept one bid to create a booking, message the booked tasker, mark the task complete, and leave a review, while an admin can deactivate users or tasks. The focus is correctness, security, and simple maintainable structure over advanced UI or realtime features.

## Assumptions

- Each user has one role only: `client`, `tasker`, or `admin`.
- Admin accounts are created manually in seed/setup, not through public registration.
- A task can have many bids but only one accepted bid and one booking.
- Messaging is allowed only after a booking exists.
- Only the client marks a booking as completed in the MVP.
- Only the client leaves a review in the MVP, because that is the stated requirement.
- Tasks use fixed-price budgets only; no hourly pricing in MVP.
- Deactivated users and tasks remain in the database for audit/history.

## 2) Database schema design notes

### Table purpose summary

- `users`: login identity, role, activation, and login-throttling fields
- `profiles`: public-facing profile data split from auth data
- `categories`: task classification
- `tasks`: client-created jobs
- `bids`: tasker applications/bids
- `bookings`: accepted bid and task engagement record
- `messages`: chat messages tied to a booking
- `reviews`: post-completion ratings and comments
- `admin_audit`: admin moderation log

### Key business rules enforced by schema

- One profile per user
- One bid per task/tasker pair
- One booking per task
- One booking per accepted bid
- One review per booking/reviewer pair

## 3) Folder structure (MVC-lite)

```text
/public
  index.php
  assets/
    css/
      app.css
    js/
      app.js
    uploads/

/app
  /config
    app.php
    database.php

  /lib
    Auth.php
    Csrf.php
    Session.php
    Validator.php
    View.php
    Helpers.php

  /controllers
    HomeController.php
    AuthController.php
    TaskController.php
    BidController.php
    BookingController.php
    MessageController.php
    ReviewController.php
    AdminController.php

  /models
    User.php
    Profile.php
    Category.php
    Task.php
    Bid.php
    Booking.php
    Message.php
    Review.php
    AdminAudit.php

  /views
    /layouts
      header.php
      footer.php
    /partials
      flash.php
      errors.php
    /home
      index.php
    /auth
      login.php
      register.php
    /tasks
      index.php
      show.php
      create.php
      edit.php
      my_tasks.php
    /bids
      create.php
      list.php
    /bookings
      show.php
      index.php
    /messages
      thread.php
    /reviews
      create.php
    /admin
      dashboard.php
      users.php
      tasks.php

/storage
  /logs
  /uploads

/database
  schema.sql
  seed.sql

/.env.example
```

### Structure notes

- `/public` is the web root and only directly accessible directory.
- `index.php` acts as a front controller using query-parameter routing such as `?route=tasks/index`.
- `/app/config` stores configuration and PDO bootstrap.
- `/app/lib` stores reusable non-framework helpers.
- `/app/controllers` handles request flow and authorization checks.
- `/app/models` handles database queries with PDO.
- `/app/views` contains plain PHP templates.
- `/storage/uploads` is for user uploads outside public access if possible; serve through a controlled script if needed.
- `/database` keeps SQL schema and seed files for quick setup.
