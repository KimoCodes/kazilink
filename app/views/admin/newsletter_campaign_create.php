<?php
$input = is_array($input ?? null) ? $input : [];
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$errors = is_array($errors ?? null) ? $errors : [];
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Create Newsletter Campaign';
        $eyebrow = 'Admin';
        $intro = 'Create a new email campaign to send updates and announcements to your newsletter subscribers.';
        $secondaryLink = ['label' => 'Back to campaigns', 'href' => url_for('admin/newsletter-campaigns')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <form method="post" action="<?= e(url_for('admin/newsletter-campaigns/create')) ?>" class="theme-form">
            <?= Csrf::input() ?>

            <div class="theme-admin-panel">
                <div class="theme-card">
                    <div class="theme-card-head">
                        <h3>Campaign Details</h3>
                        <p>Basic information about your email campaign.</p>
                    </div>

                    <div class="theme-field">
                        <label for="title">Campaign Title</label>
                        <input 
                            type="text" 
                            id="title" 
                            name="title" 
                            value="<?= e($input['title'] ?? '') ?>"
                            placeholder="e.g., Monthly Newsletter, Product Updates"
                            required
                        >
                        <?php if (isset($fieldErrors['title'])): ?>
                            <small class="field-error"><?= e(implode(' ', $fieldErrors['title'])) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="theme-field">
                        <label for="subject">Email Subject</label>
                        <input 
                            type="text" 
                            id="subject" 
                            name="subject" 
                            value="<?= e($input['subject'] ?? '') ?>"
                            placeholder="e.g., Your Monthly Updates from Kazilink"
                            required
                        >
                        <?php if (isset($fieldErrors['subject'])): ?>
                            <small class="field-error"><?= e(implode(' ', $fieldErrors['subject'])) ?></small>
                        <?php endif; ?>
                    </div>

                    <div class="theme-field">
                        <label for="audience">Target Audience</label>
                        <select id="audience" name="audience" required>
                            <option value="">Select audience...</option>
                            <option value="all" <?= ($input['audience'] ?? '') === 'all' ? 'selected' : '' ?>>All Subscribers</option>
                            <option value="client" <?= ($input['audience'] ?? '') === 'client' ? 'selected' : '' ?>>Clients Only</option>
                            <option value="tasker" <?= ($input['audience'] ?? '') === 'tasker' ? 'selected' : '' ?>>Taskers Only</option>
                            <option value="partner" <?= ($input['audience'] ?? '') === 'partner' ? 'selected' : '' ?>>Partners Only</option>
                        </select>
                        <?php if (isset($fieldErrors['audience'])): ?>
                            <small class="field-error"><?= e(implode(' ', $fieldErrors['audience'])) ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="theme-card">
                    <div class="theme-card-head">
                        <h3>Email Content</h3>
                        <p>Write your email content using HTML formatting.</p>
                    </div>

                    <div class="theme-field">
                        <label for="content">Email Content</label>
                        <textarea 
                            id="content" 
                            name="content" 
                            rows="15"
                            placeholder="Write your email content here. You can use HTML tags like &lt;h1&gt;, &lt;h2&gt;, &lt;p&gt;, &lt;a&gt;, &lt;strong&gt;, etc."
                            required
                        ><?= e($input['content'] ?? '') ?></textarea>
                        <?php if (isset($fieldErrors['content'])): ?>
                            <small class="field-error"><?= e(implode(' ', $fieldErrors['content'])) ?></small>
                        <?php endif; ?>
                        <small>HTML formatting is supported. Use semantic tags for better email rendering.</small>
                    </div>
                </div>

                <div class="theme-card">
                    <div class="theme-card-head">
                        <h3>Content Preview</h3>
                        <p>See how your email will look to subscribers.</p>
                    </div>

                    <div class="theme-preview" data-mode="light">
                        <div class="theme-preview-topbar">
                            <div class="theme-preview-badge">Subject: <?= e($input['subject'] ?? 'Email subject will appear here') ?></div>
                        </div>
                        <div class="theme-preview-content">
                            <div class="theme-preview-card">
                                <strong><?= e($input['title'] ?? 'Campaign Title') ?></strong>
                                <small>Preview content below:</small>
                            </div>
                        </div>
                        <div style="margin-top: 1rem; padding: 1rem; border: 1px solid var(--color-border); border-radius: 0.5rem; background: var(--color-surface);">
                            <?php if (!empty($input['content'])): ?>
                                <?= $input['content'] ?>
                            <?php else: ?>
                                <p style="color: var(--color-text-soft);">Email content preview will appear here...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="theme-form-actions">
                <button type="submit" class="button">Create Campaign</button>
                <a href="<?= e(url_for('admin/newsletter-campaigns')) ?>" class="button button-secondary">Cancel</a>
            </div>
        </form>

        <?php if ($errors !== []): ?>
            <div class="theme-alert theme-alert-error" style="margin-top: 1rem;">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const contentTextarea = document.getElementById('content');
    const previewDiv = document.querySelector('.theme-preview div[style*="padding: 1rem"]');
    
    function updatePreview() {
        if (previewDiv && contentTextarea.value.trim()) {
            previewDiv.innerHTML = contentTextarea.value;
        } else if (previewDiv) {
            previewDiv.innerHTML = '<p style="color: var(--color-text-soft);">Email content preview will appear here...</p>';
        }
    }
    
    if (contentTextarea && previewDiv) {
        contentTextarea.addEventListener('input', updatePreview);
        updatePreview(); // Initial preview
    }
});
</script>
