<?php $selectedPlan = is_array($selectedPlan ?? null) ? $selectedPlan : null; ?>
<div class="container narrow">
    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">Payment cancelled</span>
            <h1>No charge was completed.</h1>
            <p class="page-intro">You left Stripe Checkout before finishing payment. You can safely review the plan again or contact support if something felt unclear.</p>
        </div>
    </section>

    <section class="panel panel-subtle">
        <?php if ($selectedPlan !== null): ?>
            <p class="muted">Last selected plan: <strong><?= e((string) $selectedPlan['name']) ?></strong> at <?= e(moneyRwf($selectedPlan['amount'])) ?>.</p>
        <?php endif; ?>
        <div class="hero-actions">
            <a class="button" href="<?= e(url_for('marketing/pricing')) ?>">Back to pricing</a>
            <a class="button button-secondary" href="<?= e(url_for('marketing/contact')) ?>">Ask a question</a>
        </div>
    </section>
</div>
