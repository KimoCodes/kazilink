<?php
/**
 * status_badge.php
 *
 * Renders a styled status badge. Supports shorthand or explicit label.
 *
 * Variables:
 *   - $status (string, required): Status value (e.g., 'open', 'booked', 'completed', 'pending')
 *   - $label (string, optional): Custom display label. Defaults to ucfirst($status)
 *
 * Usage (shorthand):
 *   <?= partial('status_badge', ['status' => 'open']) ?>
 *
 * Usage (with custom label):
 *   <span><?php $status = 'pending'; $label = 'Awaiting approval'; require BASE_PATH . '/app/views/partials/status-badge.php'; ?></span>
 */

$statusValue = strtolower(trim((string) ($status ?? 'neutral')));
$statusLabel = (string) ($label ?? ucfirst($statusValue));
$statusClass = preg_replace('/[^a-z0-9_-]+/', '-', $statusValue) ?: 'neutral';
?>
<span class="badge badge-<?= e($statusClass) ?>"><?= e($statusLabel) ?></span>
