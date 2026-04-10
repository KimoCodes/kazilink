<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
$avatarUrl = !empty($profile['avatar_path']) ? public_url((string) $profile['avatar_path']) : null;
$avatarError = field_error($fieldErrors, 'avatar');
$displayName = (string) old_value('profile_full_name', (string) $profile['full_name']);
$avatarInitial = strtoupper(substr($displayName !== '' ? $displayName : (string) $profile['email'], 0, 1));
?>
<div class="container">
    <section class="panel">
        <?php
        $title = 'Edit Profile';
        $eyebrow = 'Account';
        $intro = 'Update your public profile details and upload a profile photo so clients and taskers can trust who they are working with.';
        $secondaryLink = ['label' => 'Back to profile', 'href' => url_for('profile/show')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <form method="post" action="<?= e(url_for('profile/edit')) ?>" enctype="multipart/form-data" class="form-grid" novalidate>
            <?= Csrf::input() ?>

            <?php
            $name = 'full_name';
            $label = 'Full name';
            $as = 'input';
            $type = 'text';
            $value = old_value('profile_full_name', (string) $profile['full_name']);
            $placeholder = 'Aline Uwase';
            $autocomplete = 'name';
            $required = true;
            $hint = null;
            $error = field_error($fieldErrors, 'full_name');
            $attrs = ['maxlength' => '150'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'phone';
            $label = 'Phone';
            $as = 'input';
            $type = 'text';
            $value = old_value('profile_phone', (string) $profile['phone']);
            $placeholder = '+250 7XX XXX XXX';
            $autocomplete = 'tel';
            $required = false;
            $hint = 'Use a Rwanda-friendly format if you want clients to contact you by phone.';
            $error = field_error($fieldErrors, 'phone');
            $attrs = ['maxlength' => '30'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'city';
            $label = 'City';
            $as = 'input';
            $type = 'text';
            $value = old_value('profile_city', (string) $profile['city']);
            $placeholder = 'Kigali';
            $autocomplete = 'address-level2';
            $required = false;
            $hint = null;
            $error = field_error($fieldErrors, 'city');
            $attrs = ['maxlength' => '100'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'region';
            $label = 'District / Region';
            $as = 'input';
            $type = 'text';
            $value = old_value('profile_region', (string) $profile['region']);
            $placeholder = 'Gasabo or Kigali City';
            $autocomplete = 'address-level1';
            $required = false;
            $hint = null;
            $error = field_error($fieldErrors, 'region');
            $attrs = ['maxlength' => '100'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'country';
            $label = 'Country';
            $as = 'input';
            $type = 'text';
            $value = old_value('profile_country', (string) $profile['country']);
            $placeholder = 'Rwanda';
            $autocomplete = 'country-name';
            $required = false;
            $hint = null;
            $error = field_error($fieldErrors, 'country');
            $attrs = ['maxlength' => '100'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'bio';
            $label = 'Bio';
            $as = 'textarea';
            $type = 'text';
            $value = old_value('profile_bio', (string) $profile['bio']);
            $placeholder = 'Briefly describe your background, reliability, and the kind of work you do best.';
            $autocomplete = null;
            $required = false;
            $hint = 'Keep it concise and specific so people can trust your profile quickly.';
            $error = field_error($fieldErrors, 'bio');
            $attrs = ['maxlength' => '1000', 'rows' => '5'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'skills_summary';
            $label = 'Skills summary';
            $as = 'textarea';
            $type = 'text';
            $value = old_value('profile_skills_summary', (string) $profile['skills_summary']);
            $placeholder = 'Cleaning, assembly, plumbing, deliveries.';
            $autocomplete = null;
            $required = false;
            $hint = 'Short keyword-style summary for the work you want to be hired for.';
            $error = field_error($fieldErrors, 'skills_summary');
            $attrs = ['maxlength' => '280', 'rows' => '4'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <div class="profile-media-card">
                <div class="profile-media-preview" id="avatar-preview-container">
                    <?php if ($avatarUrl !== null): ?>
                        <img
                            id="avatar-preview-image"
                            class="profile-avatar-preview"
                            src="<?= e($avatarUrl) ?>"
                            alt="Profile avatar preview"
                            data-avatar-modal-src="<?= e($avatarUrl) ?>"
                            data-avatar-modal-alt="<?= e($displayName !== '' ? $displayName : 'Profile avatar') ?>"
                        >
                    <?php else: ?>
                        <div id="avatar-preview-fallback" class="profile-avatar-fallback"><?= e($avatarInitial) ?></div>
                    <?php endif; ?>
                </div>

                <div class="profile-media-copy">
                    <div class="form-row">
                        <label class="form-label" for="avatar">Profile photo</label>
                        <input
                            id="avatar"
                            name="avatar"
                            type="file"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                            aria-describedby="avatar-hint<?= $avatarError !== null ? ' avatar-error' : '' ?>"
                        >
                        <?php if ($avatarError !== null): ?>
                            <span class="form-error" id="avatar-error"><?= e($avatarError) ?></span>
                        <?php endif; ?>
                        <span class="field-hint" id="avatar-hint">JPEG, PNG, or WebP. Maximum size: 2MB. The preview updates as soon as you choose a file.</span>
                    </div>
                </div>
            </div>

            <div class="form-actions profile-form-actions">
                <a class="button button-secondary" href="<?= e(url_for('profile/show')) ?>">Cancel</a>
                <button type="submit" class="button">Save changes</button>
            </div>
        </form>
    </section>
</div>

<script>
    (function () {
        const input = document.getElementById('avatar');
        const previewContainer = document.getElementById('avatar-preview-container');

        if (!input || !previewContainer) {
            return;
        }

        const renderFallback = (label) => {
            previewContainer.replaceChildren();
            const fallback = document.createElement('div');
            fallback.id = 'avatar-preview-fallback';
            fallback.className = 'profile-avatar-fallback';
            fallback.textContent = label;
            previewContainer.appendChild(fallback);
        };

        const renderImage = (src, alt) => {
            previewContainer.replaceChildren();
            const image = document.createElement('img');
            image.id = 'avatar-preview-image';
            image.className = 'profile-avatar-preview';
            image.src = src;
            image.alt = alt;
            image.dataset.avatarModalSrc = src;
            image.dataset.avatarModalAlt = alt;
            previewContainer.appendChild(image);
        };

        input.addEventListener('change', function (event) {
            const file = event.target.files[0];

            if (!file) {
                return;
            }

            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                alert('Please select a JPEG, PNG, or WebP image.');
                event.target.value = '';
                return;
            }

            if (file.size > 2 * 1024 * 1024) {
                alert('File size must be 2MB or smaller.');
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function (loadEvent) {
                const src = typeof loadEvent.target?.result === 'string' ? loadEvent.target.result : '';

                if (src === '') {
                    renderFallback('<?= e($avatarInitial) ?>');
                    return;
                }

                renderImage(src, 'New avatar preview');
            };
            reader.readAsDataURL(file);
        });
    }());
</script>
