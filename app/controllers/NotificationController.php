<?php

declare(strict_types=1);

final class NotificationController
{
    private Notification $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    public function index(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $recipientType = Auth::role() === 'admin' ? 'admin' : 'user';

        return View::render('notifications/index', [
            'pageTitle' => 'Notifications',
            'notifications' => $this->notifications->forRecipient($recipientType, (int) Auth::id(), 100),
        ]);
    }

    public function markRead(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        verifyPostRequest('notifications/index');

        $notificationId = (int) ($_POST['notification_id'] ?? 0);
        if ($notificationId > 0) {
            $recipientType = Auth::role() === 'admin' ? 'admin' : 'user';
            $this->notifications->markRead($notificationId, $recipientType, (int) Auth::id());
        }

        redirect('notifications/index');
    }
}

