<div class="container">
    <section class="panel panel-subtle filter-panel">
        <div>
            <span class="eyebrow">Task Discovery</span>
            <h1>Browse open tasks</h1>
            <p class="page-intro">Find jobs that match your skills. Filter by category, location, or budget in RWF to narrow down the right fit.</p>
        </div>

        <form method="get" action="<?= e(url_for('tasks/browse')) ?>" class="form-grid task-discovery-form" novalidate>
            <input type="hidden" name="route" value="tasks/browse">

            <div class="filter-grid task-filter-grid">
                <div class="form-row">
                    <label for="q">Keyword</label>
                    <input 
                        id="q" 
                        name="q" 
                        type="text" 
                        value="<?= e((string) $filters['q']) ?>" 
                        placeholder="Cleaning, moving, assembly"
                        autocomplete="off"
                    >
                </div>

                <div class="form-row">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= e((string) $category['id']) ?>" <?= (string) $filters['category_id'] === (string) $category['id'] ? 'selected' : '' ?>>
                                <?= e((string) $category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <label for="city">City</label>
                    <input 
                        id="city" 
                        name="city" 
                        type="text" 
                        value="<?= e((string) $filters['city']) ?>" 
                        placeholder="Kigali, Gasabo, Gisozi"
                        autocomplete="off"
                        list="kigali-cities"
                    >
                    <datalist id="kigali-cities">
                        <option value="Kigali">
                        <option value="Gasabo">
                        <option value="Kicukiro">
                        <option value="Nyarugenge">
                        <option value="Remera">
                        <option value="Kimisagara">
                        <option value="Nyarutarama">
                        <option value="Gisozi">
                        <option value="Kacyiru">
                        <option value="Kimironko">
                    </datalist>
                    <button type="button" id="use-current-location" class="button button-secondary button-small task-location-button">
                        Use my current location
                    </button>
                    <div id="location-feedback" aria-live="polite" class="task-location-feedback"></div>
                </div>

                <div class="form-row">
                    <label for="region">District</label>
                    <select id="region" name="region">
                        <option value="">All districts</option>
                        <option value="gasabo" <?= $filters['region'] === 'gasabo' ? 'selected' : '' ?>>Gasabo</option>
                        <option value="kicukiro" <?= $filters['region'] === 'kicukiro' ? 'selected' : '' ?>>Kicukiro</option>
                        <option value="nyarugenge" <?= $filters['region'] === 'nyarugenge' ? 'selected' : '' ?>>Nyarugenge</option>
                    </select>
                </div>

                <div class="form-row">
                    <label for="min_budget">Min budget <span class="field-unit">(RWF)</span></label>
                    <input 
                        id="min_budget" 
                        name="min_budget" 
                        type="number" 
                        step="1000" 
                        min="0" 
                        value="<?= e((string) $filters['min_budget']) ?>" 
                        placeholder="10,000"
                    >
                </div>

                <div class="form-row">
                    <label for="max_budget">Max budget <span class="field-unit">(RWF)</span></label>
                    <input 
                        id="max_budget" 
                        name="max_budget" 
                        type="number" 
                        step="1000" 
                        min="0" 
                        value="<?= e((string) $filters['max_budget']) ?>" 
                        placeholder="100,000"
                    >
                </div>

                <div class="form-row">
                    <label for="date_from">From date</label>
                    <input 
                        id="date_from" 
                        name="date_from" 
                        type="date" 
                        value="<?= e((string) $filters['date_from']) ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="date_to">To date</label>
                    <input 
                        id="date_to" 
                        name="date_to" 
                        type="date" 
                        value="<?= e((string) $filters['date_to']) ?>"
                    >
                </div>

                <div class="form-row">
                    <label for="sort">Sort by</label>
                    <select id="sort" name="sort">
                        <option value="newest" <?= $filters['sort'] === 'newest' ? 'selected' : '' ?>>Newest first</option>
                        <option value="oldest" <?= $filters['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest first</option>
                        <option value="budget_high" <?= $filters['sort'] === 'budget_high' ? 'selected' : '' ?>>Highest budget</option>
                        <option value="budget_low" <?= $filters['sort'] === 'budget_low' ? 'selected' : '' ?>>Lowest budget</option>
                        <option value="soonest" <?= $filters['sort'] === 'soonest' ? 'selected' : '' ?>>Soonest deadline</option>
                    </select>
                </div>
            </div>

            <div class="task-filter-presets">
                <label class="task-filter-presets-label">Quick filters</label>
                <div class="task-filter-presets-row">
                    <a href="<?= e(url_for('tasks/browse', ['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d')])) ?>" class="button button-secondary button-small">Today</a>
                    <a href="<?= e(url_for('tasks/browse', ['date_from' => date('Y-m-d'), 'date_to' => date('Y-m-d', strtotime('+7 days'))])) ?>" class="button button-secondary button-small">This week</a>
                    <a href="<?= e(url_for('tasks/browse', ['min_budget' => 50000])) ?>" class="button button-secondary button-small">High budget (50k+)</a>
                    <a href="<?= e(url_for('tasks/browse', ['category_id' => 1])) ?>" class="button button-secondary button-small">Cleaning jobs</a>
                    <a href="<?= e(url_for('tasks/browse', ['city' => 'kigali'])) ?>" class="button button-secondary button-small">Kigali only</a>
                </div>
            </div>

            <div class="hero-actions task-filter-actions">
                <button type="submit" class="button">Search tasks</button>
                <a class="button button-secondary" href="<?= e(url_for('tasks/browse')) ?>">Clear all filters</a>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="task-results-toolbar">
            <div>
                <h2 class="task-results-title">Available tasks</h2>
                <p class="section-intro task-results-intro">Showing <?= count($tasks) ?> open task<?= count($tasks) === 1 ? '' : 's' ?></p>
            </div>
            <div>
                <span class="pill"><?= count($tasks) ?> Result<?= count($tasks) === 1 ? '' : 's' ?></span>
            </div>
        </div>

        <?php if ($tasks === []): ?>
            <?php
            $emptyIcon = '📭';
            $emptyTitle = 'No tasks match that search';
            $emptyMessage = 'Try adjusting your filters: expand the location, remove the budget limit, or search for different keywords. New tasks are posted regularly, so check back soon.';
            $emptyAction = ['label' => 'See all open tasks', 'href' => url_for('tasks/browse')];
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="card-list">
                <?php foreach ($tasks as $task): ?>
                    <article class="task-card">
                        <div class="task-card-header">
                            <div class="task-card-title-wrap">
                                <h3 class="task-card-title task-card-title-compact">
                                    <a href="<?= e(url_for('tasks/view', ['id' => (int) $task['id']])) ?>">
                                        <?= e((string) $task['title']) ?>
                                    </a>
                                </h3>
                                <div class="task-card-meta">
                                    <span class="task-card-category">
                                        <?= e((string) $task['category_name']) ?>
                                    </span>
                                    <span>•</span>
                                    <span>
                                        📍 <?= e((string) $task['city']) ?>, <?= e((string) $task['country']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php $status = 'open'; $label = 'Open'; require BASE_PATH . '/app/views/partials/status-badge.php'; ?>
                        </div>

                        <p class="task-card-excerpt">
                            <?= e(mb_strlen((string) $task['description']) > 160 ? mb_substr((string) $task['description'], 0, 160) . '…' : (string) $task['description']) ?>
                        </p>

                        <div class="task-card-footer">
                            <div class="task-card-footer-copy">
                                <div class="price task-card-price">
                                    <?= e(moneyRwf($task['budget'])) ?>
                                </div>
                                <span class="muted task-card-meta-note">
                                    Posted by <strong><?= e((string) $task['client_name']) ?></strong>
                                </span>
                            </div>
                            <a class="button button-secondary button-small" href="<?= e(url_for('tasks/view', ['id' => (int) $task['id']])) ?>">
                                View &amp; bid
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
