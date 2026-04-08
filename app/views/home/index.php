<?php
$statusCode = (int) ($statusCode ?? 200);
$marketplaceStats = is_array($marketplaceStats ?? null) ? $marketplaceStats : [];
$featuredCategories = is_array($featuredCategories ?? null) ? $featuredCategories : [];
$openTasks = (int) ($marketplaceStats['open_tasks'] ?? 0);
$activeTaskers = (int) ($marketplaceStats['active_taskers'] ?? 0);
$activeClients = (int) ($marketplaceStats['active_clients'] ?? 0);
$activeCategories = (int) ($marketplaceStats['active_categories'] ?? 0);
$ads = is_array($ads ?? null) ? $ads : [];
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
                    <h1>Calm hiring for everyday work.</h1>
                    <p class="page-intro">Kazilink gives households and teams a cleaner hiring flow: clear requests, MTN MoMo subscription access, transparent offline job payment expectations, one message thread per booking, and a hiring agreement that captures who was hired, for what work, and what happens if problems come up.</p>

                    <div class="hero-actions">
                        <?php if (!Auth::check()): ?>
                            <a class="button" href="<?= e(url_for('auth/register')) ?>">Create account</a>
                            <a class="button button-secondary" href="<?= e(url_for('marketing/pricing')) ?>">See how protection works</a>
                        <?php elseif (($role ?? null) === 'client'): ?>
                            <a class="button" href="<?= e(url_for('tasks/create')) ?>">Post a task</a>
                            <a class="button button-secondary" href="<?= e(url_for('tasks/index')) ?>">Manage my tasks</a>
                        <?php elseif (($role ?? null) === 'tasker'): ?>
                            <a class="button" href="<?= e(url_for('tasks/browse')) ?>">Browse tasks</a>
                            <a class="button button-secondary" href="<?= e(url_for('tasker/dashboard')) ?>">Open dashboard</a>
                        <?php else: ?>
                            <a class="button" href="<?= e(url_for('admin/dashboard')) ?>">Open admin dashboard</a>
                            <a class="button button-secondary" href="<?= e(url_for('marketing/pricing')) ?>">Review protection flow</a>
                        <?php endif; ?>
                    </div>

                    <div class="home-editorial-note">
                        <span>Designed for clarity, trust, and quieter decision-making.</span>
                    </div>

                    <div class="hero-proof">
                        <span class="proof-pill"><strong>Offline job payments</strong> with no client-to-tasker escrow on-platform</span>
                        <span class="proof-pill"><strong>MTN MoMo subscriptions</strong> for monthly access and visibility</span>
                        <span class="proof-pill"><strong>Hiring Agreement</strong> generated when a tasker is hired</span>
                        <span class="proof-pill"><strong>Evidence-ready logs</strong> for disputes and scope changes</span>
                        <span class="proof-pill"><strong>Trust-first UX</strong> with practical copy and clear navigation</span>
                    </div>
                </div>

                <div class="home-hero-side">
                    <article class="home-market-snapshot">
                        <div class="home-snapshot-head">
                            <span class="eyebrow">Live marketplace snapshot</span>
                            <h2>Current activity, presented without noise.</h2>
                            <p>Real product signals from the current workspace, used as a credibility layer instead of hype.</p>
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

        <?php
        if ($ads !== []) {
            require BASE_PATH . '/app/views/partials/ad-banner.php';
        }
        ?>

        <section class="panel panel-subtle">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Why it feels better</span>
                    <h2>A quieter interface for real-world service requests</h2>
                    <p class="section-intro">The experience is organized around the questions people ask before they trust a platform: what it does, how much it costs, what happens next, and how to contact a real person.</p>
                </div>
            </div>
            <div class="marketing-grid marketing-grid-three">
                <article class="feature-card feature-card-accent">
                    <h3>Clear paths by intent</h3>
                    <p class="muted">Visitors can move from overview to pricing to contact without guessing where conversion or support lives.</p>
                </article>
                <article class="feature-card feature-card-accent">
                    <h3>Credible hiring records</h3>
                    <p class="muted">Once a client hires a tasker, the platform creates a hiring agreement that records scope, timing, compensation rules, and both acceptances.</p>
                </article>
                <article class="feature-card feature-card-accent">
                    <h3>Trust through specifics</h3>
                    <p class="muted">Copy is grounded in what the platform supports today: matching, MTN MoMo subscription billing, hiring records, and dispute evidence rather than job-payment escrow promises.</p>
                </article>
            </div>
        </section>

        <section class="panel" id="services">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Popular services</span>
                    <h2>Built for the local jobs people actually need solved</h2>
                    <p class="section-intro">Focused categories keep task briefs concise, pricing cleaner, and responses more relevant.</p>
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
                    <p class="section-intro">Every step is explicit, so people know when they are browsing, when they are committing, and what record exists if something later goes wrong.</p>
                </div>
            </div>
            <div class="home-flow-grid">
                <article class="feature-card">
                    <span class="home-step-index">01</span>
                    <h3>Start with a clear brief</h3>
                    <p class="muted">Post a focused task with budget, category, city, and timing so the right taskers can respond quickly once subscription access is active.</p>
                </article>
                <article class="feature-card">
                    <span class="home-step-index">02</span>
                    <h3>Compare responses with context</h3>
                    <p class="muted">Clients can review bids without losing the original request or jumping between disconnected threads.</p>
                </article>
                <article class="feature-card">
                    <span class="home-step-index">03</span>
                    <h3>Hire, pay offline, and keep proof</h3>
                    <p class="muted">When a bid is accepted, the platform creates a hiring agreement, records dual acceptance, and preserves an evidence trail for payment issues, no-shows, or scope changes.</p>
                </article>
            </div>
        </section>

        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Platform promise</span>
                    <h2>Protection comes from records, not platform noise</h2>
                    <p class="section-intro">The platform helps parties match, hire, and keep a trustworthy record. Job payment is arranged directly between client and tasker, while subscriptions are paid separately through MTN MoMo.</p>
                </div>
                <div class="page-actions">
                    <a class="button button-secondary" href="<?= e(url_for('marketing/pricing')) ?>">Open protection details</a>
                </div>
            </div>
            <div class="marketing-grid marketing-grid-three">
                <article class="feature-card">
                    <h3>Match and hire</h3>
                    <p class="muted">Clients post tasks, compare bids, and choose the tasker they want to hire through the marketplace flow.</p>
                </article>
                <article class="feature-card">
                    <h3>Generate an agreement</h3>
                    <p class="muted">The platform produces a hiring agreement with scope, location, timing, offline payment terms, compensation rules, and signature timestamps.</p>
                </article>
                <article class="feature-card">
                    <h3>Preserve evidence</h3>
                    <p class="muted">If payment fails, the client is unavailable, the tasker does not show, or the scope changes, the agreement and log history create a reliable record.</p>
                </article>
            </div>
        </section>
    </div>
<?php endif; ?>
