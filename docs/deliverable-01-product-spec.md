# Deliverable 1: Product Spec, Data Model, and API Route List

## Assumptions

- The MVP supports one account per email, and each user has exactly one active role: `CLIENT`, `TASKER`, or `ADMIN`.
- `ADMIN` users are created manually through seed data or direct database updates, not through public signup.
- A task can be booked with only one tasker, and only one accepted application exists per task.
- Messaging is allowed only after a booking exists between the client and the selected tasker.
- Payments are handled offline between clients and taskers; the platform records the hire through a hiring agreement and dispute log instead of processing funds.
- Location is stored as plain text for MVP (`city`, `region`, `country`) without maps, geocoding, or distance ranking.
- Search is simple keyword/category/location/status filtering with PostgreSQL text matching; no recommendations or ranking engine.
- Task budgets are fixed-price only for MVP.

## Product Goal

Build a two-sided marketplace where clients post service tasks and taskers discover them, apply with bids, get booked, message the client, complete the work, and exchange reviews. Admins can moderate users and tasks and view basic counts.

## User Roles

### Client

- Register and sign in
- Create and manage own profile
- Post, edit, view, cancel, and complete own tasks
- Review incoming applications for own open tasks
- Book one tasker for a task
- Message the booked tasker
- Leave a review after completion

### Tasker

- Register and sign in
- Create and manage own profile
- Discover open tasks
- Filter/search tasks
- Submit one application per task
- Withdraw own application before booking
- View booking status
- Message the client after booking
- Leave a review after completion

### Admin

- View all users, tasks, applications, bookings, reviews, agreements, and disputes
- Disable or re-enable users
- Disable or re-enable tasks
- View basic analytics counts and totals

## Core User Stories

### Authentication and Profiles

- As a new user, I can sign up as a client or tasker.
- As a returning user, I can sign in securely.
- As a user, I can edit my name, phone, avatar URL, bio, and location.
- As a tasker, I can add service categories and a short skills summary.

### Task Posting

- As a client, I can create a task with title, description, category, location, budget, and scheduled date.
- As a client, I can edit or cancel my open task before it is booked.
- As a client, I can view all tasks I created and their current status.

### Discovery and Applications

- As a tasker, I can browse open tasks.
- As a tasker, I can search tasks by keyword and filter by category, location, and budget range.
- As a tasker, I can submit a bid with message and bid amount.
- As a tasker, I can view the status of my applications.

### Booking and Fulfillment

- As a client, I can review applications on my task and accept one tasker.
- As a client, I can book exactly one tasker for a task.
- As either party, I can see task status move from `OPEN` to `BOOKED`, then to `COMPLETED` or `CANCELLED`.

### Messaging

- As a client or tasker, I can exchange messages only when a booking exists for that task.
- As a participant, I can poll for new messages in an existing conversation.

### Reviews

- As a client, I can review the booked tasker after task completion.
- As a tasker, I can review the client after task completion.
- As either party, I can leave only one review per completed booking for the other party.

### Admin

- As an admin, I can view a list of users and disable unsafe accounts.
- As an admin, I can view a list of tasks and disable inappropriate tasks.
- As an admin, I can view basic marketplace counts: total users, active taskers, open tasks, booked tasks, completed tasks.

## Core Flows

### Flow 1: Client posts and books a tasker

1. Client signs up or signs in.
2. Client creates a task in `OPEN` status.
3. Taskers discover the task and submit applications.
4. Client reviews applications.
5. Client accepts one application.
6. Task moves to `BOOKED`.
7. A booking record and conversation become active.

### Flow 2: Tasker discovers and applies

1. Tasker signs up or signs in.
2. Tasker browses open tasks.
3. Tasker filters by category, location, keyword, or budget.
4. Tasker opens task detail and submits one application.
5. Application stays `PENDING` until client accepts or rejects it.

### Flow 3: Messaging after booking

1. Booking is created from an accepted application.
2. Client and selected tasker can open the task conversation.
3. Messages are stored in the database.
4. Frontend polls for new messages every few seconds.

### Flow 4: Completion and reviews

1. Client marks booked task as `COMPLETED`.
2. Review form unlocks for both client and tasker.
3. Each side can submit one review for the other party.

### Flow 5: Moderation

1. Admin signs in.
2. Admin views users or tasks in dashboard tables.
3. Admin toggles `isDisabled` on a user or task.
4. Disabled users cannot sign in or act; disabled tasks do not appear in public discovery.

## Status Model

### Task Status

- `OPEN`: task is visible and accepting applications
- `BOOKED`: one application accepted, conversation enabled
- `COMPLETED`: work marked done, reviews enabled
- `CANCELLED`: task no longer active
- `DISABLED`: admin-hidden task

### Application Status

- `PENDING`
- `WITHDRAWN`
- `REJECTED`
- `ACCEPTED`

### Agreement Status

- `DRAFT`
- `PENDING_ACCEPTANCE`
- `ACCEPTED`
- `CANCELLED`
- `DISPUTED`
- `REFUNDED`
- `FAILED`

## Permissions Matrix

### Client permissions

- Can create tasks
- Can update/delete own task only while `OPEN`
- Can view applications on own tasks
- Can accept or reject applications on own `OPEN` tasks
- Can message only for own `BOOKED` tasks
- Can mark own `BOOKED` task as `COMPLETED` or `CANCELLED`
- Can review booked tasker after completion

### Tasker permissions

- Can browse only non-disabled `OPEN` tasks
- Can create one application per task
- Can withdraw own `PENDING` application
- Cannot edit tasks
- Can message only when they are the booked tasker
- Can review client after completion

### Admin permissions

- Can view all records
- Can disable or enable users
- Can disable or enable tasks
- Cannot impersonate users in MVP

## Data Model

This schema is written as a product-level contract and is intended to map directly to Prisma models backed by PostgreSQL.

### User

- `id`: UUID, primary key
- `email`: string, unique, required
- `passwordHash`: string, nullable if magic link auth is later used
- `role`: enum `CLIENT | TASKER | ADMIN`
- `fullName`: string, required
- `phone`: string, nullable
- `avatarUrl`: string, nullable
- `bio`: text, nullable
- `city`: string, nullable
- `region`: string, nullable
- `country`: string, nullable
- `skillsSummary`: text, nullable, tasker-only
- `isDisabled`: boolean, default `false`
- `createdAt`: timestamp
- `updatedAt`: timestamp

### Category

- `id`: UUID, primary key
- `slug`: string, unique
- `name`: string, unique
- `createdAt`: timestamp

### Task

- `id`: UUID, primary key
- `clientId`: foreign key to `User`
- `categoryId`: foreign key to `Category`
- `title`: string, required
- `description`: text, required
- `city`: string, required
- `region`: string, nullable
- `country`: string, required
- `budgetAmount`: decimal(10,2), required
- `currency`: string(3), default `USD`
- `scheduledFor`: timestamp, nullable
- `status`: enum `OPEN | BOOKED | COMPLETED | CANCELLED | DISABLED`
- `isDisabled`: boolean, default `false`
- `createdAt`: timestamp
- `updatedAt`: timestamp

### TaskApplication

- `id`: UUID, primary key
- `taskId`: foreign key to `Task`
- `taskerId`: foreign key to `User`
- `bidAmount`: decimal(10,2), required
- `message`: text, nullable
- `status`: enum `PENDING | WITHDRAWN | REJECTED | ACCEPTED`
- `createdAt`: timestamp
- `updatedAt`: timestamp
- Unique constraint: (`taskId`, `taskerId`)

### Booking

- `id`: UUID, primary key
- `taskId`: foreign key to `Task`, unique
- `applicationId`: foreign key to `TaskApplication`, unique
- `clientId`: foreign key to `User`
- `taskerId`: foreign key to `User`
- `bookedAt`: timestamp
- `completedAt`: timestamp, nullable
- `cancelledAt`: timestamp, nullable
- `createdAt`: timestamp

### Conversation

- `id`: UUID, primary key
- `bookingId`: foreign key to `Booking`, unique
- `createdAt`: timestamp

### Message

- `id`: UUID, primary key
- `conversationId`: foreign key to `Conversation`
- `senderId`: foreign key to `User`
- `body`: text, required
- `createdAt`: timestamp

### Review

- `id`: UUID, primary key
- `bookingId`: foreign key to `Booking`
- `reviewerId`: foreign key to `User`
- `revieweeId`: foreign key to `User`
- `rating`: integer, required, constrained to 1..5
- `comment`: text, nullable
- `createdAt`: timestamp
- Unique constraint: (`bookingId`, `reviewerId`)

### Hiring Agreement

- `id`: UUID, primary key
- `bookingId`: foreign key to `Booking`, unique
- `agreementUid`: unique public verification identifier
- `jobTitle`: string, required
- `jobDescription`: text, required
- `offlinePaymentTermsText`: text, required
- `compensationTermsText`: text, required
- `cancellationTermsText`: text, required
- `status`: enum `DRAFT | PENDING_ACCEPTANCE | ACCEPTED | CANCELLED | DISPUTED`
- `createdAt`: timestamp
- `updatedAt`: timestamp

### AdminActionLog

- `id`: UUID, primary key
- `adminId`: foreign key to `User`
- `targetType`: enum `USER | TASK`
- `targetId`: UUID
- `action`: string
- `notes`: text, nullable
- `createdAt`: timestamp

## Key Relationships

- One client creates many tasks.
- One category belongs to many tasks.
- One task has many applications.
- One task has zero or one booking.
- One accepted application creates one booking.
- One booking has one conversation.
- One conversation has many messages.
- One booking has up to two reviews, one from each party.
- One booking has one hiring agreement record.

## Indexes and Constraints

- Unique: `User.email`
- Unique: `Category.slug`
- Unique: `TaskApplication(taskId, taskerId)`
- Unique: `Booking.taskId`
- Unique: `Booking.applicationId`
- Unique: `Conversation.bookingId`
- Unique: `HiringAgreement.bookingId`
- Unique: `Review(bookingId, reviewerId)`
- Index: `Task.status`
- Index: `Task.categoryId`
- Index: `Task.city`
- Index: `Task.createdAt`
- Index: `TaskApplication.taskerId`
- Index: `Message.conversationId, createdAt`

## Business Rules

- Only active, non-disabled users can sign in and perform actions.
- Only `OPEN` tasks appear in discovery.
- Clients cannot apply to tasks; taskers cannot create tasks.
- A tasker may submit only one application per task.
- Accepting one application rejects all other pending applications for the task.
- Messaging is allowed only after booking exists.
- Reviews are allowed only after task status is `COMPLETED`.
- A reviewer cannot review themselves.
- Disabled tasks remain in admin history but are hidden from public and tasker views.

## Edge Cases

- Client cancels task before any application exists.
- Client cancels task after applications exist but before booking.
- Client attempts to edit a task after booking.
- Tasker attempts to apply to an already booked, cancelled, completed, or disabled task.
- Tasker attempts to apply twice to the same task.
- Client attempts to book more than one tasker.
- Non-booked users attempt to access conversation messages.
- One party leaves a review and the other does not.
- Disabled user tries to sign in with an existing session.
- Admin disables a task that already has applications or a booking.

## API Route List

The route list assumes PHP-based API handlers under `/api`. Authentication session handling can be adapted later if NextAuth remains a hard requirement, but this is the minimal route surface for the MVP.

### Auth

- `POST /api/auth/register`
  - Create account as `CLIENT` or `TASKER`
- `POST /api/auth/login`
  - Email/password login
- `POST /api/auth/logout`
  - Destroy session
- `GET /api/auth/session`
  - Return current authenticated user

### Profile

- `GET /api/profile`
  - Return current user profile
- `PATCH /api/profile`
  - Update current user profile
- `GET /api/users/:id`
  - Public-safe user profile for booked counterpart or admin use

### Categories

- `GET /api/categories`
  - List categories

### Tasks

- `GET /api/tasks`
  - List tasks with filters: `q`, `category`, `city`, `minBudget`, `maxBudget`, `status`
- `POST /api/tasks`
  - Create task, client-only
- `GET /api/tasks/:id`
  - Get task detail
- `PATCH /api/tasks/:id`
  - Update own open task
- `DELETE /api/tasks/:id`
  - Soft-cancel own open task
- `POST /api/tasks/:id/complete`
  - Mark own booked task completed
- `POST /api/tasks/:id/cancel`
  - Cancel own open or booked task with rules

### Applications

- `GET /api/tasks/:id/applications`
  - Client gets applications on own task
- `POST /api/tasks/:id/applications`
  - Tasker applies to task
- `PATCH /api/applications/:id`
  - Withdraw own application or client rejects pending application
- `POST /api/applications/:id/accept`
  - Client accepts application and creates booking

### Bookings

- `GET /api/bookings`
  - List current user bookings
- `GET /api/bookings/:id`
  - Get booking detail if participant or admin

### Messaging

- `GET /api/conversations/:bookingId/messages`
  - Poll messages for booking conversation
- `POST /api/conversations/:bookingId/messages`
  - Send message if booking participant

### Reviews

- `GET /api/bookings/:bookingId/reviews`
  - Get reviews for booking
- `POST /api/bookings/:bookingId/reviews`
  - Create review by participant after completion

### Agreements

- `GET /agreements/review?id=:agreementId`
  - Review agreement and audit history
- `POST /agreements/accept`
  - Accept agreement as client or tasker
- `GET /agreements/verify?agreement_uid=:agreementUid`
  - Verify a public agreement record

### Admin

- `GET /api/admin/analytics`
  - Counts for dashboard
- `GET /api/admin/users`
  - List users with filters
- `PATCH /api/admin/users/:id/disable`
  - Disable or enable user
- `GET /api/admin/tasks`
  - List tasks with filters
- `PATCH /api/admin/tasks/:id/disable`
  - Disable or enable task
- `GET /api/admin/action-logs`
  - List moderation actions

## Non-Goals for MVP

- No on-platform payment processing or escrow
- No live websocket chat
- No background jobs
- No provider verification or identity checks
- No map search, distance radius, or geocoding
- No dispute management flow
- No multi-tasker bookings
- No promotions, coupons, or subscriptions

## Suggested Next Deliverable Inputs

Deliverable 2 should convert this contract into:

- Folder structure for `frontend`, `api`, and `prisma`
- `.env.example`
- Prisma schema matching the models above
- Seed script for categories, sample users, tasks, applications, and one admin account
