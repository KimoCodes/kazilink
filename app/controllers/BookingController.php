<?php

declare(strict_types=1);

final class BookingController
{
    private Booking $bookings;
    private Review $reviews;
    private Payment $payments;

    public function __construct()
    {
        $this->bookings = new Booking();
        $this->reviews = new Review();
        $this->payments = new Payment();
    }

    public function index(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $bookings = $this->bookings->forUser((int) Auth::id(), (string) Auth::role());

        return View::render('bookings/index', [
            'pageTitle' => 'Bookings',
            'bookings' => $bookings,
            'paymentsByBooking' => $this->safePaymentsByBooking($bookings),
            'paymentsEnabled' => payments_enabled(),
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
            'bookingPayment' => $this->safePaymentForBooking($bookingId),
            'paymentsEnabled' => payments_enabled(),
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

    private function safePaymentForBooking(int $bookingId): ?array
    {
        try {
            return $this->payments->findLatestForBooking($bookingId);
        } catch (Throwable $exception) {
            error_log('Booking payment lookup error: ' . $exception->getMessage());

            return null;
        }
    }

    private function safePaymentsByBooking(array $bookings): array
    {
        try {
            return $this->payments->latestByBookingIds(array_map(
                static fn (array $booking): int => (int) ($booking['id'] ?? 0),
                $bookings
            ));
        } catch (Throwable $exception) {
            error_log('Booking list payment lookup error: ' . $exception->getMessage());

            return [];
        }
    }
}
