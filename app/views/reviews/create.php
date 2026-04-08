<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
$ratingValue = !empty($errors) ? old_value('review_rating') : '';
$commentValue = !empty($errors) ? old_value('review_comment') : '';
$ratingOptions = [
    ['value' => '', 'label' => 'Select a rating'],
    ['value' => '5', 'label' => '5 - Excellent'],
    ['value' => '4', 'label' => '4 - Good'],
    ['value' => '3', 'label' => '3 - Okay'],
    ['value' => '2', 'label' => '2 - Poor'],
    ['value' => '1', 'label' => '1 - Very poor'],
];
?>
<div class="container narrow">
    <section class="panel">
        <?php
        $title = 'Leave a review';
        $eyebrow = 'Review';
        $intro = 'Share a concise rating and comment for the completed booking.';
        $secondaryLink = ['label' => 'Back to booking', 'href' => url_for('bookings/show', ['id' => (int) $booking['id']])];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="info-card">
            <p class="muted">You are reviewing <strong><?= e((string) $booking['tasker_name']) ?></strong> for <strong><?= e((string) $booking['title']) ?></strong>.</p>
        </div>

        <form method="post" action="<?= e(url_for('reviews/create')) ?>" class="form-grid" novalidate>
            <?= Csrf::input() ?>
            <input type="hidden" name="booking_id" value="<?= e((string) $booking['id']) ?>">

            <?php
            $name = 'rating';
            $label = 'Rating';
            $as = 'select';
            $type = 'text';
            $value = $ratingValue;
            $placeholder = null;
            $autocomplete = null;
            $required = true;
            $hint = null;
            $error = field_error($fieldErrors, 'rating');
            $options = $ratingOptions;
            $attrs = [];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'comment';
            $label = 'Comment';
            $as = 'textarea';
            $type = 'text';
            $value = $commentValue;
            $placeholder = 'What went well? What should future clients know?';
            $autocomplete = null;
            $required = false;
            $hint = 'Optional, but specific comments are more helpful than generic praise.';
            $error = field_error($fieldErrors, 'comment');
            $attrs = ['maxlength' => '2000', 'rows' => '5'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <div class="form-actions">
                <button type="submit">Submit review</button>
            </div>
        </form>
    </section>
</div>
