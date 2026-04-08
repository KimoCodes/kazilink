<?php
$messages = is_array($messages ?? null) ? $messages : [];
$showFilter = ($showFilter ?? 'all') === 'unreplied' ? 'unreplied' : 'all';
$messageCount = count($messages);
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Contact Messages';
        $eyebrow = 'Admin';
        $intro = 'Review captured support messages and send replies directly from the admin workspace.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Captured messages</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) $messageCount) ?></strong>
                    <span><?= $showFilter === 'unreplied' ? 'Messages still waiting on a first admin reply' : 'Messages stored locally from the public contact form' ?></span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Reply method</span>
                <div class="task-summary-metric-row">
                    <strong>Email + local log</strong>
                    <span>Replies are recorded locally and sent if local mail is configured</span>
                </div>
            </article>
        </div>

        <div class="table-toolbar">
            <p>Filter the inbox to focus on new contact requests or review the full history.</p>
            <div class="hero-actions">
                <a class="button <?= $showFilter === 'all' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/messages')) ?>">All messages</a>
                <a class="button <?= $showFilter === 'unreplied' ? '' : 'button-secondary' ?>" href="<?= e(url_for('admin/messages', ['show' => 'unreplied'])) ?>">Unreplied only</a>
            </div>
        </div>

        <?php if ($messages === []): ?>
            <?php
            $emptyIcon = '✉️';
            $emptyTitle = $showFilter === 'unreplied' ? 'No unreplied messages' : 'No contact messages yet';
            $emptyMessage = $showFilter === 'unreplied'
                ? 'Every captured contact request already has at least one admin reply recorded.'
                : 'New public support requests will appear here after someone submits the contact form.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($messages as $message): ?>
                    <?php
                    $payload = is_array($message['payload'] ?? null) ? $message['payload'] : [];
                    $replies = is_array($message['replies'] ?? null) ? $message['replies'] : [];
                    $replySubject = trim((string) ($payload['topic'] ?? ''));
                    $statusMeta = LeadCapture::deliveryStatusMeta((string) ($message['latest_reply_status'] ?? 'not_replied'));
                    $replyCount = (int) ($message['reply_count'] ?? count($replies));
                    ?>
                    <article class="panel panel-subtle">
                        <div class="section-head">
                            <div>
                                <h2><?= e((string) ($payload['topic'] ?: 'Untitled message')) ?></h2>
                                <p class="section-intro">
                                    From <strong><?= e((string) ($payload['name'] ?: 'Unknown sender')) ?></strong>
                                    at <strong><?= e((string) ($payload['email'] ?: 'No email')) ?></strong>
                                    on <?= e(dateFmt((string) ($message['recorded_at'] ?? ''))) ?>
                                </p>
                            </div>
                            <div class="hero-actions">
                                <span class="pill <?= e((string) $statusMeta['pill_class']) ?>"><?= e((string) $statusMeta['label']) ?></span>
                                <span class="pill pill-info"><?= e((string) $replyCount) ?> repl<?= $replyCount === 1 ? 'y' : 'ies' ?></span>
                            </div>
                        </div>

                        <div class="summary-grid">
                            <article class="sidebar-card">
                                <span class="sidebar-item-label">Company</span>
                                <strong><?= e((string) ($payload['company'] ?: 'Not provided')) ?></strong>
                            </article>
                            <article class="sidebar-card">
                                <span class="sidebar-item-label">Route</span>
                                <strong><?= e((string) ($message['route'] ?: 'marketing/contact')) ?></strong>
                            </article>
                            <article class="sidebar-card">
                                <span class="sidebar-item-label">Stored at</span>
                                <strong><?= e(basename((string) ($message['source_file'] ?? 'capture'))) ?>:<?= e((string) ($message['source_line'] ?? 0)) ?></strong>
                            </article>
                            <article class="sidebar-card">
                                <span class="sidebar-item-label">Reply status</span>
                                <strong><?= e((string) $statusMeta['label']) ?></strong>
                                <div class="text-muted"><?= e((string) $statusMeta['description']) ?></div>
                            </article>
                        </div>

                        <div class="sidebar-card">
                            <span class="sidebar-item-label">Message</span>
                            <div class="detail-copy"><?= nl2br(e((string) ($payload['message'] ?? ''))) ?></div>
                        </div>

                        <?php if ($replies !== []): ?>
                            <div class="table-wrap">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>When</th>
                                            <th>Admin</th>
                                            <th>Delivery</th>
                                            <th>Reply</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($replies as $reply): ?>
                                            <?php
                                            $replyPayload = is_array($reply['payload'] ?? null) ? $reply['payload'] : [];
                                            $replyStatusMeta = LeadCapture::deliveryStatusMeta((string) ($replyPayload['delivery_status'] ?? 'logged_only'));
                                            ?>
                                            <tr>
                                                <td><?= e(dateFmt((string) ($reply['recorded_at'] ?? ''))) ?></td>
                                                <td><?= e((string) ($replyPayload['admin_name'] ?? 'Admin')) ?></td>
                                                <td><span class="pill <?= e((string) $replyStatusMeta['pill_class']) ?>"><?= e((string) $replyStatusMeta['label']) ?></span></td>
                                                <td><?= e(mb_strimwidth((string) ($replyPayload['message'] ?? ''), 0, 120, '...')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="<?= e(url_for('admin/reply-message')) ?>" class="form-grid" novalidate>
                            <?= Csrf::input() ?>
                            <input type="hidden" name="message_id" value="<?= e((string) ($message['id'] ?? '')) ?>">
                            <input type="hidden" name="to_email" value="<?= e((string) ($payload['email'] ?? '')) ?>">
                            <input type="hidden" name="subject" value="<?= e($replySubject) ?>">
                            <input type="hidden" name="show_filter" value="<?= e($showFilter) ?>">

                            <?php
                            $name = 'reply_body';
                            $label = 'Reply';
                            $as = 'textarea';
                            $value = '';
                            $placeholder = 'Write the reply you want this contact to receive.';
                            $required = true;
                            $hint = 'The reply is stored locally and sent by email when local mail delivery is configured.';
                            $error = null;
                            $attrs = ['maxlength' => '4000', 'rows' => '5'];
                            require BASE_PATH . '/app/views/partials/form_field.php';
                            ?>

                            <div class="form-actions">
                                <button type="submit" class="button">Send reply</button>
                            </div>
                        </form>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
