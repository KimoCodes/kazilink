<?php

declare(strict_types=1);

final class HomeController
{
    private User $users;
    private Task $tasks;
    private Category $categories;
    private Ad $ads;

    public function __construct()
    {
        $this->users = new User();
        $this->tasks = new Task();
        $this->categories = new Category();
        $this->ads = new Ad();
    }

    public function index(): string
    {
        if (Auth::check()) {
            redirect($this->homeRouteForRole(Auth::role()));
        }

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
            'ads' => $this->ads->activeByPlacement('home', 2),
        ]);
    }

    private function homeRouteForRole(?string $role): string
    {
        return match ($role) {
            'client' => 'tasks/index',
            'tasker' => 'tasker/dashboard',
            'admin' => 'admin/dashboard',
            default => 'home/index',
        };
    }
}
