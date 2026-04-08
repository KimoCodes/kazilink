<?php
$platformName = (string) app_config('name', 'the platform');
$status = (string) ($dispute['status'] ?? 'open');
$statusLabel = ucfirst(str_replace('_', ' ', $status));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $dispute['agreement_uid']) ?> | Issue Record</title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; color: #1e293b; margin: 0; background: #f8fafc; }
        .page { max-width: 900px; margin: 0 auto; padding: 32px 24px 80px; background: #fff; }
        .toolbar { display: flex; gap: 12px; justify-content: flex-end; margin-bottom: 24px; }
        .toolbar a, .toolbar button { border: 1px solid #0f766e; background: #0f766e; color: #fff; padding: 10px 16px; border-radius: 999px; cursor: pointer; text-decoration: none; font: inherit; }
        h1, h2 { margin-bottom: 12px; color: #0f172a; }
        p { line-height: 1.6; }
        .document-head { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; border-bottom: 2px solid #cbd5e1; padding-bottom: 20px; margin-bottom: 24px; }
        .document-badge { border: 1px solid #b45309; color: #b45309; border-radius: 999px; padding: 8px 14px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; }
        .meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .meta-card { border: 1px solid #cbd5e1; border-radius: 14px; padding: 16px; }
        .small-label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #475569; margin-bottom: 6px; }
        .section { margin-top: 28px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { padding: 0; max-width: none; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="toolbar">
            <a href="<?= e(url_for('disputes/show', ['id' => (int) $dispute['id']])) ?>">Back to issue</a>
            <button type="button" onclick="window.print()">Download PDF</button>
        </div>

        <div class="document-head">
            <div>
                <h1>Issue Record</h1>
                <p>This document records an issue reported through <?= e($platformName) ?> in connection with hiring agreement <?= e((string) $dispute['agreement_uid']) ?>.</p>
            </div>
            <div class="document-badge"><?= e($statusLabel) ?></div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <span class="small-label">Agreement UID</span>
                <strong><?= e((string) $dispute['agreement_uid']) ?></strong>
            </div>
            <div class="meta-card">
                <span class="small-label">Issue type</span>
                <strong><?= e(dispute_type_label((string) $dispute['type'])) ?></strong>
            </div>
            <div class="meta-card">
                <span class="small-label">Reported by</span>
                <strong><?= e((string) ($dispute['reporter_name'] ?: $dispute['reporter_email'])) ?></strong>
            </div>
            <div class="meta-card">
                <span class="small-label">Reported at</span>
                <strong><?= e(dateFmt((string) $dispute['created_at'])) ?></strong>
            </div>
        </div>

        <div class="section">
            <h2>Job Context</h2>
            <p><strong>Job:</strong> <?= e((string) $dispute['job_title']) ?></p>
            <p><strong>Agreement status:</strong> <?= e(agreement_status_label((string) $dispute['agreement_status'])) ?></p>
        </div>

        <div class="section">
            <h2>Issue Description</h2>
            <p><?= nl2br(e((string) $dispute['description'])) ?></p>
        </div>

        <?php if (!empty($dispute['admin_notes']) || !empty($dispute['admin_updated_by_name']) || !empty($dispute['resolved_at'])): ?>
            <div class="section">
                <h2>Admin Record</h2>
                <?php if (!empty($dispute['admin_notes'])): ?>
                    <p><strong>Admin notes:</strong><br><?= nl2br(e((string) $dispute['admin_notes'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($dispute['admin_updated_by_name'])): ?>
                    <p><strong>Last updated by:</strong> <?= e((string) $dispute['admin_updated_by_name']) ?></p>
                <?php endif; ?>
                <?php if (!empty($dispute['resolved_at'])): ?>
                    <p><strong>Closed at:</strong> <?= e(dateFmt((string) $dispute['resolved_at'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
