<?php

declare(strict_types=1);

final class ReviewController
{
    private Booking $bookings;
    private Review $reviews;

    public function __construct()
    {
        $this->bookings = new Booking();
        $this->reviews = new Review();
    }

    public function create(): string
    {
        Auth::requireRole('client');

        $bookingId = (int) ($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

        if ($bookingId <= 0) {
            Session::flash('error', 'Booking not found.');
            redirect('bookings/index');
        }

        $booking = $this->bookings->findVisibleById($bookingId, (int) Auth::id(), (string) Auth::role());

        if ($booking === null || (int) $booking['client_id'] !== (int) Auth::id()) {
            Session::flash('error', 'Booking not found.');
            redirect('bookings/index');
        }

        if (!$this->bookings->canClientReview($bookingId, (int) Auth::id())) {
            Session::flash('error', 'You can only review completed bookings.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        $existingReview = $this->reviews->findByBookingAndReviewer($bookingId, (int) Auth::id());

        if ($existingReview !== null) {
            Session::flash('error', 'You have already submitted a review for this booking.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Csrf::verifyRequest();
            $input = Validator::trim($_POST);
            Session::setOldInput([
                'review_rating' => (string) ($input['rating'] ?? ''),
                'review_comment' => (string) ($input['comment'] ?? ''),
            ]);
            $fieldErrors = Validator::reviewFields($input);

            if ($fieldErrors !== []) {
                return View::render('reviews/create', [
                    'pageTitle' => 'Leave Review',
                    'booking' => $booking,
                    'errors' => Validator::flattenFieldErrors($fieldErrors),
                    'fieldErrors' => $fieldErrors,
                ]);
            }

            $this->reviews->create([
                'booking_id' => $bookingId,
                'reviewer_id' => (int) Auth::id(),
                'reviewee_id' => (int) $booking['tasker_id'],
                'rating' => (int) $input['rating'],
                'comment' => (string) ($input['comment'] ?? ''),
            ]);

            Session::clearOldInput();
            Session::flash('success', 'Review submitted successfully.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        return View::render('reviews/create', [
            'pageTitle' => 'Leave Review',
            'booking' => $booking,
            'errors' => [],
            'fieldErrors' => [],
        ]);
    }
}
