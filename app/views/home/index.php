<?php
$statusCode = (int) ($statusCode ?? 200);
$marketplaceStats = is_array($marketplaceStats ?? null) ? $marketplaceStats : [];
$featuredCategories = is_array($featuredCategories ?? null) ? $featuredCategories : [];
$plans = is_array($plans ?? null) ? $plans : array_values(pricing_plans());
$paymentsEnabled = (bool) ($paymentsEnabled ?? payments_enabled());
$openTasks = (int) ($marketplaceStats['open_tasks'] ?? 0);
$activeTaskers = (int) ($marketplaceStats['active_taskers'] ?? 0);
$activeClients = (int) ($marketplaceStats['active_clients'] ?? 0);
$activeCategories = (int) ($marketplaceStats['active_categories'] ?? 0);
?>
<?php if ($statusCode !== 200): ?>
    <div class="container narrow">
        <section class="panel panel-subtle">
            <?php
            $eyebrow = $statusCode === 404 ? 'Not Found' : 'Something went wrong';
            $title = $statusCode === 404 ? 'Page not found' : 'Please try again shortly';
            $intro = $message ?? 'The page you requested could not be displayed.';
            $primaryAction = ['label' => 'Back home', 'href' => url_for('home/index')];
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>
        </section>
    </div>
<?php else: ?>
    <div class="container">
        <section class="panel hero-surface home-hero-panel">
            <div class="hero-grid home-hero-grid">
                <div class="hero-copy">
                    <span class="eyebrow">Professional local help</span>
                    <h1>Book reliable support with less friction and more clarity.</h1>
                    <p class="page-intro">Kazilink is designed for households and teams that want a clean workflow: clear requests, transparent pricing, one message thread per booking, and a checkout path that feels trustworthy instead of improvised.</p>

                    <div class="hero-actions">
                        <?php if (!Auth::check()): ?>
                            <a class="button" href="<?= e(url_for('auth/register')) ?>">Create account</a>
                            <a class="button button-secondary" href="<?= e(url_for('marketing/pricing')) ?>">View pricing</a>
                        <?php elseif (($role ?? null) === 'client'): ?>
                            <a class="button" href="<?= e(url_for('tasks/create')) ?>">Post a task</a>
                            <a class="button button-secondary" href="<?= e(url_for('tasks/index')) ?>">Manage my tasks</a>
                        <?php elseif (($role ?? null) === 'tasker'): ?>
                            <a class="button" href="<?= e(url_for('tasks/browse')) ?>">Browse tasks</a>
                            <a class="button button-secondary" href="<?= e(url_for('tasker/dashboard')) ?>">Open dashboard</a>
                        <?php else: ?>
                            <a class="button" href="<?= e(url_for('admin/dashboard')) ?>">Open admin dashboard</a>
                            <a class="button button-secondary" href="<?= e(url_for('marketing/pricing')) ?>">Review pricing</a>
                        <?php endif; ?>
                    </div>

                    <div class="hero-proof">
                        <span class="proof-pill"><strong>Secure Checkout</strong> via Stripe-hosted payment pages</span>
                        <span class="proof-pill"><strong>Trust-first UX</strong> with practical copy and clear navigation</span>
                        <span class="proof-pill"><strong>Single-thread bookings</strong> so job history stays readable</span>
                    </div>
                </div>

                <div class="home-hero-side">
                    <article class="home-market-snapshot">
                        <div class="home-snapshot-head">
                            <span class="eyebrow">Live marketplace snapshot</span>
                            <p>Real product signals from the current workspace, presented as a credibility layer instead of hype.</p>
                        </div>
                        <div class="home-stats-grid">
                            <div class="home-stat-card">
                                <span>Open tasks</span>
                                <strong><?= e((string) $openTasks) ?></strong>
                                <p>Active requests currently available for bidding or review.</p>
                            </div>
                            <div class="home-stat-card">
                                <span>Active taskers</span>
                                <strong><?= e((string) $activeTaskers) ?></strong>
                                <p>Profiles ready to respond with clear offers and availability.</p>
                            </div>
                            <div class="home-stat-card">
                                <span>Active clients</span>
                                <strong><?= e((string) $activeClients) ?></strong>
                                <p>People already using the platform to coordinate local work.</p>
                            </div>
                            <div class="home-stat-card">
                                <span>Service categories</span>
                                <strong><?= e((string) $activeCategories) ?></strong>
                                <p>Focused lanes that keep requests easier to understand and fulfill.</p>
                            </div>
                        </div>
                    </article>

                    <article class="welcome-panel" data-return-visitor data-personalization-name="<?= e((string) (($user['full_name'] ?? '') !== '' ? $user['full_name'] : 'friend')) ?>">
                        <div>
                            <span class="eyebrow">Welcome back</span>
                            <h2>Keep the next step simple.</h2>
                            <p class="muted" data-return-visitor-copy>We will remember that you visited recently and show a lightweight welcome panel without storing sensitive information.</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="panel panel-subtle">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Why it feels better</span>
                    <h2>A cleaner information architecture for real-world service requests</h2>
                    <p class="section-intro">The updated experience is organized around the questions people actually ask before they trust a platform: what it does, how much it costs, what happens next, and how to contact a real person.</p>
                </div>
            </div>
            <div class="marketing-grid marketing-grid-three">
                <article class="feature-card feature-card-accent">
                    <h3>Clear paths by intent</h3>
                    <p class="muted">Visitors can move from overview to pricing to contact without guessing where conversion or support lives.</p>
                </article>
                <article class="feature-card feature-card-accent">
                    <h3>Professional payment entry point</h3>
                    <p class="muted">The pricing page routes directly into Stripe Checkout, using environment-based keys and hosted payment screens.</p>
                </article>
                <article class="feature-card feature-card-accent">
                    <h3>Trust through specifics</h3>
                    <p class="muted">Copy is grounded in what the platform already supports today, not feature promises that would weaken confidence.</p>
                </article>
            </div>
        </section>

        <section class="panel" id="services">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Popular services</span>
                    <h2>Built for the kinds of local jobs people actually need solved</h2>
                    <p class="section-intro">Use focused categories to keep task briefs concise, pricing cleaner, and responses more relevant.</p>
                </div>
            </div>

            <?php if ($featuredCategories === []): ?>
                <?php
                $emptyIcon = '📂';
                $emptyTitle = 'No active categories yet';
                $emptyMessage = 'Activate categories to make task posting and discovery feel complete.';
                require BASE_PATH . '/app/views/partials/empty_state.php';
                ?>
            <?php else: ?>
                <div class="home-category-grid">
                    <?php foreach ($featuredCategories as $category): ?>
                        <?php
                        if (($role ?? null) === 'tasker' || ($role ?? null) === 'admin') {
                            $categoryCtaHref = url_for('tasks/browse', ['category_id' => (int) $category['id']]);
                            $categoryCtaLabel = 'Browse related tasks';
                        } elseif (($role ?? null) === 'client') {
                            $categoryCtaHref = url_for('tasks/create');
                            $categoryCtaLabel = 'Post a task in this category';
                        } else {
                            $categoryCtaHref = url_for('auth/register');
                            $categoryCtaLabel = 'Create an account to get started';
                        }
                        ?>
                        <article class="home-category-card">
                            <h3><?= e((string) $category['name']) ?></h3>
                            <p>Use this category when you want pricing, expectations, and discovery to stay easy to scan.</p>
                            <a class="button-link" href="<?= e($categoryCtaHref) ?>"><?= e($categoryCtaLabel) ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel panel-subtle" id="how-it-works">
            <div class="section-head">
                <div>
                    <span class="eyebrow">How it works</span>
                    <h2>A simple workflow from request to booking</h2>
                    <p class="section-intro">Every step is explicit, so people know when they are browsing, when they are committing, and when they are paying.</p>
                </div>
            </div>
            <div class="home-flow-grid">
                <article class="feature-card">
                    <span class="home-step-index">01</span>
                    <h3>Start with a clear brief</h3>
                    <p class="muted">Post a focused task with budget, category, city, and timing so the right taskers can respond quickly.</p>
                </article>
                <article class="feature-card">
                    <span class="home-step-index">02</span>
                    <h3>Compare responses with context</h3>
                    <p class="muted">Clients can review bids without losing the original request or jumping between disconnected threads.</p>
                </article>
                <article class="feature-card">
                    <span class="home-step-index">03</span>
                    <h3>Pay and coordinate confidently</h3>
                    <p class="muted">The refreshed pricing flow gives you a clean payment handoff while the booking workspace keeps the follow-up organized.</p>
                </article>
            </div>
        </section>

        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Pricing overview</span>
                    <h2>Concise plans with a secure route to pay</h2>
                    <p class="section-intro">These one-time plans are designed as simple professional defaults. You can tune amounts and copy later without changing the flow.</p>
                </div>
                <div class="page-actions">
                    <a class="button button-secondary" href="<?= e(url_for('marketing/pricing')) ?>">Open full pricing</a>
                </div>
            </div>
            <div class="pricing-grid">
                <?php foreach ($plans as $plan): ?>
                    <article class="pricing-card <?= !empty($plan['highlighted']) ? 'pricing-card-featured' : '' ?>">
                        <div class="pricing-card-head">
                            <div>
                                <span class="pricing-badge"><?= e((string) ($plan['badge'] ?? 'Plan')) ?></span>
                                <h3><?= e((string) $plan['name']) ?></h3>
                                <p class="muted"><?= e((string) $plan['description']) ?></p>
                            </div>
                            <div class="pricing-amount">
                                <strong><?= e(moneyRwf($plan['amount'])) ?></strong>
                                <span><?= e((string) ($plan['billing_label'] ?? 'One-time payment')) ?></span>
                            </div>
                        </div>
                        <ul class="check-list">
                            <?php foreach ((array) ($plan['features'] ?? []) as $feature): ?>
                                <li><?= e((string) $feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if ($paymentsEnabled): ?>
                            <form method="post" action="<?= e(url_for('payments/checkout')) ?>">
                                <?= Csrf::input() ?>
                                <input type="hidden" name="plan_id" value="<?= e((string) $plan['id']) ?>">
                                <button type="submit" class="button button-block"><?= e((string) ($plan['cta'] ?? 'Pay now')) ?></button>
                            </form>
                        <?php else: ?>
                            <div class="setup-note">
                                <strong>Stripe setup required</strong>
                                <p class="muted">Add your Stripe keys in `.env` to enable live checkout buttons.</p>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="panel panel-subtle">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Trust signals</span>
                    <h2>Space for real proof, without pretending it already exists</h2>
                    <p class="section-intro">These cards are ready for approved testimonials, partner logos, or case studies once you have them. For now, they stay honest and clearly framed.</p>
                </div>
            </div>
            <div class="marketing-grid marketing-grid-three">
                <article class="quote-card">
                    <p>“Add your first verified client quote here after launch.”</p>
                    <span>Placeholder testimonial slot</span>
                </article>
                <article class="quote-card">
                    <p>“Add a tasker quote about clarity, trust, or better communication.”</p>
                    <span>Placeholder testimonial slot</span>
                </article>
                <article class="quote-card">
                    <p>“Add a business or household use case with measurable value.”</p>
                    <span>Placeholder case study slot</span>
                </article>
            </div>
        </section>

        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">FAQs</span>
                    <h2>Short answers to the questions people ask before they engage</h2>
                </div>
            </div>
            <div class="faq-list">
                <details class="faq-item">
                    <summary>Do I need an account before using the platform?</summary>
                    <p>You can browse the public marketing pages first, but you need an account to post tasks, browse as a tasker, or manage bookings.</p>
                </details>
                <details class="faq-item">
                    <summary>How does payment work?</summary>
                    <p>The pricing page starts a Stripe Checkout session. Payment happens on Stripe-hosted pages, not inside your own form fields.</p>
                </details>
                <details class="faq-item">
                    <summary>Can I still talk to someone before paying?</summary>
                    <p>Yes. The contact page is meant for support questions, partnership requests, and operational follow-up.</p>
                </details>
            </div>
        </section>
    </div>
<?php endif; ?>
