<?php

declare(strict_types=1);

final class BidController
{
    private Task $tasks;
    private Bid $bids;
    private Booking $bookings;

    public function __construct()
    {
        $this->tasks = new Task();
        $this->bids = new Bid();
        $this->bookings = new Booking();
    }

    public function create(): string
    {
        Auth::requireRole('tasker');
        SubscriptionAccess::requirePaidAccess('Submitting bids requires an active subscription after your free trial.');
        verifyPostRequest('tasks/browse');
        $taskId = (int) ($_POST['task_id'] ?? 0);

        if ($taskId <= 0) {
            Session::flash('error', 'Task not found.');
            redirect('tasks/browse');
        }

        $task = $this->tasks->findOpenById($taskId, getUserPlan((int) Auth::id()));

        if ($task === null) {
            Session::flash('error', 'That task is not available for bidding.');
            redirect('tasks/browse');
        }

        if ((int) $task['client_id'] === (int) Auth::id()) {
            Session::flash('error', 'You cannot bid on your own task.');
            redirect('tasks/view', ['id' => $taskId]);
        }

        if ($this->bids->findForTasker($taskId, (int) Auth::id()) !== null) {
            Session::flash('error', 'You have already submitted a bid for this task.');
            redirect('tasks/view', ['id' => $taskId]);
        }

        $applicationAccess = canApplyToJob((int) Auth::id());
        if (!$applicationAccess['allowed']) {
            Session::flash('error', (string) $applicationAccess['message']);
            redirect('subscriptions/index');
        }

        $input = Validator::trim($_POST);
        Session::setOldInput([
            'bid_amount' => (string) ($input['amount'] ?? ''),
            'bid_message' => (string) ($input['message'] ?? ''),
        ]);
        $fieldErrors = Validator::bidFields($input);

        if ($fieldErrors !== []) {
            return $this->renderTaskViewWithBidFormErrors($task, $fieldErrors);
        }

        try {
            $this->bids->createForTaskerOnOpenTask([
                'task_id' => $taskId,
                'tasker_id' => (int) Auth::id(),
                'amount' => number_format((float) $input['amount'], 2, '.', ''),
                'message' => (string) ($input['message'] ?? ''),
            ], (int) ($applicationAccess['limit'] ?? 0));
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('tasks/view', ['id' => $taskId]);
        }

        Session::clearOldInput();
        Session::flash('success', 'Your bid has been submitted.');
        redirect('tasks/view', ['id' => $taskId]);
    }

    public function accept(): string
    {
        Auth::requireRole('client');
        verifyPostRequest('tasks/index');
        $bidId = (int) ($_POST['bid_id'] ?? 0);

        if ($bidId <= 0) {
            Session::flash('error', 'Bid not found.');
            redirect('tasks/index');
        }

        $bid = $this->bids->findAcceptableForClient($bidId, (int) Auth::id());

        if ($bid === null) {
            Session::flash('error', 'Bid not found.');
            redirect('tasks/index');
        }

        if ((int) $bid['task_is_active'] !== 1 || $bid['task_status'] !== 'open' || $bid['status'] !== 'pending') {
            Session::flash('error', 'Only pending bids on active open tasks can be accepted.');
            redirect('tasks/show', ['id' => (int) $bid['task_id']]);
        }

        try {
            $bookingId = $this->bookings->createFromBid($bid);
            Session::flash('success', 'Bid accepted and booking created.');
            redirect('bookings/show', ['id' => $bookingId]);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('tasks/show', ['id' => (int) $bid['task_id']]);
        }
    }

    private function renderTaskViewWithBidFormErrors(array $task, array $fieldErrors): string
    {
        return View::render('tasks/view', [
            'pageTitle' => 'Browse Task',
            'task' => $task,
            'existingBid' => null,
            'viewerPlan' => getUserPlan((int) Auth::id()),
            'applicationAccess' => canApplyToJob((int) Auth::id()),
            'errors' => Validator::flattenFieldErrors($fieldErrors),
            'fieldErrors' => $fieldErrors,
        ]);
    }
}
