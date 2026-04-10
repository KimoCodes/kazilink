<?php

$title = (string) ($data['subject'] ?? 'Kazilink notification');
$body = nl2br(e((string) ($data['message'] ?? '')));
$link = (string) ($data['link_url'] ?? '');
$linkLabel = (string) ($data['link_label'] ?? 'Open notification');
?>
<!doctype html>
<html lang="en">
<body style="margin:0;padding:0;background:#f6f1e7;font-family:Arial,sans-serif;color:#1f1a17;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#ffffff;border:1px solid #e6dccd;border-radius:16px;padding:32px;">
      <p style="margin:0 0 8px;font-size:12px;letter-spacing:1.5px;text-transform:uppercase;color:#2f6b2f;font-weight:700;"><?= e($data['platform_name']); ?></p>
      <h1 style="margin:0 0 16px;font-size:28px;line-height:1.2;"><?= e($title); ?></h1>
      <p style="margin:0 0 24px;font-size:16px;line-height:1.7;"><?= $body; ?></p>
      <?php if ($link !== ''): ?>
        <p style="margin:0 0 24px;"><a href="<?= e($link); ?>" style="display:inline-block;background:#1f1a17;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:999px;font-weight:700;"><?= e($linkLabel); ?></a></p>
      <?php endif; ?>
      <p style="margin:0;font-size:14px;line-height:1.6;color:#5c5146;">Support: <?= e($data['support_email']); ?><?php if ((string) $data['support_phone'] !== ''): ?> | <?= e($data['support_phone']); ?><?php endif; ?></p>
    </div>
  </div>
</body>
</html>
