<?php

declare(strict_types=1);

final class TaskController
{
    private Task $tasks;
    private Category $categories;
    private Bid $bids;
    private Booking $bookings;

    public function __construct()
    {
        $this->tasks = new Task();
        $this->categories = new Category();
        $this->bids = new Bid();
        $this->bookings = new Booking();
    }

    public function index(): string
    {
        Auth::requireRole('client');

        return View::render('tasks/index', [
            'pageTitle' => 'My Tasks',
            'tasks' => $this->tasks->forClient((int) Auth::id()),
        ]);
    }

    public function browse(): string
    {
        Auth::requireRole(['tasker', 'admin']);

        $categories = $this->categories->allActive();
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'city' => normalize_whitespace((string) ($_GET['city'] ?? '')),
            'region' => normalize_whitespace((string) ($_GET['region'] ?? '')),
            'min_budget' => trim((string) ($_GET['min_budget'] ?? '')),
            'max_budget' => trim((string) ($_GET['max_budget'] ?? '')),
            'date_from' => trim((string) ($_GET['date_from'] ?? '')),
            'date_to' => trim((string) ($_GET['date_to'] ?? '')),
            'sort' => trim((string) ($_GET['sort'] ?? 'newest')),
        ];

        return View::render('tasks/browse', [
            'pageTitle' => 'Browse Tasks',
            'categories' => $categories,
            'filters' => $filters,
            'tasks' => $this->tasks->browseOpen($filters),
        ]);
    }

    public function show(): string
    {
        Auth::requireLogin();

        $taskId = (int) ($_GET['id'] ?? 0);

        if ($taskId <= 0) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/index');
        }

        $task = $this->tasks->findById($taskId);

        if ($task === null) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/index');
        }

        $currentUser = Auth::user();
        $isOwner = (int) $task['client_id'] === (int) ($currentUser['id'] ?? 0);
        $isAdmin = Auth::role() === 'admin';

        if (!$isOwner && !$isAdmin) {
            Session::flash('error', 'You do not have permission to view that task.');
            redirect('home/index');
        }

        $booking = null;
        if ((string) $task['status'] === 'booked' || (string) $task['status'] === 'completed') {
            $booking = $this->bookings->findByTaskId($taskId);
        }

        return View::render('tasks/show', [
            'pageTitle' => 'Task Details',
            'task' => $task,
            'canEdit' => $isOwner && (int) $task['is_active'] === 1 && $task['status'] === 'open',
            'bids' => $isOwner ? $this->bids->forClientTask($taskId, (int) Auth::id()) : [],
            'booking' => $booking,
        ]);
    }

    public function create(): string
    {
        Auth::requireRole('client');

        $categories = $this->categories->allActive();

        if ($categories === []) {
            Session::flash('error', 'No active categories are available yet. Please add seed data first.');
            redirect('tasks/index');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verifyRequest();
            $input = Validator::trim($_POST);
            Session::setOldInput($input);
            $fieldErrors = Validator::taskFields($input, $this->categories->activeIds());

            if ($fieldErrors !== []) {
                return View::render('tasks/create', [
                    'pageTitle' => 'Create Task',
                    'categories' => $categories,
                    'errors' => Validator::flattenFieldErrors($fieldErrors),
                    'fieldErrors' => $fieldErrors,
                ]);
            }

            $taskId = $this->tasks->create([
                'client_id' => (int) Auth::id(),
                'category_id' => (int) $input['category_id'],
                'title' => normalize_whitespace((string) $input['title']),
                'description' => normalize_whitespace((string) $input['description']),
                'city' => normalize_whitespace((string) $input['city']),
                'region' => normalize_whitespace((string) ($input['region'] ?? '')),
                'country' => normalize_whitespace((string) $input['country']),
                'budget' => number_format((float) round((float) $input['budget']), 2, '.', ''),
                'scheduled_for' => (string) ($input['scheduled_for'] ?? ''),
            ]);

            Session::clearOldInput();
            Session::flash('success', 'Task created successfully.');
            redirect('tasks/show', ['id' => $taskId]);
        }

        return View::render('tasks/create', [
            'pageTitle' => 'Create Task',
            'categories' => $categories,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function view(): string
    {
        Auth::requireRole(['tasker', 'admin']);

        $taskId = (int) ($_GET['id'] ?? 0);

        if ($taskId <= 0) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/browse');
        }

        $task = $this->tasks->findOpenById($taskId);

        if ($task === null) {
            Session::flash('error', 'That task is not available for discovery.');
            redirect('tasks/browse');
        }

        return View::render('tasks/view', [
            'pageTitle' => 'Browse Task',
            'task' => $task,
            'existingBid' => Auth::role() === 'tasker' ? $this->bids->findForTasker($taskId, (int) Auth::id()) : null,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function edit(): string
    {
        Auth::requireRole('client');

        $taskId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

        if ($taskId <= 0) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/index');
        }

        $task = $this->tasks->findByIdForClient($taskId, (int) Auth::id());

        if ($task === null) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/index');
        }

        if ((int) $task['is_active'] !== 1 || $task['status'] !== 'open') {
            Session::flash('error', 'Only active open tasks can be edited.');
            redirect('tasks/show', ['id' => $taskId]);
        }

        $categories = $this->categories->allActive();

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verifyRequest();
            $input = Validator::trim($_POST);
            Session::setOldInput($input);
            $fieldErrors = Validator::taskFields($input, $this->categories->activeIds());

            if ($fieldErrors !== []) {
                return View::render('tasks/edit', [
                    'pageTitle' => 'Edit Task',
                    'categories' => $categories,
                    'task' => $task,
                    'errors' => Validator::flattenFieldErrors($fieldErrors),
                    'fieldErrors' => $fieldErrors,
                ]);
            }

            $this->tasks->update([
                'id' => $taskId,
                'client_id' => (int) Auth::id(),
                'category_id' => (int) $input['category_id'],
                'title' => normalize_whitespace((string) $input['title']),
                'description' => normalize_whitespace((string) $input['description']),
                'city' => normalize_whitespace((string) $input['city']),
                'region' => normalize_whitespace((string) ($input['region'] ?? '')),
                'country' => normalize_whitespace((string) $input['country']),
                'budget' => number_format((float) round((float) $input['budget']), 2, '.', ''),
                'scheduled_for' => (string) ($input['scheduled_for'] ?? ''),
            ]);

            Session::clearOldInput();
            Session::flash('success', 'Task updated successfully.');
            redirect('tasks/show', ['id' => $taskId]);
        }

        return View::render('tasks/edit', [
            'pageTitle' => 'Edit Task',
            'categories' => $categories,
            'task' => $task,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function cancel(): string
    {
        Auth::requireRole('client');
        verifyPostRequest('tasks/index');
        $taskId = (int) ($_POST['id'] ?? 0);

        if ($taskId <= 0) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/index');
        }

        $task = $this->tasks->findByIdForClient($taskId, (int) Auth::id());

        if ($task === null) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/index');
        }

        if ((int) $task['is_active'] !== 1 || $task['status'] !== 'open') {
            Session::flash('error', 'Only active open tasks can be cancelled.');
            redirect('tasks/show', ['id' => $taskId]);
        }

        $this->tasks->cancel($taskId, (int) Auth::id());
        Session::flash('success', 'Task cancelled successfully.');
        redirect('tasks/index');
    }
}
