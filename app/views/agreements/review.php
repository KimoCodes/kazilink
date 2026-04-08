<?php
$agreementStatus = (string) $agreement['status'];
$status = $agreementStatus;
$label = agreement_status_label($agreementStatus);
$isClient = (int) Auth::id() === (int) $agreement['client_user_id'];
$isTasker = (int) Auth::id() === (int) $agreement['tasker_user_id'];
$viewerCanAccept = ($isClient && empty($agreement['client_accepted_at'])) || ($isTasker && empty($agreement['tasker_accepted_at']));
$canOpenDispute = in_array((string) Auth::role(), ['client', 'tasker', 'admin'], true);
$acceptanceDeadline = !empty($agreement['start_datetime'])
    ? dateFmt(date('Y-m-d H:i:s', strtotime((string) $agreement['start_datetime'] . ' +' . (int) $agreement['dispute_window_hours'] . ' hours')))
    : '48 hours from the recorded issue';
$signatureEvents = array_values(array_filter($events, static fn (array $event): bool => (string) ($event['event_type'] ?? '') === 'agreement_accepted'));
?>
<div class="container">
    <section class="booking-layout">
        <article class="panel detail-body">
            <?php
            $title = (string) $agreement['job_title'];
            $eyebrow = 'Hiring Agreement';
            $intro = 'This record confirms the hire, makes offline payment expectations explicit, and preserves an auditable timeline if anything goes wrong.';
            $primaryAction = ['label' => 'Open booking', 'href' => url_for('bookings/show', ['id' => (int) $agreement['booking_id']])];
            $secondaryLink = ['label' => 'Back to bookings', 'href' => url_for('bookings/index')];
            unset($secondaryAction);
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <div class="detail-facts">
                <div class="detail-fact">
                    <span class="detail-fact-label">Agreement UID</span>
                    <div class="detail-fact-value"><code class="entity-chip text-mono"><?= e((string) $agreement['agreement_uid']) ?></code></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Issue date</span>
                    <div class="detail-fact-value"><?= e(dateFmt((string) $agreement['created_at'])) ?></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Status</span>
                    <div class="detail-fact-value"><?php require BASE_PATH . '/app/views/partials/status-badge.php'; ?></div>
                </div>
                <div class="detail-fact">
                    <span class="detail-fact-label">Compensation base</span>
                    <div class="detail-fact-value"><span class="price"><?= e(moneyRwf((float) ($agreement['agreed_amount'] ?? 0))) ?></span></div>
                </div>
            </div>

            <div class="summary-grid">
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Client</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $agreement['client_name']) ?></strong>
                        <span><?= e((string) ($agreement['client_phone'] ?: $agreement['client_email'])) ?></span>
                    </div>
                </article>
                <article class="info-card task-summary-card">
                    <span class="sidebar-item-label">Tasker</span>
                    <div class="task-summary-metric-row">
                        <strong><?= e((string) $agreement['tasker_name']) ?></strong>
                        <span><?= e((string) ($agreement['tasker_phone'] ?: $agreement['tasker_email'])) ?></span>
                    </div>
                </article>
            </div>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Job details</h2>
                        <p class="section-intro">The agreement captures the exact work that was hired through the platform.</p>
                    </div>
                </div>
                <ul class="summary-list booking-card-summary">
                    <li><strong>Category</strong><span><?= e((string) $agreement['category']) ?></span></li>
                    <li><strong>Location</strong><span><?= e((string) $agreement['location_text']) ?></span></li>
                    <li><strong>Start date</strong><span><?= e(dateFmt((string) $agreement['start_datetime'])) ?></span></li>
                    <li><strong>Expected duration</strong><span><?= e((string) ($agreement['expected_duration'] ?: 'Not specified')) ?></span></li>
                </ul>
                <div class="detail-copy">
                    <?= nl2br(e((string) $agreement['job_description'])) ?>
                </div>
            </section>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Offline payment and compensation terms</h2>
                        <p class="section-intro">Payment happens directly between the client and tasker. The platform only keeps the hiring record and evidence trail.</p>
                    </div>
                </div>
                <div class="detail-copy">
                        <p><strong>Offline payment.</strong> <?= nl2br(e(normalize_offline_terms_text((string) $agreement['offline_payment_terms_text']))) ?></p>
                        <p><strong>Compensation if access fails or scope changes.</strong> <?= nl2br(e((string) $agreement['compensation_terms_text'])) ?></p>
                        <p><strong>Cancellation and no-show.</strong> <?= nl2br(e((string) $agreement['cancellation_terms_text'])) ?></p>
                    <p><strong>Dispute window.</strong> Issues must be reported within <?= e((string) $agreement['dispute_window_hours']) ?> hours. Current reference deadline: <?= e($acceptanceDeadline) ?>.</p>
                        <p><strong>Platform role.</strong> <?= e(app_config('name')) ?> is a facilitator and record-keeper only. It is not the employer, principal contractor, or payment processor for this hire.</p>
                </div>
            </section>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Digital signatures</h2>
                        <p class="section-intro">Acceptance timestamps, IP address, and user agent are stored as evidence once each party confirms.</p>
                    </div>
                </div>

                <div class="summary-grid">
                    <article class="info-card task-summary-card">
                        <span class="sidebar-item-label">Client acceptance</span>
                        <div class="task-summary-metric-row">
                            <strong><?= !empty($agreement['client_accepted_at']) ? e(dateFmt((string) $agreement['client_accepted_at'])) : 'Pending' ?></strong>
                            <span><?= e((string) $agreement['client_name']) ?></span>
                        </div>
                    </article>
                    <article class="info-card task-summary-card">
                        <span class="sidebar-item-label">Tasker acceptance</span>
                        <div class="task-summary-metric-row">
                            <strong><?= !empty($agreement['tasker_accepted_at']) ? e(dateFmt((string) $agreement['tasker_accepted_at'])) : 'Pending' ?></strong>
                            <span><?= e((string) $agreement['tasker_name']) ?></span>
                        </div>
                    </article>
                </div>

                <?php if ($signatureEvents !== []): ?>
                    <div class="card-list">
                        <?php foreach ($signatureEvents as $event): ?>
                            <?php $decoded = json_decode((string) ($event['event_json'] ?? ''), true); $decoded = is_array($decoded) ? $decoded : []; ?>
                            <article class="info-card">
                                <h3><?= e(ucfirst((string) ($decoded['accepted_by'] ?? 'party'))) ?> signature evidence</h3>
                                <p class="muted">Accepted at <?= e(dateFmt((string) ($decoded['accepted_at'] ?? $event['created_at']))) ?></p>
                                <p><strong>IP:</strong> <?= e((string) ($decoded['ip_address'] ?? 'unknown')) ?></p>
                                <p><strong>User agent:</strong> <?= e((string) ($decoded['user_agent'] ?? 'unknown')) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($viewerCanAccept): ?>
                    <form method="post" action="<?= e(url_for('agreements/accept')) ?>" class="stack-form">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="agreement_id" value="<?= e((string) $agreement['id']) ?>">
                        <label class="checkbox-row">
                            <input type="checkbox" name="confirm_offline_payment" value="1" <?= old_value('confirm_offline_payment') === '1' ? 'checked' : '' ?>>
                            <span>I understand payment is offline and the platform does not collect or hold funds.</span>
                        </label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="confirm_scope" value="1" <?= old_value('confirm_scope') === '1' ? 'checked' : '' ?>>
                            <span>I confirm the job scope, compensation rules, cancellation terms, and dispute process recorded here.</span>
                        </label>
                        <button type="submit" class="button">Accept Agreement</button>
                    </form>
                <?php endif; ?>

                <?php if ($agreementStatus === 'accepted'): ?>
                    <div class="button-group">
                        <a class="button" href="<?= e(url_for('agreements/download', ['id' => (int) $agreement['id']])) ?>">Download PDF</a>
                        <a class="button button-secondary" href="<?= e(url_for('agreements/verify', ['agreement_uid' => (string) $agreement['agreement_uid']])) ?>">Verify Agreement</a>
                    </div>
                    <p class="muted">Download opens a print-ready agreement page. Use your browser's Print to PDF option to save a copy.</p>
                <?php else: ?>
                    <p class="muted">Download and public verification unlock after both parties accept the agreement.</p>
                <?php endif; ?>
            </section>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Issue reporting</h2>
                        <p class="section-intro">Use this form to preserve a platform timestamp if payment fails, access is blocked, the tasker does not show up, or the scope changes.</p>
                    </div>
                </div>

                <?php if ($canOpenDispute): ?>
                    <form method="post" action="<?= e(url_for('disputes/create')) ?>" class="stack-form">
                        <?= Csrf::input() ?>
                        <input type="hidden" name="agreement_id" value="<?= e((string) $agreement['id']) ?>">
                        <div class="form-row">
                            <label for="type">Issue type</label>
                            <select id="type" name="type">
                                <?php foreach (['non_payment', 'client_unavailable', 'tasker_no_show', 'scope_change', 'unsafe', 'other'] as $typeOption): ?>
                                    <option value="<?= e($typeOption) ?>" <?= old_value('type') === $typeOption ? 'selected' : '' ?>><?= e(dispute_type_label($typeOption)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="description">Describe what happened</label>
                            <textarea id="description" name="description" rows="5" placeholder="Include arrival time, access issues, payment promises, scope changes, safety concerns, or other useful evidence."><?= old('description') ?></textarea>
                        </div>
                        <button type="submit" class="button button-warning">Report an Issue</button>
                    </form>
                <?php endif; ?>

                <?php if ($disputes === []): ?>
                    <p class="muted">No issues have been reported on this agreement.</p>
                <?php else: ?>
                    <div class="card-list">
                        <?php foreach ($disputes as $dispute): ?>
                            <?php $status = (string) $dispute['status']; $label = ucfirst(str_replace('_', ' ', $status)); ?>
                            <article class="review-card">
                                <div class="card-header">
                                    <div>
                                        <h3><?= e(dispute_type_label((string) $dispute['type'])) ?></h3>
                                        <p class="review-meta">
                                            <span>Reported by <?= e((string) ($dispute['reporter_name'] ?: $dispute['reporter_email'])) ?></span>
                                            <span>•</span>
                                            <span><?= e(dateFmt((string) $dispute['created_at'])) ?></span>
                                        </p>
                                    </div>
                                    <?php require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                                </div>
                                <p><?= nl2br(e((string) $dispute['description'])) ?></p>
                                <a class="button button-secondary button-small" href="<?= e(url_for('disputes/show', ['id' => (int) $dispute['id']])) ?>">View issue record</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="booking-section">
                <div class="section-head">
                    <div>
                        <h2>Agreement log</h2>
                        <p class="section-intro">Every acceptance and dispute event is retained as an audit trail.</p>
                    </div>
                </div>
                <?php if ($events === []): ?>
                    <p class="muted">No agreement events recorded yet.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>When</th>
                                    <th>Actor</th>
                                    <th>Event</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td><?= e(dateFmt((string) $event['created_at'])) ?></td>
                                        <td><?= e((string) ($event['actor_name'] ?: $event['actor_email'] ?: 'System')) ?></td>
                                        <td><?= e(ucfirst(str_replace('_', ' ', (string) $event['event_type']))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </article>

        <aside class="sidebar-stack">
            <div class="sidebar-card">
                <span class="sidebar-item-label">Quick actions</span>
                <div class="button-group button-group-column">
                    <a class="button button-secondary button-small" href="<?= e(url_for('bookings/show', ['id' => (int) $agreement['booking_id']])) ?>">Open booking</a>
                    <a class="button button-secondary button-small" href="<?= e(url_for('messages/thread', ['id' => (int) $agreement['booking_id']])) ?>">Open messages</a>
                    <a class="button button-secondary button-small" href="<?= e(url_for('agreements/verify', ['agreement_uid' => (string) $agreement['agreement_uid']])) ?>">Verify public record</a>
                </div>
            </div>
        </aside>
    </section>
</div>
