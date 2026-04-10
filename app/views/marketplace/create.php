<?php
$fieldErrors = is_array($fieldErrors ?? null) ? $fieldErrors : [];
$connectSummaryErrors = !empty($errors);
?>
<div class="container narrow">
    <section class="panel">
        <?php
        $title = 'Sell an Item';
        $eyebrow = 'Marketplace';
        $intro = 'Post an item, collect bids, and select the highest offer when you are ready to share contact details.';
        $secondaryLink = ['label' => 'Back to marketplace', 'href' => url_for('marketplace/index')];
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <form method="post" action="<?= e(url_for('marketplace/create')) ?>" class="form-grid" novalidate>
            <?= Csrf::input() ?>

            <?php
            $name = 'title';
            $label = 'Item title';
            $value = old_value('title');
            $placeholder = 'iPhone 13, office chair, dining table';
            $required = true;
            $hint = 'Keep it specific so bidders understand what is being sold.';
            $error = field_error($fieldErrors, 'title');
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <?php
            $name = 'description';
            $label = 'Description';
            $as = 'textarea';
            $value = old_value('description');
            $placeholder = 'Describe the condition, brand, age, accessories included, and any important details.';
            $required = true;
            $hint = 'A clearer description leads to better bids.';
            $error = field_error($fieldErrors, 'description');
            $attrs = ['maxlength' => '4000', 'rows' => '6'];
            require BASE_PATH . '/app/views/partials/form_field.php';
            ?>

            <div class="filter-grid task-filter-grid">
                <?php
                $name = 'city';
                $label = 'City';
                $as = 'input';
                $type = 'text';
                $value = old_value('city');
                $placeholder = 'Kigali';
                $required = true;
                $hint = null;
                $error = field_error($fieldErrors, 'city');
                $attrs = [];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <?php
                $name = 'region';
                $label = 'Region';
                $value = old_value('region');
                $placeholder = 'Optional';
                $required = false;
                $hint = null;
                $error = field_error($fieldErrors, 'region');
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <?php
                $name = 'country';
                $label = 'Country';
                $value = old_value('country', 'Rwanda');
                $placeholder = 'Rwanda';
                $required = true;
                $hint = null;
                $error = field_error($fieldErrors, 'country');
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>

                <?php
                $name = 'starting_price';
                $label = 'Starting price (RWF)';
                $type = 'number';
                $value = old_value('starting_price');
                $placeholder = '50000';
                $required = true;
                $hint = 'Bids must meet or exceed this amount.';
                $error = field_error($fieldErrors, 'starting_price');
                $attrs = ['step' => '1000', 'min' => '1000', 'inputmode' => 'numeric'];
                require BASE_PATH . '/app/views/partials/form_field.php';
                ?>
            </div>

            <button type="submit" class="button">Publish listing</button>
        </form>
    </section>
</div>
