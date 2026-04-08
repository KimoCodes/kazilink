<?php if (!empty($errors)): ?>
    <div class="error-summary" id="form-errors" aria-live="polite" tabindex="-1">
        <strong>Please fix the following:</strong>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li class="error-item"><?= e((string) $error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
