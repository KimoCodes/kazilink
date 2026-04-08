    </main>
    <?php $contact = app_config('contact', []); ?>
    <footer class="site-footer">
        <div class="container shell-container">
            <section class="footer-newsletter">
                <div>
                    <span class="eyebrow">Stay in the loop</span>
                    <h2>Useful updates, sent sparingly.</h2>
                    <p class="section-intro">Join the Kazilink list for launch announcements, marketplace improvements, hiring tips, and practical guidance for clients, taskers, and partners.</p>
                </div>
                <form method="post" action="<?= e(url_for('marketing/newsletter')) ?>" class="footer-newsletter-form" novalidate>
                    <?= Csrf::input() ?>
                    <input type="hidden" name="redirect_route" value="<?= e(route_is('marketing/*') ? current_route() : 'home/index') ?>">
                    <div class="sr-only" aria-hidden="true">
                        <label for="newsletter-company-website">Company website</label>
                        <input id="newsletter-company-website" name="company_website" type="text" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="footer-newsletter-fields">
                        <input type="email" name="email" placeholder="Enter your email for updates" autocomplete="email" aria-label="Email address for newsletter">
                        <select name="audience" aria-label="Audience type">
                            <option value="client">Hiring clients</option>
                            <option value="tasker">Taskers</option>
                            <option value="partner">Partners</option>
                        </select>
                        <button type="submit" class="button">Subscribe</button>
                    </div>
                    <p class="muted">No spam. You can review or move subscribers from the local capture file until a full email provider is connected.</p>
                </form>
            </section>

            <div class="site-footer-inner">
                <div class="footer-column footer-brand-column">
                    <a class="brand brand-wrap footer-brand-link" href="<?= e(url_for('home/index')) ?>">
                        <span class="brand-mark">K</span>
                        <span class="brand-copy">
                            <strong><?= e(app_config('name')) ?></strong>
                            <span>Calm tools for trusted local hiring.</span>
                        </span>
                    </a>
                    <p class="muted">Built with plain PHP and a practical marketplace workflow centered on matching, hiring agreements, and evidence capture.</p>
                </div>

                <div class="footer-column">
                    <h3>Platform</h3>
                    <ul class="footer-link-list">
                        <li><a href="<?= e(url_for('home/index')) ?>">Home</a></li>
                        <li><a href="<?= e(url_for('marketing/about')) ?>">About</a></li>
                        <li><a href="<?= e(url_for('marketing/pricing')) ?>">How it works</a></li>
                        <li><a href="<?= e(url_for('marketing/contact')) ?>">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Support</h3>
                    <ul class="footer-link-list">
                        <li><a href="<?= e(url_for('auth/register')) ?>">Create account</a></li>
                        <li><a href="<?= e(url_for('auth/login')) ?>">Log in</a></li>
                        <li><a href="<?= e(url_for('marketing/contact')) ?>">Request help</a></li>
                        <li><a href="<?= e(url_for('marketing/pricing')) ?>">Hiring protection</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Contact</h3>
                    <ul class="footer-link-list footer-contact-list">
                        <li><a href="mailto:<?= e((string) ($contact['email'] ?? 'hello@yourdomain.com')) ?>"><?= e((string) ($contact['email'] ?? 'hello@yourdomain.com')) ?></a></li>
                        <li><a href="tel:<?= e(preg_replace('/\s+/', '', (string) ($contact['phone'] ?? '+250000000000')) ?? '+250000000000') ?>"><?= e((string) ($contact['phone'] ?? '+250 000 000 000')) ?></a></li>
                        <li><?= e((string) ($contact['location'] ?? 'Kigali, Rwanda')) ?></li>
                        <li><?= e((string) ($contact['hours'] ?? 'Mon-Fri, 08:00-18:00 CAT')) ?></li>
                    </ul>
                    <div class="footer-socials">
                        <a href="<?= e((string) ($contact['instagram'] ?? 'https://instagram.com/yourbrand')) ?>" target="_blank" rel="noreferrer">Instagram</a>
                        <a href="<?= e((string) ($contact['linkedin'] ?? 'https://linkedin.com/company/yourbrand')) ?>" target="_blank" rel="noreferrer">LinkedIn</a>
                    </div>
                </div>
            </div>

            <div class="footer-legal">
                <p>&copy; <?= e((string) date('Y')) ?> <?= e(app_config('name')) ?>. Replace placeholder contact and social links before launch.</p>
            </div>
        </div>
    </footer>
    </div>

    <div id="avatar-modal" class="avatar-modal" aria-hidden="true">
        <div class="avatar-modal-dialog">
            <div class="avatar-modal-frame">
                <img id="avatar-modal-image" class="avatar-modal-image" src="" alt="">
                <button type="button" class="avatar-modal-close" data-avatar-modal-close>Close</button>
            </div>
        </div>
    </div>

    <script>
        function openAvatarModal(imageSrc, altText) {
            const modal = document.getElementById('avatar-modal');
            const modalImage = document.getElementById('avatar-modal-image');
            modalImage.src = imageSrc;
            modalImage.alt = altText + ' - Full size';
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeAvatarModal() {
            const modal = document.getElementById('avatar-modal');
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = 'auto';
        }

        document.addEventListener('click', function (event) {
            const target = event.target;

            if (!(target instanceof HTMLElement)) {
                return;
            }

            const trigger = target.closest('[data-avatar-modal-src]');
            if (trigger instanceof HTMLElement) {
                openAvatarModal(trigger.dataset.avatarModalSrc || '', trigger.dataset.avatarModalAlt || 'Profile avatar');
                return;
            }

            const modal = document.getElementById('avatar-modal');
            if (target.matches('[data-avatar-modal-close]') || target === modal || target.classList.contains('avatar-modal-dialog')) {
                closeAvatarModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAvatarModal();
            }
        });
    </script>

    <script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
