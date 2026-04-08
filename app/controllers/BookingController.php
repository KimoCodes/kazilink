<?php

declare(strict_types=1);

final class BookingController
{
    private Booking $bookings;
    private Review $reviews;
    private HiringAgreement $agreements;

    public function __construct()
    {
        $this->bookings = new Booking();
        $this->reviews = new Review();
        $this->agreements = new HiringAgreement();
    }

    public function index(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $pagination = pagination_params($page, 20);

        $bookings = $this->bookings->forUser((int) Auth::id(), (string) Auth::role(), $pagination['limit'], $pagination['offset']);

        return View::render('bookings/index', [
            'pageTitle' => 'Bookings',
            'bookings' => $bookings,
            'agreementsByBooking' => $this->agreementsByBooking($bookings),
            'pagination' => pagination_meta($page, $pagination['per_page'], $this->bookings->countForUser((int) Auth::id(), (string) Auth::role())),
        ]);
    }

    public function show(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $bookingId = (int) ($_GET['id'] ?? 0);

        if ($bookingId <= 0) {
            Session::flash('error', 'Booking not found.');
            redirect('bookings/index');
        }

        $booking = $this->bookings->findVisibleById($bookingId, (int) Auth::id(), (string) Auth::role());

        if ($booking === null) {
            Session::flash('error', 'Booking not found.');
            redirect('bookings/index');
        }

        return View::render('bookings/show', [
            'pageTitle' => 'Booking Details',
            'booking' => $booking,
            'reviews' => $this->reviews->forBooking($bookingId),
            'clientReview' => $this->reviews->findByBookingAndReviewer($bookingId, (int) Auth::id()),
            'agreement' => $this->safeAgreementForBooking($bookingId),
        ]);
    }

    public function complete(): string
    {
        Auth::requireRole('client');
        verifyPostRequest('bookings/index');
        $bookingId = (int) ($_POST['booking_id'] ?? 0);

        if ($bookingId <= 0) {
            Session::flash('error', 'Booking not found.');
            redirect('bookings/index');
        }

        try {
            $this->bookings->completeForClient($bookingId, (int) Auth::id());
            Session::flash('success', 'Booking marked as completed.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('bookings/show', ['id' => $bookingId]);
    }

    private function safeAgreementForBooking(int $bookingId): ?array
    {
        try {
            return $this->agreements->findVisibleByBookingId($bookingId, (int) Auth::id(), (string) Auth::role());
        } catch (Throwable $exception) {
            error_log('Booking agreement lookup error: ' . $exception->getMessage());

            return null;
        }
    }

    private function agreementsByBooking(array $bookings): array
    {
        try {
            return $this->agreements->findVisibleByBookingIds(
                array_map(static fn (array $booking): int => (int) ($booking['id'] ?? 0), $bookings),
                (int) Auth::id(),
                (string) Auth::role()
            );
        } catch (Throwable $exception) {
            error_log('Booking list agreement lookup error: ' . $exception->getMessage());

            return [];
        }
    }
}
