<?php

declare(strict_types=1);

final class ProfileController
{
    private Profile $profiles;
    private Review $reviews;
    private Booking $bookings;
    private Bid $bids;
    private Task $tasks;

    private const AVATAR_MAX_BYTES = 2097152;
    private const ALLOWED_AVATAR_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct()
    {
        $this->profiles = new Profile();
        $this->reviews = new Review();
        $this->bookings = new Booking();
        $this->bids = new Bid();
        $this->tasks = new Task();
    }

    public function show(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $userId = (int) Auth::id();
        $role = (string) Auth::role();
        $profile = $this->profiles->findByUserId($userId);

        if ($profile === null) {
            Session::flash('error', 'Your profile could not be loaded.');
            redirect('home/index');
        }

        return View::render('profiles/show', [
            'pageTitle' => 'My Profile',
            'profile' => $profile,
            'profileStats' => $this->buildProfileStats($userId, $role, $profile),
        ]);
    }

    public function edit(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $userId = (int) Auth::id();
        $profile = $this->profiles->findByUserId($userId);

        if ($profile === null) {
            Session::flash('error', 'Your profile could not be loaded.');
            redirect('home/index');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verifyRequest();
            $input = Validator::trim($_POST);

            Session::setOldInput([
                'profile_full_name' => (string) ($input['full_name'] ?? ''),
                'profile_phone' => (string) ($input['phone'] ?? ''),
                'profile_city' => (string) ($input['city'] ?? ''),
                'profile_region' => (string) ($input['region'] ?? ''),
                'profile_country' => (string) ($input['country'] ?? ''),
                'profile_bio' => (string) ($input['bio'] ?? ''),
                'profile_skills_summary' => (string) ($input['skills_summary'] ?? ''),
            ]);

            $fieldErrors = Validator::profileFields($input);
            $avatarPath = $profile['avatar_path'] ?? null;

            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = $this->processAvatarUpload($_FILES['avatar']);

                if (is_array($uploadResult)) {
                    $fieldErrors['avatar'] = array_merge($fieldErrors['avatar'] ?? [], $uploadResult);
                } else {
                    $avatarPath = $uploadResult;
                }
            }

            if ($fieldErrors !== []) {
                return View::render('profiles/edit', [
                    'pageTitle' => 'Edit Profile',
                    'profile' => $profile,
                    'errors' => Validator::flattenFieldErrors($fieldErrors),
                    'fieldErrors' => $fieldErrors,
                ]);
            }

            $this->profiles->updateByUserId($userId, [
                'full_name' => normalize_whitespace((string) ($input['full_name'] ?? '')),
                'phone' => $input['phone'] ?? null,
                'city' => $input['city'] ?? null,
                'region' => $input['region'] ?? null,
                'country' => $input['country'] ?? null,
                'bio' => $input['bio'] !== '' ? $input['bio'] : null,
                'skills_summary' => $input['skills_summary'] !== '' ? $input['skills_summary'] : null,
                'avatar_path' => $avatarPath,
            ]);

            Session::clearOldInput();
            Session::flash('success', 'Profile updated successfully.');
            redirect('profile/show');
        }

        return View::render('profiles/edit', [
            'pageTitle' => 'Edit Profile',
            'profile' => $profile,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }

    public function view(): string
    {
        $taskerId = (int) ($_GET['id'] ?? 0);

        if ($taskerId <= 0) {
            Session::flash('error', 'Tasker profile not found.');
            redirect('home/index');
        }

        $profile = $this->profiles->findTaskerById($taskerId);

        if ($profile === null) {
            Session::flash('error', 'Tasker profile not found.');
            redirect('home/index');
        }

        $sort = (string) ($_GET['sort'] ?? 'newest');
        if (!in_array($sort, ['newest', 'highest', 'lowest'], true)) {
            $sort = 'newest';
        }

        return View::render('profiles/view', [
            'pageTitle' => 'Tasker Profile',
            'profile' => $profile,
            'sort' => $sort,
            'reviews' => $this->reviews->listByTaskerId($taskerId, $sort),
            'reviewStats' => $this->reviews->getAggregatesByTaskerId($taskerId),
            'bookingStats' => $this->bookings->getStatsByTaskerId($taskerId),
        ]);
    }

    public function taskerDashboard(): string
    {
        Auth::requireRole('tasker');

        $userId = (int) Auth::id();
        $profile = $this->profiles->findByUserId($userId);

        if ($profile === null) {
            Session::flash('error', 'Your profile could not be loaded.');
            redirect('home/index');
        }

        // Get active bids/applications
        $activeBids = $this->bids->findByTaskerId($userId, ['pending', 'accepted']);

        // Get recent bookings (last 10)
        $recentBookings = $this->bookings->findByTaskerId($userId, 10);

        // Get statistics
        $stats = [
            'total_bids' => $this->bids->countByTaskerId($userId),
            'active_bids' => count($activeBids),
            'total_bookings' => $this->bookings->countByTaskerId($userId),
            'completed_bookings' => $this->bookings->countByTaskerIdAndStatus($userId, 'completed'),
            'total_earnings' => $this->bookings->getTotalEarningsByTaskerId($userId),
            'average_rating' => $this->reviews->getAverageRatingByTaskerId($userId),
            'review_count' => $this->reviews->countByTaskerId($userId),
        ];

        return View::render('profiles/tasker-dashboard', [
            'pageTitle' => 'Tasker Dashboard',
            'profile' => $profile,
            'activeBids' => $activeBids,
            'recentBookings' => $recentBookings,
            'stats' => $stats,
        ]);
    }

    private function processAvatarUpload(array $file): array|string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['Avatar upload failed. Please try again.'];
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            return ['Invalid avatar upload.'];
        }

        if ($file['size'] > self::AVATAR_MAX_BYTES) {
            return ['Avatar must be 2MB or smaller.'];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime === false || !array_key_exists($mime, self::ALLOWED_AVATAR_MIME)) {
            return ['Avatar must be a JPEG, PNG, or WebP image.'];
        }

        $extension = self::ALLOWED_AVATAR_MIME[$mime];
        $filename = sprintf('%s.%s', sha1(uniqid((string) Auth::id(), true) . microtime(true)), $extension);
        $destinationDirectory = BASE_PATH . '/public/uploads/profiles';

        if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true) && !is_dir($destinationDirectory)) {
            return ['Unable to save avatar image.'];
        }

        $destinationPath = $destinationDirectory . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destinationPath)) {
            return ['Unable to save avatar image.'];
        }

        return 'uploads/profiles/' . $filename;
    }

    private function buildProfileStats(int $userId, string $role, array $profile): array
    {
        if ($role === 'tasker') {
            $bookingStats = $this->bookings->getStatsByTaskerId($userId);
            $reviewCount = $this->reviews->countByTaskerId($userId);
            $averageRating = $this->reviews->getAverageRatingByTaskerId($userId);

            return [
                [
                    'label' => 'Completed jobs',
                    'value' => (string) $bookingStats['completed_jobs'],
                    'note' => 'Finished through confirmed bookings',
                ],
                [
                    'label' => 'Average rating',
                    'value' => $reviewCount > 0 ? number_format($averageRating, 1) . '/5' : 'No ratings yet',
                    'note' => $reviewCount > 0 ? sprintf('%d public review%s', $reviewCount, $reviewCount === 1 ? '' : 's') : 'Client feedback appears after completed bookings',
                ],
                [
                    'label' => 'Total earnings',
                    'value' => moneyRwf($this->bookings->getTotalEarningsByTaskerId($userId)),
                    'note' => 'Based on completed bookings only',
                ],
            ];
        }

        if ($role === 'client') {
            $tasks = $this->tasks->forClient($userId);
            $openTasks = count(array_filter($tasks, static fn (array $task): bool => (string) $task['status'] === 'open'));
            $activeBookings = count(array_filter($tasks, static fn (array $task): bool => (string) $task['status'] === 'booked'));
            $completedTasks = count(array_filter($tasks, static fn (array $task): bool => (string) $task['status'] === 'completed'));

            return [
                [
                    'label' => 'Open tasks',
                    'value' => (string) $openTasks,
                    'note' => 'Live task posts still accepting bids',
                ],
                [
                    'label' => 'Active bookings',
                    'value' => (string) $activeBookings,
                    'note' => 'Tasks already matched with a tasker',
                ],
                [
                    'label' => 'Completed tasks',
                    'value' => (string) $completedTasks,
                    'note' => 'Jobs finished and ready for review history',
                ],
            ];
        }

        $completedFields = count(array_filter([
            $profile['phone'] ?? null,
            $profile['city'] ?? null,
            $profile['country'] ?? null,
            $profile['bio'] ?? null,
            $profile['skills_summary'] ?? null,
            $profile['avatar_path'] ?? null,
        ], static fn ($value): bool => $value !== null && trim((string) $value) !== ''));

        return [
            [
                'label' => 'Role',
                'value' => ucfirst($role),
                'note' => 'Current workspace access level',
            ],
            [
                'label' => 'Profile readiness',
                'value' => sprintf('%d/6', $completedFields),
                'note' => 'Filled profile sections for a stronger internal demo',
            ],
            [
                'label' => 'Account visibility',
                'value' => 'Internal',
                'note' => 'Admin profiles are not public marketplace listings',
            ],
        ];
    }
}
