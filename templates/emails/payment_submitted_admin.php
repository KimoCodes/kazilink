<?php

$title = (string) ($data['subject'] ?? 'Payment submitted for review');
$planName = (string) ($data['plan_name'] ?? 'Plan');
$amount = (string) ($data['amount'] ?? '');
$submittedAt = (string) ($data['submitted_at'] ?? '');
$deadline = (string) ($data['deadline_at'] ?? '');
$activation = (string) ($data['intended_activation_at'] ?? '');
$userName = (string) ($data['user_name'] ?? 'User');
$reviewLink = (string) ($data['admin_review_link'] ?? '');
?>
<!doctype html>
<html lang="en">
<body style="margin:0;padding:0;background:#f6f1e7;font-family:Arial,sans-serif;color:#1f1a17;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#ffffff;border:1px solid #e6dccd;border-radius:16px;padding:32px;">
      <p style="margin:0 0 8px;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#2f6b2f;font-weight:700;"><?= e($data['platform_name']); ?></p>
      <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;"><?= e($title); ?></h1>
      <p style="margin:0 0 20px;font-size:16px;line-height:1.6;"><?= e($userName); ?> submitted a manual payment proof that needs admin review.</p>
      <table style="width:100%;border-collapse:collapse;margin:0 0 24px;">
        <tr><td style="padding:8px 0;font-weight:700;">Plan</td><td style="padding:8px 0;"><?= e($planName); ?></td></tr>
        <tr><td style="padding:8px 0;font-weight:700;">Amount</td><td style="padding:8px 0;"><?= e($amount); ?></td></tr>
        <tr><td style="padding:8px 0;font-weight:700;">Submitted</td><td style="padding:8px 0;"><?= e($submittedAt); ?></td></tr>
        <tr><td style="padding:8px 0;font-weight:700;">Deadline</td><td style="padding:8px 0;"><?= e($deadline); ?></td></tr>
        <tr><td style="padding:8px 0;font-weight:700;">Activation</td><td style="padding:8px 0;"><?= e($activation); ?></td></tr>
      </table>
      <?php if ($reviewLink !== ''): ?>
        <p style="margin:0 0 24px;"><a href="<?= e($reviewLink); ?>" style="display:inline-block;background:#1f1a17;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:700;">Review Payment</a></p>
      <?php endif; ?>
      <p style="margin:0;font-size:14px;line-height:1.6;color:#5c5146;">Support: <?= e($data['support_email']); ?><?php if ((string) $data['support_phone'] !== ''): ?> | <?= e($data['support_phone']); ?><?php endif; ?></p>
    </div>
  </div>
</body>
</html>
