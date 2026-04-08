You are a senior full-stack engineer specializing in PHP (procedural + MySQLi) and building scalable marketplace platforms.

I have an existing PHP + MySQL platform that connects clients with taskers for informal jobs. I want to implement a 3-tier subscription trial system with clear performance-based differentiation.

Your task is to DESIGN and IMPLEMENT this system cleanly into an existing codebase.

---

## SYSTEM REQUIREMENTS

Tech stack:

* PHP (no frameworks, no Composer)
* MySQL database
* Hosted environment (no terminal access)
* Existing system includes:

  * Users table
  * Jobs table
  * Applications system
  * Checkout system (PayPal + MoMo already integrated)

---

## FEATURE OBJECTIVE

Implement 3 subscription plans:

1. Basic Trial
2. Growth Trial
3. Pro Trial

Each plan must affect:

* Job visibility timing
* Application limits per day
* Search ranking priority
* Job posting limits (for clients)
* Profile badges
* Commission discount (for Pro)

---

## STEP 1: DATABASE DESIGN

Create SQL schema changes if not exist:

1. plans table:

* id
* name (basic, growth, pro)
* max_applications_per_day
* priority_level (int)
* job_alert_delay_minutes
* max_active_jobs
* commission_discount
* badge_name

2. user_subscriptions table:

* id
* user_id
* plan_id
* start_date
* end_date
* status (active, expired)

3. user_metrics table:

* user_id
* daily_applications_count
* last_reset_date

Provide full SQL statements.

---

## STEP 2: PLAN SEED DATA

Insert default values:

Basic:

* 5 applications/day
* priority 1
* 10 min delay
* 1 active job
* no badge

Growth:

* 20 applications/day
* priority 2
* 0 min delay
* 5 jobs
* "Rising Tasker"

Pro:

* unlimited applications (use large number like 9999)
* priority 3
* early access (-5 min logic)
* unlimited jobs
* "Verified Pro"
* commission discount

---

## STEP 3: BACKEND LOGIC (PHP)

Implement reusable functions:

1. getUserPlan($user_id)
2. canApplyToJob($user_id)
3. incrementApplicationCount($user_id)
4. resetDailyLimitsIfNeeded($user_id)
5. getJobVisibilityTime($plan)

Logic rules:

* Block application if limit reached
* Reset daily counters every new day
* Apply delay to job visibility based on plan
* Pro users see jobs earlier than others

---

## STEP 4: JOB VISIBILITY SYSTEM

Modify job fetching query:

* Jobs should only be visible if:
  CURRENT_TIME >= job_created_at + plan_delay

* Order results by:
  priority_level DESC
  then created_at DESC

---

## STEP 5: APPLICATION LIMIT ENFORCEMENT

Before inserting a job application:

* Check plan limit
* Reject with error message if exceeded

---

## STEP 6: BADGE SYSTEM

Display badge dynamically:

* Fetch from plan
* Show in:

  * User profile
  * Job applications list

---

## STEP 7: CLIENT-SIDE UI ADJUSTMENTS

Generate HTML + CSS for:

* Pricing table (3 plans)
* Badge display
* Upgrade button

Keep it clean and modern.

---

## STEP 8: ADMIN CONTROL (OPTIONAL BUT PREFERRED)

Provide if not exist:

* Simple admin page to:

  * Edit plans
  * View user subscriptions

---

## STEP 9: CODE QUALITY

* Use MySQLi (not PDO)
* Write modular, reusable PHP functions
* Avoid frameworks
* Include comments for each section
* Ensure security:

  * Prepared statements
  * Basic validation

---

## OUTPUT FORMAT

Return:

1. SQL schema (complete)
2. PHP backend functions (grouped by feature)
3. Example integration into:

   * job listing page
   * application submission
4. UI components (HTML + CSS)

Do NOT explain theory.
Focus on clean, working implementation code.
