<div class="container">
    <section class="panel hero-surface">
        <div class="hero">
            <span class="eyebrow">About Kazilink</span>
            <h1>A cleaner, calmer way to coordinate local services.</h1>
            <p class="page-intro">Kazilink is for people who want a service marketplace to feel professional from the first click: clear navigation, practical copy, consistent styling, reliable task flows, and a direct way to pay when they are ready.</p>
            <div class="hero-actions">
                <a class="button" href="<?= e(url_for('marketing/pricing')) ?>">See pricing</a>
                <a class="button button-secondary" href="<?= e(url_for('marketing/contact')) ?>">Talk to us</a>
            </div>
        </div>
    </section>

    <section class="panel panel-subtle">
        <div class="section-head">
            <div>
                <span class="eyebrow">Experience principles</span>
                <h2>What the redesign is optimizing for</h2>
            </div>
        </div>
        <div class="marketing-grid marketing-grid-three">
            <article class="feature-card">
                <h3>Clarity before conversion</h3>
                <p class="muted">The product now answers what it is, how it works, how much it costs, and who to contact without making people hunt for basics.</p>
            </article>
            <article class="feature-card">
                <h3>Consistency across pages</h3>
                <p class="muted">Shared tokens, repeated card patterns, and stronger page structure make the site feel like one product instead of disconnected screens.</p>
            </article>
            <article class="feature-card">
                <h3>Payment without improvisation</h3>
                <p class="muted">Stripe Checkout keeps card entry on a hosted surface while your app owns the surrounding plan selection and post-payment messaging.</p>
            </article>
        </div>
    </section>

    <section class="panel">
        <div class="section-head">
            <div>
                <span class="eyebrow">Operational flow</span>
                <h2>From first visit to successful follow-up</h2>
            </div>
        </div>
        <div class="timeline-list">
            <article class="timeline-item">
                <span class="timeline-step">1</span>
                <div>
                    <h3>Understand the offer</h3>
                    <p class="muted">Visitors land on a homepage that explains value, service categories, pricing, and support in a clear sequence.</p>
                </div>
            </article>
            <article class="timeline-item">
                <span class="timeline-step">2</span>
                <div>
                    <h3>Choose the right path</h3>
                    <p class="muted">They can create an account, browse tasks, ask for help, or move directly to pricing based on their intent.</p>
                </div>
            </article>
            <article class="timeline-item">
                <span class="timeline-step">3</span>
                <div>
                    <h3>Coordinate confidently</h3>
                    <p class="muted">Once inside the app, the existing task, bid, booking, and messaging flows stay available and easier to navigate around.</p>
                </div>
            </article>
        </div>
    </section>

    <section class="panel panel-subtle">
        <div class="section-head">
            <div>
                <span class="eyebrow">Frequently asked</span>
                <h2>Common questions from households and teams</h2>
            </div>
        </div>
        <div class="faq-list">
            <details class="faq-item">
                <summary>Is this a full custom build or a marketplace MVP?</summary>
                <p>It remains a focused marketplace MVP, but the marketing layer and payment path now feel more launch-ready and professional.</p>
            </details>
            <details class="faq-item">
                <summary>Can I change the plans later?</summary>
                <p>Yes. Plan names, prices, and descriptions live in helper configuration so they can be updated without reworking the page structure.</p>
            </details>
            <details class="faq-item">
                <summary>What happens after a successful payment?</summary>
                <p>The user lands on a success page that can verify the Checkout session. The next production step is typically webhook-based fulfillment.</p>
            </details>
        </div>
    </section>
</div>
