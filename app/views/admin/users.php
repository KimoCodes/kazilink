<div class="container">
    <section class="panel">
        <?php
        $activeUsers = array_values(array_filter($users, static fn (array $user): bool => (int) $user['is_active'] === 1));
        $inactiveUsers = array_values(array_filter($users, static fn (array $user): bool => (int) $user['is_active'] !== 1));
        $taskers = array_values(array_filter($users, static fn (array $user): bool => (string) $user['role'] === 'tasker'));
        ?>
        <?php
        $title = 'Manage Users';
        $eyebrow = 'Admin';
        $intro = 'Activate or deactivate users to manage marketplace access and account status.';
        $secondaryLink = ['label' => 'Back to dashboard', 'href' => url_for('admin/dashboard')];
        unset($primaryAction, $secondaryAction);
        require BASE_PATH . '/app/views/partials/page_header.php';
        ?>

        <div class="table-toolbar">
            <div>
                <h2>All Users</h2>
                <p class="muted">Review account health, role mix, and tasker performance at a glance.</p>
            </div>
            <span class="pill pill-info"><?= count($users) ?> user<?= count($users) !== 1 ? 's' : '' ?></span>
        </div>

        <div class="summary-grid booking-index-summary">
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Active accounts</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($activeUsers)) ?></strong>
                    <span>Currently allowed to access the marketplace</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Inactive accounts</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($inactiveUsers)) ?></strong>
                    <span>Users currently blocked from access</span>
                </div>
            </article>
            <article class="info-card task-summary-card">
                <span class="sidebar-item-label">Tasker accounts</span>
                <div class="task-summary-metric-row">
                    <strong><?= e((string) count($taskers)) ?></strong>
                    <span>Profiles available for work discovery and booking</span>
                </div>
            </article>
        </div>

        <?php if ($users === []): ?>
            <?php
            $emptyIcon = '👥';
            $emptyTitle = 'No users found';
            $emptyMessage = 'The marketplace is empty or all user records are currently unavailable.';
            require BASE_PATH . '/app/views/partials/empty_state.php';
            ?>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Jobs Completed</th>
                            <th>Avg Rating</th>
                            <th>Reviews</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?= e((string) ($user['full_name'] ?? 'Unknown')) ?></strong></td>
                                <td><span class="text-mono"><?= e((string) $user['email']) ?></span></td>
                                <td>
                                    <?php
                                    $role = (string) $user['role'];
                                    $roleClass = $role === 'client' ? 'role-chip-client' : ($role === 'tasker' ? 'role-chip-tasker' : 'role-chip-admin');
                                    $roleLabel = $role === 'client' ? 'Client' : ($role === 'tasker' ? 'Tasker' : 'Admin');
                                    ?>
                                    <span class="role-chip <?= e($roleClass) ?>"><?= e($roleLabel) ?></span>
                                </td>
                                <td>
                                    <?= $role === 'tasker' ? e((string) $user['jobs_completed']) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <?= $role === 'tasker' ? e(number_format((float) $user['avg_rating'], 1)) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <?= $role === 'tasker' ? e((string) $user['review_count']) : '<span class="text-muted">—</span>' ?>
                                </td>
                                <td>
                                    <?php
                                    $status = (int) $user['is_active'] === 1 ? 'active' : 'inactive';
                                    $label = (int) $user['is_active'] === 1 ? 'Active' : 'Inactive';
                                    require BASE_PATH . '/app/views/partials/status-badge.php';
                                    ?>
                                </td>
                                <td>
                                    <?= !empty($user['last_login_at']) ? e(dateFmt((string) $user['last_login_at'])) : '<span class="text-muted">Never</span>' ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <form method="post" action="<?= e(url_for('admin/toggle-user')) ?>">
                                            <?= Csrf::input() ?>
                                            <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">
                                            <button
                                                type="submit"
                                                class="button <?= (int) $user['is_active'] === 1 ? 'button-danger' : 'button-success' ?> button-small"
                                                data-confirm="Change this user status?"
                                            >
                                                <?= (int) $user['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
