<div class="container">
    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Pricing and protection</span>
            <h1>Job payments stay offline. Subscriptions are collected through MTN MoMo Rwanda.</h1>
            <p class="page-intro">Kazilink does not collect client-to-tasker job payments. Instead, it charges monthly subscription access through MTN MoMo and keeps the Hiring Agreement flow focused on proof of hire, scope, and dispute evidence.</p>
        </div>
    </section>

    <?php $plans = is_array($plans ?? null) ? $plans : []; ?>
    <?php if ($plans !== []): ?>
        <section class="panel">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Subscription plans</span>
                    <h2>Visibility tiers</h2>
                </div>
            </div>
            <div class="pricing-grid">
                <?php foreach ($plans as $plan): ?>
                    <article class="pricing-card <?= (string) $plan['slug'] === 'medium' ? 'pricing-card-featured' : '' ?>">
                        <div class="pricing-card-head">
                            <div>
                                <h3><?= e((string) $plan['name']) ?></h3>
                                <span class="pricing-badge">Visibility level <?= e((string) $plan['visibility_level']) ?></span>
                            </div>
                            <div class="pricing-amount">
                                <strong><?= e(moneyRwf((int) $plan['price_rwf'])) ?></strong>
                                <span>per month</span>
                            </div>
                        </div>
                        <p class="muted">New users begin on a 30-day free Basic trial. Higher plans improve ranking and visibility across discovery surfaces.</p>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="panel panel-subtle">
        <div class="section-head">
            <div>
                <span class="eyebrow">Core protections</span>
                <h2>What the marketplace records for each hire</h2>
            </div>
        </div>
        <div class="marketing-grid marketing-grid-three">
            <article class="feature-card">
                <h3>Agreement UID and issue time</h3>
                <p class="muted">Each hiring agreement gets a unique public verification code and a recorded issue timestamp.</p>
            </article>
            <article class="feature-card">
                <h3>Job scope and compensation rules</h3>
                <p class="muted">The agreement records title, description, category, location, expected timing, offline job payment terms, and what happens if access fails or the scope changes.</p>
            </article>
            <article class="feature-card">
                <h3>Acceptance and dispute evidence</h3>
                <p class="muted">Both sides accept digitally. The platform stores timestamps, IP address, user agent, and dispute logs as evidence.</p>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Dispute coverage</span>
                <h2>The agreement is built for real-world problems</h2>
            </div>
        </div>
        <div class="marketing-grid marketing-grid-three">
            <article class="info-card">
                <h3>Non-payment</h3>
                <p class="muted">If the tasker is not paid as agreed, they can open a dispute and rely on the agreement plus logs as evidence that the hire happened through the platform.</p>
            </article>
            <article class="info-card">
                <h3>Client unavailable or site closed</h3>
                <p class="muted">The agreement can show transport reimbursement and standby or call-out compensation when the tasker arrives but cannot start work.</p>
            </article>
            <article class="info-card">
                <h3>No-show and scope change</h3>
                <p class="muted">The record preserves grace periods, cancellations, and later scope updates so both sides have a shared source of truth.</p>
            </article>
        </div>
    </section>
</div>
