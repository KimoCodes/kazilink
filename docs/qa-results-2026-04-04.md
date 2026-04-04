# QA Results - 2026-04-04

- Tester: Codex
- Environment: macOS, XAMPP PHP 8.2.x, local workspace review
- Verification type: static code review, route/security audit, targeted PHP linting
- P0 blockers found in this pass: no confirmed blockers remain in code review
- Browser/manual QA complete: no
- Ready for demo after manual checklist run: pending

## Summary

This report replaces the earlier placeholder result set. The checks below reflect what was actually verified on April 4, 2026 from code inspection and local PHP linting, not a full browser-based end-to-end session.

## Verified In This Pass

| Area | Check | Result | Evidence |
| --- | --- | --- | --- |
| Setup Sanity | PHP syntax on touched controllers, helpers, and views | Pass | `php -l` passed on auth, task, bid, profile, review, message, and helper files |
| Auth | Session regeneration on login | Pass | `Auth::login()` still calls `Session::regenerate()` |
| Auth | Logout invalidates session and clears cookie | Pass | `Session::invalidate()` is used by logout |
| Auth | Login no longer reveals inactive vs unknown vs locked accounts | Pass | Neutral login failure flow in `app/controllers/AuthController.php` |
| Auth | Browser-level repeated login throttling exists | Pass | `_login_attempts` window enforcement in `app/controllers/AuthController.php` |
| CSRF | POST-only actions checked through shared helper or explicit verification | Pass | `verifyPostRequest()` and `Csrf::verifyRequest()` usage across mutating controllers |
| Tasks | Task create/edit now return field-level validation errors | Pass | `Validator::taskFields()` plus `fieldErrors` rendering |
| Bids | Invalid bids stay on task page with inline validation | Pass | `BidController::create()` now re-renders `tasks/view` |
| Messaging | Stored XSS in live polling path | Pass | Thread rendering uses escaped text and safe DOM text insertion |
| Messaging | Message order remains oldest to newest | Pass | Existing message fetch remains ordered and polling appends newer IDs only |
| Reviews | Review validation is server-side and field-aware | Pass | `Validator::reviewFields()` and `ReviewController::create()` |
| Profiles | Avatar URLs no longer use hardcoded `/informal/public/...` paths | Pass | `public_url()` helper used in profile views |
| Rwanda | Money and date helpers remain standardized | Pass | `moneyRwf()` and `dateFmt()` are used in current shared flows |

## Manual QA Still Required

These checks are still pending in a browser with the seeded demo accounts:

1. Register a new client and tasker account and confirm full success flow.
2. Trigger repeated failed logins and confirm the neutral lockout UX feels acceptable.
3. Create, edit, and cancel a task as a client.
4. Bid on a task as a tasker, then accept the bid as the client.
5. Send messages from both sides and confirm live polling visually.
6. Mark a booking completed and submit a review.
7. Deactivate and reactivate users/tasks from admin and confirm cross-session behavior.
8. Run the CSRF tampering checks from `docs/qa-mvp-checklist.md`.

## Notes

- No database migration was needed for this hardening/refactor pass.
- The current repo is materially safer and more maintainable than the earlier placeholder QA report implied.
- The remaining risk is execution risk, not an obvious code-level blocker: the browser checklist still needs to be run end to end.
