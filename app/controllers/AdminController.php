<?php

declare(strict_types=1);

final class AdminController
{
    private User $users;
    private Task $tasks;
    private AdminAudit $audit;
    private Payment $payments;

    public function __construct()
    {
        $this->users = new User();
        $this->tasks = new Task();
        $this->audit = new AdminAudit();
        $this->payments = new Payment();
    }

    public function dashboard(): string
    {
        Auth::requireRole('admin');

        $paymentStats = $this->safePaymentStats();

        return View::render('admin/dashboard', [
            'pageTitle' => 'Admin Dashboard',
            'stats' => [
                'total_users' => $this->users->countAll(),
                'active_clients' => $this->users->countByRole('client', true),
                'active_taskers' => $this->users->countByRole('tasker', true),
                'open_tasks' => $this->tasks->countByStatus('open'),
                'booked_tasks' => $this->tasks->countByStatus('booked'),
                'completed_tasks' => $this->tasks->countByStatus('completed'),
                'paid_payments' => $paymentStats['paid_payments'],
                'payments_volume' => $paymentStats['payments_volume'],
            ],
            'auditLogs' => $this->audit->latest(15),
            'recentPayments' => $this->safeRecentPayments(8),
        ]);
    }

    public function payments(): string
    {
        Auth::requireRole('admin');

        $paymentStats = $this->safePaymentStats();

        return View::render('admin/payments', [
            'pageTitle' => 'Payments',
            'payments' => $this->safeRecentPayments(50),
            'stats' => [
                'paid_payments' => $paymentStats['paid_payments'],
                'payments_volume' => $paymentStats['payments_volume'],
            ],
        ]);
    }

    private function safePaymentStats(): array
    {
        try {
            return [
                'paid_payments' => $this->payments->countPaid(),
                'payments_volume' => $this->payments->totalPaidMinor(),
            ];
        } catch (Throwable $exception) {
            error_log('Admin payment stats error: ' . $exception->getMessage());

            return [
                'paid_payments' => 0,
                'payments_volume' => 0,
            ];
        }
    }

    private function safeRecentPayments(int $limit): array
    {
        try {
            return $this->payments->latest($limit);
        } catch (Throwable $exception) {
            error_log('Admin recent payments error: ' . $exception->getMessage());

            return [];
        }
    }

    public function users(): string
    {
        Auth::requireRole('admin');

        return View::render('admin/users', [
            'pageTitle' => 'Manage Users',
            'users' => $this->users->allForAdmin(),
        ]);
    }

    public function tasks(): string
    {
        Auth::requireRole('admin');

        return View::render('admin/tasks', [
            'pageTitle' => 'Manage Tasks',
            'tasks' => $this->tasks->allForAdmin(),
        ]);
    }

    public function toggleUser(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/users');
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            Session::flash('error', 'User not found.');
            redirect('admin/users');
        }

        if ($userId === (int) Auth::id()) {
            Session::flash('error', 'You cannot deactivate your own admin account.');
            redirect('admin/users');
        }

        $user = $this->users->findById($userId);

        if ($user === null) {
            Session::flash('error', 'User not found.');
            redirect('admin/users');
        }

        $newStatus = !(bool) $user['is_active'];
        $this->users->setActive($userId, $newStatus);
        $this->audit->log((int) Auth::id(), 'user', $userId, $newStatus ? 'activated' : 'deactivated');

        Session::flash('success', 'User status updated.');
        redirect('admin/users');
    }

    public function toggleTask(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/tasks');
        $taskId = (int) ($_POST['task_id'] ?? 0);

        if ($taskId <= 0) {
            Session::flash('error', 'Task not found.');
            redirect('admin/tasks');
        }

        $task = $this->tasks->findById($taskId);

        if ($task === null) {
            Session::flash('error', 'Task not found.');
            redirect('admin/tasks');
        }

        $newActive = !(bool) $task['is_active'];
        $this->tasks->setActive($taskId, $newActive);
        $this->audit->log((int) Auth::id(), 'task', $taskId, $newActive ? 'activated' : 'deactivated');

        Session::flash('success', 'Task status updated.');
        redirect('admin/tasks');
    }
}
