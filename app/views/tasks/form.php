<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
$taskCategoryValue = !empty($errors) ? old_value('category_id') : (string) ($task['category_id'] ?? '');
$taskTitleValue = !empty($errors) ? old_value('title') : (string) ($task['title'] ?? '');
$taskDescriptionValue = !empty($errors) ? old_value('description') : (string) ($task['description'] ?? '');
$taskBudgetValue = !empty($errors) ? old_value('budget') : (string) ($task['budget'] ?? '');
$taskCityValue = !empty($errors) ? old_value('city') : (string) ($task['city'] ?? '');
$taskRegionValue = !empty($errors) ? old_value('region') : (string) ($task['region'] ?? '');
$taskCountryValue = !empty($errors) ? old_value('country', 'Rwanda') : (string) ($task['country'] ?? 'Rwanda');
$taskScheduledValue = !empty($errors)
    ? old_value('scheduled_for')
    : (!empty($task['scheduled_for']) ? date('Y-m-d\TH:i', strtotime((string) $task['scheduled_for'])) : '');
$categoryOptions = [['value' => '', 'label' => 'Select a category']];

foreach ($categories as $category) {
    $categoryOptions[] = [
        'value' => (string) $category['id'],
        'label' => (string) $category['name'],
    ];
}
?>
<form method="post" class="form-grid" novalidate>
    <?= Csrf::input() ?>

    <?php
    $name = 'category_id';
    $label = 'Category';
    $as = 'select';
    $type = 'text';
    $value = $taskCategoryValue;
    $required = true;
    $placeholder = null;
    $autocomplete = null;
    $hint = 'Choose the category taskers will use when browsing work.';
    $error = field_error($fieldErrors, 'category_id');
    $options = $categoryOptions;
    $attrs = [];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <?php
    $name = 'title';
    $label = 'Title';
    $as = 'input';
    $type = 'text';
    $value = $taskTitleValue;
    $required = true;
    $placeholder = 'Clean a two-bedroom apartment';
    $autocomplete = null;
    $hint = null;
    $error = field_error($fieldErrors, 'title');
    $attrs = ['maxlength' => '180'];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <?php
    $name = 'description';
    $label = 'Description';
    $as = 'textarea';
    $type = 'text';
    $value = $taskDescriptionValue;
    $required = true;
    $placeholder = 'Describe the work, timing, tools needed, and anything the tasker should know.';
    $autocomplete = null;
    $hint = null;
    $error = field_error($fieldErrors, 'description');
    $attrs = [];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <?php
    $name = 'budget';
    $label = 'Budget (RWF)';
    $as = 'input';
    $type = 'number';
    $value = $taskBudgetValue;
    $required = true;
    $placeholder = '10000';
    $autocomplete = null;
    $hint = 'Enter your total fixed budget in RWF.';
    $error = field_error($fieldErrors, 'budget');
    $attrs = ['step' => '1', 'min' => '1', 'inputmode' => 'numeric'];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <?php
    $name = 'city';
    $label = 'City';
    $as = 'input';
    $type = 'text';
    $value = $taskCityValue;
    $required = true;
    $placeholder = 'Kigali';
    $autocomplete = null;
    $hint = null;
    $error = field_error($fieldErrors, 'city');
    $attrs = ['maxlength' => '100'];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <?php
    $name = 'region';
    $label = 'District / Sector';
    $as = 'input';
    $type = 'text';
    $value = $taskRegionValue;
    $required = false;
    $placeholder = 'Gasabo, Kicukiro, Nyarugenge';
    $autocomplete = null;
    $hint = 'Optional, but it helps taskers judge travel time.';
    $error = field_error($fieldErrors, 'region');
    $attrs = ['maxlength' => '100'];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <?php
    $name = 'country';
    $label = 'Country';
    $as = 'input';
    $type = 'text';
    $value = $taskCountryValue;
    $required = true;
    $placeholder = null;
    $autocomplete = null;
    $hint = null;
    $error = field_error($fieldErrors, 'country');
    $attrs = ['maxlength' => '100'];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <?php
    $name = 'scheduled_for';
    $label = 'Scheduled for';
    $as = 'input';
    $type = 'datetime-local';
    $value = $taskScheduledValue;
    $required = false;
    $placeholder = null;
    $autocomplete = null;
    $hint = 'Optional. Times display in Africa/Kigali.';
    $error = field_error($fieldErrors, 'scheduled_for');
    $attrs = [];
    require BASE_PATH . '/app/views/partials/form_field.php';
    ?>

    <div class="form-actions">
        <button type="submit"><?= isset($task) ? 'Save changes' : 'Create task' ?></button>
        <span class="helper-text">Your task stays visible to bidders only while it is active and open.</span>
    </div>
</form>
