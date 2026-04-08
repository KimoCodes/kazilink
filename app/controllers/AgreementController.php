<?php

declare(strict_types=1);

final class AgreementController
{
    private HiringAgreement $agreements;
    private Dispute $disputes;

    public function __construct()
    {
        $this->agreements = new HiringAgreement();
        $this->disputes = new Dispute();
    }

    public function review(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $agreementId = (int) ($_GET['id'] ?? 0);

        if ($agreementId <= 0) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        $agreement = $this->agreements->findVisibleById($agreementId, (int) Auth::id(), (string) Auth::role());

        if ($agreement === null) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        return View::render('agreements/review', [
            'pageTitle' => 'Hiring Agreement',
            'agreement' => $agreement,
            'events' => $this->agreements->eventsForAgreement((int) $agreement['id']),
            'disputes' => $this->disputes->forAgreement((int) $agreement['id']),
            'fieldErrors' => [],
        ]);
    }

    public function accept(): string
    {
        Auth::requireRole(['client', 'tasker']);
        verifyPostRequest('bookings/index');

        $agreementId = (int) ($_POST['agreement_id'] ?? 0);

        if ($agreementId <= 0) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        $agreement = $this->agreements->findVisibleById($agreementId, (int) Auth::id(), (string) Auth::role());

        if ($agreement === null) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        $input = Validator::trim($_POST);
        Session::setOldInput($input);
        $fieldErrors = Validator::agreementAcceptanceFields($input);

        if ($fieldErrors !== []) {
            return View::render('agreements/review', [
                'pageTitle' => 'Hiring Agreement',
                'agreement' => $agreement,
                'events' => $this->agreements->eventsForAgreement((int) $agreement['id']),
                'disputes' => $this->disputes->forAgreement((int) $agreement['id']),
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
            ]);
        }

        try {
            $this->agreements->accept($agreementId, (int) Auth::id(), [
                'ip_address' => request_ip(),
                'user_agent' => request_user_agent(),
            ]);
            Session::clearOldInput();
            Session::flash('success', 'Agreement acceptance recorded.');
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('agreements/review', ['id' => $agreementId]);
    }

    public function download(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $agreementId = (int) ($_GET['id'] ?? 0);

        if ($agreementId <= 0) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        $agreement = $this->agreements->findVisibleById($agreementId, (int) Auth::id(), (string) Auth::role());

        if ($agreement === null) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        if (
            (string) Auth::role() !== 'admin'
            && !in_array((string) $agreement['status'], ['accepted', 'disputed'], true)
        ) {
            Session::flash('error', 'The agreement can only be downloaded after both parties accept it.');
            redirect('agreements/review', ['id' => $agreementId]);
        }

        $events = $this->agreements->eventsForAgreement((int) $agreement['id']);

        header('Content-Type: text/html; charset=UTF-8');

        return View::renderContent('agreements/print', [
            'agreement' => $agreement,
            'events' => $events,
        ]);
    }

    public function downloadDispute(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $disputeId = (int) ($_GET['id'] ?? 0);

        if ($disputeId <= 0) {
            Session::flash('error', 'Dispute not found.');
            redirect('bookings/index');
        }

        $dispute = $this->disputes->findVisibleById($disputeId, (int) Auth::id(), (string) Auth::role());

        if ($dispute === null) {
            Session::flash('error', 'Dispute not found.');
            redirect('bookings/index');
        }

        header('Content-Type: text/html; charset=UTF-8');

        return View::renderContent('disputes/print', [
            'dispute' => $dispute,
        ]);
    }

    public function verify(): string
    {
        $agreementUid = trim((string) ($_GET['agreement_uid'] ?? ''));

        if ($agreementUid === '') {
            http_response_code(404);

            return View::render('agreements/verify', [
                'pageTitle' => 'Verify Agreement',
                'agreement' => null,
            ]);
        }

        $agreement = $this->agreements->findPublicByUid($agreementUid);

        if ($agreement === null) {
            http_response_code(404);
        }

        return View::render('agreements/verify', [
            'pageTitle' => 'Verify Agreement',
            'agreement' => $agreement,
        ]);
    }

    public function createDispute(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);
        verifyPostRequest('bookings/index');

        $agreementId = (int) ($_POST['agreement_id'] ?? 0);

        if ($agreementId <= 0) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        $agreement = $this->agreements->findVisibleById($agreementId, (int) Auth::id(), (string) Auth::role());

        if ($agreement === null) {
            Session::flash('error', 'Agreement not found.');
            redirect('bookings/index');
        }

        if (!$this->isWithinDisputeWindow($agreement)) {
            Session::flash('error', 'The dispute reporting window for this agreement has expired.');
            redirect('agreements/review', ['id' => $agreementId]);
        }

        $input = Validator::trim($_POST);
        Session::setOldInput($input);
        $fieldErrors = Validator::disputeFields($input);

        if ($fieldErrors !== []) {
            return View::render('agreements/review', [
                'pageTitle' => 'Hiring Agreement',
                'agreement' => $agreement,
                'events' => $this->agreements->eventsForAgreement((int) $agreement['id']),
                'disputes' => $this->disputes->forAgreement((int) $agreement['id']),
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
            ]);
        }

        try {
            $disputeId = $this->disputes->create(
                $agreementId,
                (int) Auth::id(),
                (string) $input['type'],
                trim((string) $input['description'])
            );
            Session::clearOldInput();
            Session::flash('success', 'Issue reported and attached to the agreement record.');
            redirect('disputes/show', ['id' => $disputeId]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('agreements/review', ['id' => $agreementId]);
        }
    }

    public function showDispute(): string
    {
        Auth::requireRole(['client', 'tasker', 'admin']);

        $disputeId = (int) ($_GET['id'] ?? 0);

        if ($disputeId <= 0) {
            Session::flash('error', 'Dispute not found.');
            redirect('bookings/index');
        }

        $dispute = $this->disputes->findVisibleById($disputeId, (int) Auth::id(), (string) Auth::role());

        if ($dispute === null) {
            Session::flash('error', 'Dispute not found.');
            redirect('bookings/index');
        }

        return View::render('disputes/show', [
            'pageTitle' => 'Issue Report',
            'dispute' => $dispute,
        ]);
    }

    private function isWithinDisputeWindow(array $agreement): bool
    {
        $reference = trim((string) ($agreement['completed_at'] ?? ''));

        if ($reference === '') {
            $reference = trim((string) ($agreement['start_datetime'] ?? ''));
        }

        if ($reference === '') {
            $reference = trim((string) ($agreement['booked_at'] ?? ''));
        }

        if ($reference === '') {
            return true;
        }

        $referenceTimestamp = strtotime($reference);

        if ($referenceTimestamp === false) {
            return true;
        }

        $windowHours = max(1, (int) ($agreement['dispute_window_hours'] ?? 48));
        $deadline = $referenceTimestamp + ($windowHours * 3600);

        return time() <= $deadline;
    }
}
