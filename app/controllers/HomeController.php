<?php

declare(strict_types=1);

final class HomeController
{
    private User $users;
    private Task $tasks;
    private Category $categories;

    public function __construct()
    {
        $this->users = new User();
        $this->tasks = new Task();
        $this->categories = new Category();
    }

    public function index(): string
    {
        return View::render('home/index', [
            'pageTitle' => 'Home',
            'user' => Auth::user(),
            'role' => Auth::role(),
            'message' => null,
            'statusCode' => 200,
            'marketplaceStats' => [
                'open_tasks' => $this->tasks->countActiveByStatus('open'),
                'active_taskers' => $this->users->countByRole('tasker', true),
                'active_clients' => $this->users->countByRole('client', true),
                'active_categories' => count($this->categories->allActive()),
            ],
            'featuredCategories' => array_slice($this->categories->allActive(), 0, 6),
            'plans' => array_values(pricing_plans()),
            'paymentsEnabled' => payments_enabled(),
        ]);
    }
}
