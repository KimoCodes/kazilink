<?php

declare(strict_types=1);

final class PaymentController
{
    private Payment $payments;

    public function __construct()
    {
        $this->payments = new Payment();
    }

    public function checkout(): string
    {
        verifyPostRequest('marketing/pricing');

        $plan = pricing_plan((string) ($_POST['plan_id'] ?? ''));

        if ($plan === null) {
            Session::flash('error', 'That pricing plan is not available.');
            redirect('marketing/pricing');
        }

        if (!payments_enabled()) {
            Session::flash('error', 'Stripe is not configured yet. Add your Stripe keys to continue.');
            redirect('marketing/pricing');
        }

        $currentUser = Auth::user();

        try {
            $gateway = new StripeCheckout();
            $session = $gateway->createCheckoutSession($plan, [
                'success_url' => absolute_url('payments/success') . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => absolute_url('payments/cancel', ['plan' => (string) $plan['id']]),
                'client_reference_id' => $plan['id'] . '-' . date('YmdHis'),
                'customer_email' => (string) ($currentUser['email'] ?? ''),
                'user_id' => (string) ($currentUser['id'] ?? ''),
            ]);
            $this->payments->createFromCheckoutSession($session, isset($currentUser['id']) ? (int) $currentUser['id'] : null);
        } catch (Throwable $exception) {
            error_log('Stripe checkout error: ' . $exception->getMessage());
            Session::flash('error', 'We could not start checkout right now. Please try again.');
            redirect('marketing/pricing');
        }

        $checkoutUrl = trim((string) ($session['url'] ?? ''));

        if ($checkoutUrl === '') {
            Session::flash('error', 'Stripe did not return a checkout link.');
            redirect('marketing/pricing');
        }

        header('Location: ' . $checkoutUrl);
        exit;
    }

    public function success(): string
    {
        $sessionId = trim((string) ($_GET['session_id'] ?? ''));
        $checkoutSession = null;
        $checkoutError = null;
        $localPayment = null;

        if ($sessionId !== '' && payments_enabled()) {
            try {
                $checkoutSession = (new StripeCheckout())->retrieveCheckoutSession($sessionId);
                $this->payments->upsertFromCheckoutSession($checkoutSession);
            } catch (Throwable $exception) {
                error_log('Stripe success retrieval error: ' . $exception->getMessage());
                $checkoutError = 'Payment completed, but live confirmation details could not be loaded.';
            }
        }

        if ($sessionId !== '') {
            try {
                $localPayment = $this->payments->findByCheckoutSessionId($sessionId);
            } catch (Throwable $exception) {
                error_log('Payment lookup error: ' . $exception->getMessage());
                $localPayment = null;
            }
        }

        return View::render('payments/success', [
            'pageTitle' => 'Payment Success',
            'checkoutSession' => $checkoutSession,
            'checkoutError' => $checkoutError,
            'localPayment' => $localPayment,
        ]);
    }

    public function cancel(): string
    {
        return View::render('payments/cancel', [
            'pageTitle' => 'Payment Cancelled',
            'selectedPlan' => pricing_plan((string) ($_GET['plan'] ?? '')),
        ]);
    }

    public function checkoutBooking(): string
    {
        Auth::requireRole('client');
        verifyPostRequest('bookings/index');

        $bookingId = (int) ($_POST['booking_id'] ?? 0);

        if ($bookingId <= 0) {
            Session::flash('error', 'Booking not found.');
            redirect('bookings/index');
        }

        $booking = (new Booking())->findVisibleById($bookingId, (int) Auth::id(), 'client');

        if ($booking === null) {
            Session::flash('error', 'Booking not found.');
            redirect('bookings/index');
        }

        if ((string) $booking['status'] !== 'completed') {
            Session::flash('error', 'Only completed bookings can be paid.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        if (!payments_enabled()) {
            Session::flash('error', 'Stripe is not configured yet. Add your Stripe keys to continue.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        $existingPayment = null;

        try {
            $existingPayment = $this->payments->findLatestForBooking($bookingId);
        } catch (Throwable $exception) {
            error_log('Existing booking payment lookup error: ' . $exception->getMessage());
        }

        if (is_array($existingPayment) && (string) ($existingPayment['status'] ?? '') === 'paid') {
            Session::flash('success', 'This completed booking has already been paid.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        $agreedAmount = (int) round((float) ($booking['agreed_amount'] ?? $booking['budget'] ?? 0));

        if ($agreedAmount <= 0) {
            Session::flash('error', 'This booking does not have a valid amount to charge.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        $customer = Auth::user();
        $sessionDescriptor = [
            'id' => 'booking-' . $bookingId,
            'name' => 'Payment for ' . (string) $booking['title'],
            'amount' => $agreedAmount,
            'description' => 'Completed booking payment for ' . (string) $booking['tasker_name'],
        ];

        try {
            $gateway = new StripeCheckout();
            $session = $gateway->createCheckoutSession($sessionDescriptor, [
                'success_url' => absolute_url('payments/success') . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => absolute_url('bookings/show', ['id' => $bookingId]),
                'client_reference_id' => 'booking-' . $bookingId . '-' . date('YmdHis'),
                'customer_email' => (string) ($customer['email'] ?? ''),
                'user_id' => (string) ($customer['id'] ?? ''),
                'metadata' => [
                    'booking_id' => (string) $bookingId,
                    'task_id' => (string) ((int) ($booking['task_id'] ?? 0)),
                    'plan_id' => 'booking-' . $bookingId,
                    'plan_name' => 'Payment for ' . (string) $booking['title'],
                ],
            ]);
            $this->payments->createFromCheckoutSession($session, isset($customer['id']) ? (int) $customer['id'] : null);
        } catch (Throwable $exception) {
            error_log('Booking checkout error: ' . $exception->getMessage());
            Session::flash('error', 'We could not start payment for this completed task right now.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        $checkoutUrl = trim((string) ($session['url'] ?? ''));

        if ($checkoutUrl === '') {
            Session::flash('error', 'Stripe did not return a checkout link.');
            redirect('bookings/show', ['id' => $bookingId]);
        }

        header('Location: ' . $checkoutUrl);
        exit;
    }

    public function webhook(): string
    {
        $payload = file_get_contents('php://input');
        $signatureHeader = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');
        $secret = (string) app_config('stripe.webhook_secret', '');

        if (!is_string($payload) || $payload === '') {
            http_response_code(400);
            header('Content-Type: application/json');

            return json_encode(['received' => false, 'error' => 'Missing payload']) ?: '{"received":false}';
        }

        try {
            $event = StripeWebhook::verifyAndDecode($payload, $signatureHeader, $secret);
        } catch (Throwable $exception) {
            error_log('Stripe webhook verification error: ' . $exception->getMessage());
            http_response_code(400);
            header('Content-Type: application/json');

            return json_encode(['received' => false, 'error' => 'Invalid signature']) ?: '{"received":false}';
        }

        $eventType = (string) ($event['type'] ?? '');
        $eventId = (string) ($event['id'] ?? '');
        $object = $event['data']['object'] ?? null;

        try {
            if (
                is_array($object)
                && in_array($eventType, [
                    'checkout.session.completed',
                    'checkout.session.async_payment_succeeded',
                    'checkout.session.async_payment_failed',
                    'checkout.session.expired',
                ], true)
            ) {
                $this->payments->upsertFromCheckoutSession($object, $eventType, null, $eventId !== '' ? $eventId : null);
            }
        } catch (Throwable $exception) {
            error_log('Stripe webhook processing error: ' . $exception->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');

            return json_encode(['received' => false, 'error' => 'Processing failed']) ?: '{"received":false}';
        }

        http_response_code(200);
        header('Content-Type: application/json');

        return json_encode(['received' => true]) ?: '{"received":true}';
    }
}
