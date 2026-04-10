<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
$bidAmountValue = !empty($errors) ? old_value('bid_amount') : '';
$bidMessageValue = !empty($errors) ? old_value('bid_message') : '';
$viewerPlan = is_array($viewerPlan ?? null) ? $viewerPlan : null;
$applicationAccess = is_array($applicationAccess ?? null) ? $applicationAccess : null;
?>
<div class="container">
    <section class="task-detail-layout">
        <article class="panel detail-body">
            <!-- Page Header -->
            <?php
            $title = (string) $task['title'];
            $eyebrow = 'Task Preview';
            $intro = 'Review the job carefully before submitting your bid.';
            $primaryAction = [
                'label' => '← Back to browse',
                'href' => url_for('tasks/browse')
            ];
            unset($secondaryAction, $secondaryLink);
            require BASE_PATH . '/app/views/partials/page_header.php';
            ?>

            <!-- Key Info Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: var(--space-4); margin-bottom: var(--space-8); padding: var(--space-5); background: var(--color-surface-muted); border-radius: var(--radius-lg);">
                <div>
                    <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-2);">Budget</span>
                    <strong style="font-size: 1.25rem; color: var(--color-primary-strong);">
                        <?= e(moneyRwf($task['budget'])) ?>
                    </strong>
                </div>
                <div>
                    <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-2);">Category</span>
                    <div style="color: var(--color-text); font-weight: 500;">
                        <?= e((string) $task['category_name']) ?>
                    </div>
                </div>
                <div>
                    <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-2);">Location</span>
                    <div style="color: var(--color-text);">
                        📍 <?= e((string) $task['city']) ?><?= !empty($task['region']) ? ', ' . e((string) $task['region']) : '' ?>, <?= e((string) $task['country']) ?>
                    </div>
                </div>
                <div>
                    <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-2);">Posted by</span>
                    <div style="color: var(--color-text); font-weight: 500;">
                        👤 <?= e((string) $task['client_name']) ?>
                    </div>
                </div>
            </div>

            <!-- Description Section -->
            <section style="margin-bottom: var(--space-8);">
                <h2 style="margin: 0 0 var(--space-4) 0; color: var(--color-text);">📋 Description</h2>
                <div style="padding: var(--space-5); border-left: 4px solid var(--color-primary); background: linear-gradient(to bottom, var(--color-surface-muted), transparent); border-radius: var(--radius-md); line-height: 1.7; color: var(--color-text);">
                    <?= nl2br(e((string) $task['description'])) ?>
                </div>
            </section>

            <!-- Your Bid Section (Taskers Only) -->
            <?php if (Auth::role() === 'tasker'): ?>
                <section style="margin-bottom: var(--space-8);">
                    <h2 style="margin: 0 0 var(--space-4) 0; color: var(--color-text);">💰 Your Bid</h2>
                    <p style="color: var(--color-text-muted); font-size: var(--font-sm); margin-bottom: var(--space-4);">
                        You can submit one bid per task. Keep the amount and message clear to stand out.
                    </p>

                    <?php if ($viewerPlan !== null && $applicationAccess !== null): ?>
                        <div class="subscription-plan-banner subscription-plan-banner-tight">
                            <div>
                                <span class="sidebar-item-label">Application access</span>
                                <div class="task-summary-metric-row">
                                    <strong><?= e((string) $viewerPlan['name']) ?></strong>
                                    <span><?= e((string) $applicationAccess['remaining']) ?> left today</span>
                                </div>
                            </div>
                            <div class="button-group">
                                <?php if (!empty($viewerPlan['badge_name'])): ?>
                                    <span class="subscription-tier-badge"><?= e((string) $viewerPlan['badge_name']) ?></span>
                                <?php endif; ?>
                                <a class="button button-secondary button-small" href="<?= e(url_for('subscriptions/index')) ?>">Upgrade</a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($existingBid !== null): ?>
                        <!-- Existing Bid Display -->
                        <article style="padding: var(--space-5); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); position: relative;">
                            <!-- Status Badge -->
                            <div style="position: absolute; top: var(--space-4); right: var(--space-4);">
                                <?php $status = (string) $existingBid['status']; $label = ucfirst((string) $existingBid['status']); require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                            </div>

                            <!-- Bid Amount -->
                            <strong style="display: block; font-size: 1.35rem; color: var(--color-primary-strong); margin-bottom: var(--space-3); padding-right: var(--space-8);">
                                <?= e(moneyRwf($existingBid['amount'])) ?>
                            </strong>

                            <!-- Message -->
                            <?php if ($existingBid['message'] !== null && $existingBid['message'] !== ''): ?>
                                <div style="padding: var(--space-3); background: var(--color-surface-muted); border-radius: var(--radius-sm); margin-bottom: var(--space-3); line-height: 1.6;">
                                    <?= nl2br(e((string) $existingBid['message'])) ?>
                                </div>
                            <?php else: ?>
                                <p style="color: var(--color-text-muted); font-style: italic; margin: 0;">No message included with this bid.</p>
                            <?php endif; ?>
                        </article>
                    <?php else: ?>
                        <!-- Bid Form -->
                        <div style="padding: var(--space-5); background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                            <form method="post" action="<?= e(url_for('bids/create')) ?>" class="form-grid" novalidate>
                                <?= Csrf::input() ?>
                                <input type="hidden" name="task_id" value="<?= e((string) $task['id']) ?>">

                                <?php
                                $name = 'amount';
                                $label = 'Bid amount (RWF)';
                                $as = 'input';
                                $type = 'number';
                                $value = $bidAmountValue;
                                $placeholder = '15000';
                                $autocomplete = null;
                                $required = true;
                                $hint = 'Set a realistic total amount in RWF for the full task.';
                                $error = field_error($fieldErrors, 'amount');
                                $attrs = ['step' => '1000', 'min' => '1000', 'inputmode' => 'numeric'];
                                require BASE_PATH . '/app/views/partials/form_field.php';
                                ?>

                                <?php
                                $name = 'message';
                                $label = 'Message';
                                $as = 'textarea';
                                $type = 'text';
                                $value = $bidMessageValue;
                                $placeholder = 'Share when you are available, your approach, or why you are the best fit.';
                                $autocomplete = null;
                                $required = false;
                                $hint = 'A thoughtful message often matters as much as the price.';
                                $error = field_error($fieldErrors, 'message');
                                $attrs = ['maxlength' => '2000', 'rows' => '5'];
                                require BASE_PATH . '/app/views/partials/form_field.php';
                                ?>

                                <button type="submit" class="button button-block">Submit your bid</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        </article>

        <!-- Sidebar -->
        <aside style="display: flex; flex-direction: column; gap: var(--space-6);">
            <div class="sidebar-card">
                <span style="display: block; font-size: var(--font-xs); text-transform: uppercase; color: var(--color-text-muted); margin-bottom: var(--space-3);">Quick Info</span>
                <div style="display: grid; grid-template-columns: auto 1fr; gap: var(--space-2) var(--space-3); align-items: start; font-size: var(--font-sm);">
                    <span style="color: var(--color-text-muted); text-transform: uppercase;">Status</span>
                    <div><?php $status = 'open'; $label = 'Open'; require BASE_PATH . '/app/views/partials/status-badge.php'; ?></div>

                    <span style="color: var(--color-text-muted); text-transform: uppercase;">Scheduled</span>
                    <div style="color: var(--color-text);">
                        📅 <?= e(format_datetime($task['scheduled_for'])) ?>
                    </div>
                </div>
            </div>

            <!-- Back Button -->
            <a class="button button-secondary" style="width: 100%;" href="<?= e(url_for('tasks/browse')) ?>">
                ← Back to browse
            </a>
        </aside>
    </section>
</div>
