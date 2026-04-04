<?php

declare(strict_types=1);

final class MessageController
{
    private Booking $bookings;
    private Message $messages;

    public function __construct()
    {
        $this->bookings = new Booking();
        $this->messages = new Message();
    }

    public function poll(): string
    {
        Auth::requireRole(['client', 'tasker']);

        $bookingId = (int) ($_GET['booking_id'] ?? 0);
        $hasAfterId = array_key_exists('after_id', $_GET);
        $afterId = max(0, (int) ($_GET['after_id'] ?? 0));
        $since = (string) ($_GET['since'] ?? '');

        header('Content-Type: application/json');

        if ($bookingId <= 0 || (!$hasAfterId && $since === '')) {
            http_response_code(400);
            return json_encode(['error' => 'Invalid parameters']);
        }

        $booking = $this->bookings->findVisibleById($bookingId, (int) Auth::id(), (string) Auth::role());

        if ($booking === null) {
            http_response_code(404);
            return json_encode(['error' => 'Conversation not found']);
        }

        $newMessages = $hasAfterId
            ? $this->messages->forBookingAfterId($bookingId, $afterId)
            : $this->messages->forBookingSince($bookingId, $since);

        return json_encode([
            'messages' => $newMessages,
            'has_new' => count($newMessages) > 0,
        ]);
    }

    public function thread(): string
    {
        Auth::requireRole(['client', 'tasker']);

        $bookingId = (int) ($_GET['id'] ?? $_POST['booking_id'] ?? 0);

        if ($bookingId <= 0) {
            Session::flash('error', 'Conversation not found.');
            redirect('bookings/index');
        }

        $booking = $this->bookings->findVisibleById($bookingId, (int) Auth::id(), (string) Auth::role());

        if ($booking === null) {
            Session::flash('error', 'Conversation not found.');
            redirect('bookings/index');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verifyRequest();
            $input = Validator::trim($_POST);
            Session::setOldInput([
                'message_body' => (string) ($input['body'] ?? ''),
            ]);
            $fieldErrors = Validator::messageFields($input);

            if ($fieldErrors === []) {
                $this->messages->create([
                    'booking_id' => $bookingId,
                    'sender_id' => (int) Auth::id(),
                    'body' => (string) $input['body'],
                ]);

                Session::clearOldInput();
                Session::flash('success', 'Message sent.');
                redirect('messages/thread', ['id' => $bookingId]);
            }

            return View::render('messages/thread', [
                'pageTitle' => 'Messages',
                'booking' => $booking,
                'messages' => $this->messages->forBooking($bookingId),
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
            ]);
        }

        return View::render('messages/thread', [
            'pageTitle' => 'Messages',
            'booking' => $booking,
            'messages' => $this->messages->forBooking($bookingId),
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }
}
