# Demo Checklist

Use this as the short runbook before presenting the marketplace.

## Before Demo

1. Confirm `.env` points to the intended local database.
2. Import `database/schema.sql` and `database/seed.sql` if you need a clean reset.
3. Start the app and open `index.php?route=home/index`.
4. Confirm the following demo accounts work:
   - `batsindakeynesbenoit10101@gmail.com / admin12345`
   - `client@example.com / password123`
   - `tasker@example.com / password123`
5. Confirm seeded categories are visible on task creation.

## Demo Flow

1. Guest view:
   - Show the home page and auth screens.
   - Mention Rwanda-friendly budgets and location defaults.
2. Client flow:
   - Log in as `client@example.com`.
   - Open `Post a Task`.
   - Create or show an open task with a clear RWF budget and Kigali location.
   - Open `My Tasks` and show task management.
3. Tasker flow:
   - Log in as `tasker@example.com`.
   - Browse tasks.
   - Open a task and submit a bid.
   - Show the tasker dashboard and accepted work visibility.
4. Booking and messaging:
   - Return to the client account.
   - Accept the bid to create a booking.
   - Open the booking and then open messages.
   - Send one message from the client and one from the tasker.
5. Completion and trust:
   - Mark the booking completed as the client.
   - Leave a review.
   - Open the tasker profile and show review proof.
6. Admin moderation:
   - Log in as `admin@example.com`.
   - Show dashboard counts.
   - Show user/task moderation screens.
   - Explain activation/deactivation and audit logging.

## Final Sanity Checks

1. Budgets display as `RWF`.
2. Dates render in the Kigali timezone format used by the app.
3. Navigation changes correctly by role.
4. No raw HTML or script tags render in tasks, messages, bids, or reviews.
5. Mutating actions only submit through protected POST forms.
