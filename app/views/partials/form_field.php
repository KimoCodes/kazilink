<?php
$fieldName = (string) ($name ?? '');
$fieldId = (string) ($id ?? $fieldName);
$fieldAs = (string) ($as ?? 'input');
$fieldType = (string) ($type ?? 'text');
$fieldLabel = (string) ($label ?? '');
$fieldValue = $value ?? '';
$fieldHint = isset($hint) ? (string) $hint : null;
$fieldError = isset($error) ? (string) $error : null;
$fieldRequired = (bool) ($required ?? false);
$fieldOptions = is_array($options ?? null) ? $options : [];
$fieldRows = (int) ($rows ?? 5);
$fieldPlaceholder = isset($placeholder) ? (string) $placeholder : null;
$fieldAutocomplete = isset($autocomplete) ? (string) $autocomplete : null;
$fieldClass = trim((string) ($class ?? 'form-row'));
$fieldAttributes = is_array($attrs ?? null) ? $attrs : [];
$connectSummaryErrors = (bool) ($connectSummaryErrors ?? false);

$describedBy = [];

if ($connectSummaryErrors) {
    $describedBy[] = 'form-errors';
}

if ($fieldHint) {
    $describedBy[] = $fieldId . '-hint';
}

if ($fieldError) {
    $describedBy[] = $fieldId . '-error';
}

$existingDescribedBy = trim((string) ($fieldAttributes['aria-describedby'] ?? ''));

if ($existingDescribedBy !== '') {
    array_unshift($describedBy, $existingDescribedBy);
}

$controlClass = 'form-control';

if ($fieldAs === 'select') {
    $controlClass .= ' form-select';
}

if ($fieldAs === 'textarea') {
    $controlClass .= ' form-textarea';
}

if ($fieldError) {
    $controlClass .= ' input-invalid';
    $fieldAttributes['aria-invalid'] = 'true';
}

$fieldAttributes['id'] = $fieldId;
$fieldAttributes['name'] = $fieldName;
$fieldAttributes['class'] = trim((string) ($fieldAttributes['class'] ?? '') . ' ' . $controlClass);

if ($fieldPlaceholder !== null && !array_key_exists('placeholder', $fieldAttributes)) {
    $fieldAttributes['placeholder'] = $fieldPlaceholder;
}

if ($fieldAutocomplete !== null && !array_key_exists('autocomplete', $fieldAttributes)) {
    $fieldAttributes['autocomplete'] = $fieldAutocomplete;
}

if ($fieldRequired) {
    $fieldAttributes['required'] = true;
}

if ($describedBy !== []) {
    $fieldAttributes['aria-describedby'] = implode(' ', array_unique(array_filter($describedBy)));
}

if ($fieldAs === 'textarea' && !array_key_exists('rows', $fieldAttributes)) {
    $fieldAttributes['rows'] = (string) $fieldRows;
}

if ($fieldAs !== 'textarea' && $fieldAs !== 'select' && !array_key_exists('type', $fieldAttributes)) {
    $fieldAttributes['type'] = $fieldType;
}

$renderAttributes = static function (array $attributes): string {
    $parts = [];

    foreach ($attributes as $attribute => $rawValue) {
        if ($rawValue === false || $rawValue === null) {
            continue;
        }

        if ($rawValue === true) {
            $parts[] = e((string) $attribute);
            continue;
        }

        $parts[] = e((string) $attribute) . '="' . e((string) $rawValue) . '"';
    }

    return implode(' ', $parts);
};
?>
<div class="<?= e($fieldClass) ?>">
    <label class="form-label" for="<?= e($fieldId) ?>">
        <?= e($fieldLabel) ?>
        <?php if ($fieldRequired): ?>
            <span class="required-indicator" aria-hidden="true">*</span>
        <?php endif; ?>
    </label>

    <?php if ($fieldAs === 'select'): ?>
        <select <?= $renderAttributes($fieldAttributes) ?>>
            <?php foreach ($fieldOptions as $option): ?>
                <?php
                $optionValue = is_array($option) ? (string) ($option['value'] ?? '') : (string) $option;
                $optionLabel = is_array($option) ? (string) ($option['label'] ?? $optionValue) : (string) $option;
                $optionDisabled = (bool) (is_array($option) ? ($option['disabled'] ?? false) : false);
                ?>
                <option
                    value="<?= e($optionValue) ?>"
                    <?= (string) $fieldValue === $optionValue ? 'selected' : '' ?>
                    <?= $optionDisabled ? 'disabled' : '' ?>
                >
                    <?= e($optionLabel) ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php elseif ($fieldAs === 'textarea'): ?>
        <textarea <?= $renderAttributes($fieldAttributes) ?>><?= e((string) $fieldValue) ?></textarea>
    <?php else: ?>
        <input <?= $renderAttributes($fieldAttributes) ?> value="<?= e((string) $fieldValue) ?>">
    <?php endif; ?>

    <?php if ($fieldError): ?>
        <span class="form-error" id="<?= e($fieldId . '-error') ?>"><?= e($fieldError) ?></span>
    <?php elseif ($fieldHint): ?>
        <span class="field-hint" id="<?= e($fieldId . '-hint') ?>"><?= e($fieldHint) ?></span>
    <?php endif; ?>
</div>
