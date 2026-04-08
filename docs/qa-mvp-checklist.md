# QA MVP Checklist

## Purpose

Use this checklist to manually verify the PHP marketplace MVP end to end after setup, before demoing, and after any code changes.
Record results in `docs/qa-results-YYYY-MM-DD.md` with pass/fail + screenshots/errors.

## Environment Setup

1. Copy `.env.example` to `.env`.
2. Create the database.
3. Import `database/schema.sql`.
4. Import `database/seed.sql`.
5. Start the app with the PHP built-in server or Docker.
6. Open `http://127.0.0.1:8000/index.php?route=home/index` or the Docker app URL.

## Setup Sanity

1. Confirm PHP version is `8.x`.
2. Confirm required PHP extensions are enabled: `pdo_mysql`, `mbstring`.
3. Confirm `.env` is actually being loaded by temporarily using wrong DB credentials and verifying the app fails cleanly.
4. Confirm seeded categories exist in the database.
5. Confirm only categories with `is_active = 1` appear in task creation.

## Demo Accounts

- `admin@example.com / admin12345`
- `client@example.com / password123`
- `tasker@example.com / password123`

## Smoke Check

1. Home page loads without PHP errors.
2. Header and footer render correctly.
3. Navigation changes based on login state.
4. Unknown route shows a safe not-found page.

## Auth

1. Register a new `client` account successfully.
2. Register a new `tasker` account successfully.
3. Confirm public registration does not allow `admin`.
4. Submit empty/invalid register form and confirm validation errors appear.
5. Log in with valid credentials and confirm success flash appears.
6. Confirm session ID is regenerated after login.
7. Log out and confirm success flash appears.
8. Confirm logout clears the session completely and protected pages do not remain usable after browser back navigation.
9. Try invalid login credentials and confirm login fails safely.
10. Trigger repeated failed logins and confirm lockout behavior appears.
11. Deactivate a logged-in user from admin in another session, then confirm the next protected request redirects to login and destroys the session.

## Client Task CRUD

1. Log in as a client.
2. Open `Post Task`.
3. Submit an invalid task form and confirm validation errors appear.
4. Create a valid task and confirm redirect to task detail.
5. Confirm the task appears in `My Tasks`.
6. Edit the task and confirm changes persist.
7. Cancel an open task and confirm status changes to `cancelled`.
8. Confirm cancelled tasks can no longer be edited.
9. Confirm booked tasks can no longer be edited.
10. Confirm completed tasks can no longer be edited.
11. Confirm another client cannot view or edit your task management pages.

## Task Discovery

1. Log in as a tasker.
2. Open `Browse Tasks`.
3. Confirm open active tasks are listed.
4. Search by keyword and confirm results narrow correctly.
5. Filter by category and confirm results update.
6. Filter by city and confirm results update.
7. Filter by min/max budget and confirm results update.
8. Open a task detail page from browse and confirm data is correct.
9. Confirm cancelled or inactive tasks do not appear in discovery.

## Bidding and Booking

1. As a tasker, open an open task and submit a valid bid.
2. Confirm success flash appears.
3. Confirm the same tasker cannot submit a second bid on that task.
4. Confirm a tasker cannot bid on their own task.
5. Confirm a tasker cannot bid on tasks that are not `open`.
6. Confirm a tasker cannot bid on tasks where `is_active = 0`.
7. Log in as the client who owns the task.
8. Open the task detail page and confirm the bid is visible.
9. Accept the bid and confirm a booking is created.
10. Confirm task status changes from `open` to `booked`.
11. Confirm accepted bid is marked `accepted`.
12. Confirm other pending bids on that task become `rejected`.
13. Attempt to accept a second bid for the same task and confirm it fails safely without creating another booking.
14. Confirm the booked task no longer appears in tasker browse results.
15. Cancel or deactivate the task, if supported in the UI/admin, and confirm the client cannot accept a bid afterward.
16. If `cancel booking` is supported, confirm cancelling a booking updates both booking status and linked task status consistently.

## Messaging

1. Open a booking as the client and click `Open Messages`.
2. Send a message and confirm it appears in the thread.
3. Open the same booking as the tasker and confirm the client message is visible.
4. Send a reply as the tasker and confirm it appears.
5. Submit an empty message and confirm validation blocks it.
6. Confirm message text is preserved after validation failure.
7. Confirm a non-participant cannot open the booking message thread.
8. Send `<script>alert(1)</script>` and confirm it renders harmlessly as escaped text.
9. Refresh the thread and confirm message ordering is stable from oldest to newest with no duplicate entries.

## Completion and Reviews

1. As the client, open an active booking.
2. Confirm `Mark Completed` is visible.
3. Mark the booking completed.
4. Confirm booking status changes to `completed`.
5. Confirm linked task status changes to `completed`.
6. Confirm `Leave Review` becomes visible after completion.
7. Submit an invalid review and confirm validation errors appear.
8. Confirm review form input is preserved on validation failure.
9. Submit a valid review and confirm it appears on the booking page.
10. Confirm the client cannot submit a second review for the same booking.
11. Confirm the tasker can view the posted review.
12. Tamper with the submitted rating and confirm server-side validation enforces bounds `1-5`.
13. Confirm a review can only be created when the booking is `completed`.
14. Confirm only the client on that booking can submit the review.

## Admin Moderation

1. Log in as admin.
2. Open the admin dashboard and confirm summary counts render.
3. Open `Manage Users` and confirm users are listed.
4. Deactivate a client or tasker account.
5. Confirm the deactivated user cannot log in.
6. If the user was already logged in elsewhere, confirm their next protected action fails because the session is no longer valid.
7. Reactivate the same user and confirm login works again.
8. Open `Manage Tasks` and confirm tasks are listed.
9. Deactivate an open task and confirm it disappears from tasker discovery.
10. Reactivate the task and confirm it becomes discoverable again if it is still truly open.
11. Deactivate a completed or booked task, then reactivate it, and confirm its original lifecycle state is preserved.
12. Confirm deactivating a task does not change its business status (`open`, `booked`, `completed`) unless that is an intentional product rule.
13. Confirm a task with `is_active = 0` is hidden from discovery even if its status is still `open`.
14. Confirm recent admin actions appear on the dashboard.
15. Confirm admin audit log records `admin_id`, target type/id, action, and `created_at`.
16. Confirm admin cannot deactivate their own account.

## Access Control

1. Confirm a guest cannot access client, tasker, booking, messaging, review, or admin pages.
2. Confirm a client cannot access tasker browse-only flows as a tasker.
3. Confirm a tasker cannot access client-only task management flows.
4. Confirm a non-admin cannot access admin routes.
5. Confirm admin can view users, tasks, and bookings.

## Security Checks

1. Remove a CSRF token from a POST form and confirm the request is rejected.
2. Tamper with a POST CSRF token and confirm the request is rejected.
3. Confirm output is escaped on pages that show user-entered content such as tasks, bids, messages, and reviews.
4. Confirm prepared statements are used for all database writes and filtered reads.
5. Confirm inactive users cannot continue acting after admin deactivation.

## Rwanda-Specific Checks

1. Confirm budgets display as `RWF` consistently, without decimals unless the product intentionally supports them.
2. Enter `10000` and confirm the UI formats it consistently as `RWF 10,000` or the chosen equivalent format.
3. If profiles use phone numbers, confirm valid Rwanda numbers such as `+2507...` are accepted.
4. Confirm obviously invalid phone values are rejected.
5. Confirm Rwanda location values such as Kigali districts work correctly in filters regardless of case or extra whitespace.
6. If the UI shows dates or timestamps, confirm they render in `Africa/Kigali (UTC+2)` rather than UTC.

## Regression Focus Areas

- Auth and session persistence
- Task status changes: `open -> booked -> completed` and `open -> cancelled`
- Admin task activation/deactivation without corrupting lifecycle status
- One bid per tasker per task
- One booking per task
- One review per booking per reviewer
- Messaging restricted to booking participants

## Sign-off

- QA date:
- Environment tested:
- Tested by:
- Issues found: ___ (link to bug list)
- P0 blockers: yes / no
- Ready for demo: yes / no
