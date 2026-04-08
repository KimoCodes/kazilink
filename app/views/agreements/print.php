<?php
$decodedEvents = [];

foreach ($events as $event) {
    $decoded = json_decode((string) ($event['event_json'] ?? ''), true);
    $decodedEvents[] = is_array($decoded) ? array_merge($event, ['decoded' => $decoded]) : array_merge($event, ['decoded' => []]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $agreement['agreement_uid']) ?> | Hiring Agreement</title>
    <style>
        body { font-family: Georgia, "Times New Roman", serif; color: #1e293b; margin: 0; background: #f8fafc; }
        .page { max-width: 900px; margin: 0 auto; padding: 32px 24px 80px; background: #fff; }
        .toolbar { display: flex; gap: 12px; justify-content: flex-end; margin-bottom: 24px; }
        .toolbar a, .toolbar button { border: 1px solid #0f766e; background: #0f766e; color: #fff; padding: 10px 16px; border-radius: 999px; cursor: pointer; text-decoration: none; font: inherit; }
        h1, h2, h3 { margin-bottom: 12px; color: #0f172a; }
        p, li { line-height: 1.6; }
        .document-head { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; border-bottom: 2px solid #cbd5e1; padding-bottom: 20px; margin-bottom: 24px; }
        .document-badge { border: 1px solid #0f766e; color: #0f766e; border-radius: 999px; padding: 8px 14px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; }
        .meta-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .meta-card { border: 1px solid #cbd5e1; border-radius: 14px; padding: 16px; }
        .small-label { display: block; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #475569; margin-bottom: 6px; }
        .section { margin-top: 28px; }
        .signature-box { border: 1px solid #cbd5e1; border-radius: 14px; padding: 16px; margin-top: 12px; }
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
            <a href="<?= e(url_for('agreements/review', ['id' => (int) $agreement['id']])) ?>">Back to agreement</a>
            <button type="button" onclick="window.print()">Download PDF</button>
        </div>

        <div class="document-head">
            <div>
                <h1>Hiring Agreement</h1>
                <p>This agreement records that the tasker was hired through <?= e(app_config('name')) ?>. Payment is arranged offline between the parties. The platform keeps this document and the related event log as evidence of the hire.</p>
            </div>
            <div class="document-badge">Verified platform hiring record</div>
        </div>

        <div class="meta-grid">
            <div class="meta-card">
                <span class="small-label">Agreement UID</span>
                <strong><?= e((string) $agreement['agreement_uid']) ?></strong>
            </div>
            <div class="meta-card">
                <span class="small-label">Issue date</span>
                <strong><?= e(dateFmt((string) $agreement['created_at'])) ?></strong>
            </div>
            <div class="meta-card">
                <span class="small-label">Client</span>
                <strong><?= e((string) $agreement['client_name']) ?></strong><br>
                <span><?= e((string) ($agreement['client_phone'] ?: 'No phone added')) ?></span><br>
                <span><?= e((string) $agreement['client_email']) ?></span>
            </div>
            <div class="meta-card">
                <span class="small-label">Tasker</span>
                <strong><?= e((string) $agreement['tasker_name']) ?></strong><br>
                <span><?= e((string) ($agreement['tasker_phone'] ?: 'No phone added')) ?></span><br>
                <span><?= e((string) $agreement['tasker_email']) ?></span>
            </div>
        </div>

        <div class="section">
            <h2>Job Nature</h2>
            <p><strong>Title:</strong> <?= e((string) $agreement['job_title']) ?></p>
            <p><strong>Category:</strong> <?= e((string) $agreement['category']) ?></p>
            <p><strong>Location:</strong> <?= e((string) $agreement['location_text']) ?></p>
            <p><strong>Start date/time:</strong> <?= e(dateFmt((string) $agreement['start_datetime'])) ?></p>
            <p><strong>Expected duration:</strong> <?= e((string) ($agreement['expected_duration'] ?: 'Not specified')) ?></p>
            <p><strong>Description:</strong><br><?= nl2br(e((string) $agreement['job_description'])) ?></p>
        </div>

        <div class="section">
            <h2>Payment and Compensation Rules</h2>
            <p><strong>Offline payment statement.</strong> <?= nl2br(e(normalize_offline_terms_text((string) $agreement['offline_payment_terms_text']))) ?></p>
            <p><strong>Compensation if the client is unavailable or access fails.</strong> <?= nl2br(e((string) $agreement['compensation_terms_text'])) ?></p>
            <p><strong>No-show and cancellation.</strong> <?= nl2br(e((string) $agreement['cancellation_terms_text'])) ?></p>
            <p><strong>Non-payment.</strong> If the tasker is not paid as agreed, the tasker may open a dispute on the platform. This agreement and the stored event logs act as evidence that the hire was made through the platform.</p>
            <p><strong>Dispute process.</strong> A dispute should be reported within <?= e((string) $agreement['dispute_window_hours']) ?> hours. The platform stores acceptance timestamps, IP address, user agent, dispute submissions, and related log events as record-keeping evidence.</p>
            <p><strong>Platform disclaimer.</strong> <?= e(app_config('name')) ?> is a facilitator and record-keeper. It is not the employer, labor broker, insurer, guarantor, or payment processor for this engagement.</p>
        </div>

        <div class="section">
            <h2>Digital Signatures</h2>
            <?php foreach ($decodedEvents as $event): ?>
                <?php if ((string) $event['event_type'] !== 'agreement_accepted') { continue; } ?>
                <div class="signature-box">
                    <h3><?= e(ucfirst((string) ($event['decoded']['accepted_by'] ?? 'party'))) ?> acceptance</h3>
                    <p><strong>Accepted at:</strong> <?= e(dateFmt((string) ($event['decoded']['accepted_at'] ?? $event['created_at']))) ?></p>
                    <p><strong>IP address:</strong> <?= e((string) ($event['decoded']['ip_address'] ?? 'unknown')) ?></p>
                    <p><strong>User agent:</strong> <?= e((string) ($event['decoded']['user_agent'] ?? 'unknown')) ?></p>
                    <p><strong>Checkbox confirmations:</strong> Offline payment acknowledged and scope/dispute terms confirmed.</p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
