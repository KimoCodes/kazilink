# Router Access Audit

## Purpose

This document maps each whitelisted route to:

- intended role
- allowed HTTP methods
- required request params
- IDOR protection notes

## Route Matrix

| Route | Intended role | Methods | Required params | IDOR / authorization notes |
|---|---|---|---|---|
| `home/index` | guest, client, tasker, admin | `GET` | none | public home page |
| `auth/login` | guest | `GET`, `POST` | `POST`: `email`, `password`, CSRF token | guest-only; inactive users blocked at login |
| `auth/register` | guest | `GET`, `POST` | `POST`: `full_name`, `email`, `role`, `password`, CSRF token | guest-only; public role limited to `client` or `tasker` |
| `auth/logout` | client, tasker, admin | `POST` | CSRF token | session logout only |
| `tasks/index` | client | `GET` | none | lists only current client’s tasks |
| `tasks/browse` | tasker, admin | `GET` | optional: `q`, `category_id`, `city`, `min_budget`, `max_budget` | shows only active open tasks |
| `tasks/create` | client | `GET`, `POST` | `POST`: task fields + CSRF token | creates task for current client only |
| `tasks/show` | client owner, admin | `GET` | `id` | owner/admin check prevents viewing another client’s task management page |
| `tasks/view` | tasker, admin | `GET` | `id` | only active open tasks are visible in discovery |
| `tasks/edit` | client owner | `GET`, `POST` | `id` on GET or POST, task fields on POST, CSRF token on POST | owner-only and open-task-only |
| `tasks/cancel` | client owner | `POST` | `id`, CSRF token | owner-only and open-task-only |
| `bids/create` | tasker | `POST` | `task_id`, `amount`, optional `message`, CSRF token | task must be active/open; tasker cannot bid on own task; one bid per tasker/task |
| `bids/accept` | client owner | `POST` | `bid_id`, CSRF token | bid must belong to current client’s task and remain pending on an open task |
| `bookings/index` | client, tasker, admin | `GET` | none | client sees own bookings, tasker sees own bookings, admin sees all |
| `bookings/show` | booking participant, admin | `GET` | `id` | booking visibility restricted to participant or admin |
| `bookings/complete` | booking client | `POST` | `booking_id`, CSRF token | only current client can complete own active booking |
| `messages/thread` | booking client/tasker | `GET`, `POST` | `GET`: `id`; `POST`: `booking_id`, `body`, CSRF token | booking participant check prevents thread IDOR |
| `reviews/create` | booking client | `GET`, `POST` | `GET`: `booking_id`; `POST`: `booking_id`, `rating`, optional `comment`, CSRF token | only current client on a completed booking; duplicate reviews blocked |
| `admin/dashboard` | admin | `GET` | none | admin-only summary page |
| `admin/users` | admin | `GET` | none | admin-only user list |
| `admin/tasks` | admin | `GET` | none | admin-only task list |
| `admin/toggle-user` | admin | `POST` | `user_id`, CSRF token | admin-only; cannot deactivate own account |
| `admin/toggle-task` | admin | `POST` | `task_id`, CSRF token | admin-only toggle of task active state |

## State-changing Routes

These routes mutate state and are now router-enforced as `POST` only:

- `auth/logout`
- `tasks/cancel`
- `bids/create`
- `bids/accept`
- `bookings/complete`
- `admin/toggle-user`
- `admin/toggle-task`

These routes support both `GET` and `POST` because `GET` renders a form/thread and `POST` submits a change:

- `auth/login`
- `auth/register`
- `tasks/create`
- `tasks/edit`
- `messages/thread`
- `reviews/create`

## IDOR Review Summary

### Verified protections present

- `tasks/show`: owner or admin only
- `tasks/edit`: owner only
- `tasks/cancel`: owner only
- `bids/accept`: bid must belong to current client’s task
- `bookings/show`: participant or admin only
- `bookings/complete`: current client only
- `messages/thread`: booking participant only
- `reviews/create`: current client on completed booking only
- `admin/*`: admin only

### Missing IDOR checks found

- None found after review of the current route/controller set.

## Notes

- The router now rejects unsupported methods with HTTP `405 Method Not Allowed`.
- Controller-level authorization checks remain in place and continue to be the primary access-control layer.
