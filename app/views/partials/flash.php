<?php $flashMessages = Session::getFlash(); ?>
<?php if ($flashMessages !== []): ?>
    <ul class="flash-list" aria-live="polite">
        <?php foreach ($flashMessages as $type => $messages): ?>
            <?php foreach ((array) $messages as $message): ?>
                <li class="flash flash-<?= e((string) $type) ?>" data-flash>
                    <div>
                        <strong class="flash-title"><?= e(ucfirst((string) $type)) ?></strong>
                        <p class="flash-message"><?= e((string) $message) ?></p>
                    </div>
                    <button type="button" class="flash-dismiss" data-flash-dismiss aria-label="Dismiss message">&times;</button>
                </li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
