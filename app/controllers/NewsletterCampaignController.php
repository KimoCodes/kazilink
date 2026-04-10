<?php

declare(strict_types=1);

final class NewsletterCampaignController
{
    private NewsletterCampaign $campaigns;
    private LeadCapture $leadCapture;

    public function __construct()
    {
        $this->campaigns = new NewsletterCampaign();
        $this->leadCapture = new LeadCapture();
    }

    public function index(): string
    {
        Auth::requireRole('admin');

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $status = $_GET['status'] ?? null;
        $pagination = pagination_params($page, 20);

        if ($status !== null && !in_array($status, ['draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled'])) {
            $status = null;
        }

        $campaigns = $this->campaigns->all($status, $pagination['limit'], $pagination['offset']);
        $totalCount = $this->campaigns->count($status);

        // Get subscriber counts for each audience
        $subscriberCounts = [
            'all' => count($this->campaigns->getSubscribersByAudience('all')),
            'client' => count($this->campaigns->getSubscribersByAudience('client')),
            'tasker' => count($this->campaigns->getSubscribersByAudience('tasker')),
            'partner' => count($this->campaigns->getSubscribersByAudience('partner')),
        ];

        return View::render('admin/newsletter_campaigns', [
            'pageTitle' => 'Newsletter Campaigns',
            'campaigns' => $campaigns,
            'subscriberCounts' => $subscriberCounts,
            'currentStatus' => $status,
            'pagination' => pagination_meta($page, $pagination['per_page'], $totalCount),
        ]);
    }

    public function create(): string
    {
        Auth::requireRole('admin');

        if (isPostRequest()) {
            Csrf::verifyRequest();

            $input = Validator::trim($_POST);
            $fieldErrors = $this->validateCampaignFields($input);

            if ($fieldErrors !== []) {
                return $this->renderCreatePage($input, $fieldErrors);
            }

            try {
                $campaignData = [
                    'title' => $input['title'],
                    'subject' => $input['subject'],
                    'content' => $input['content'],
                    'audience' => $input['audience'],
                    'status' => 'draft',
                    'created_by' => Auth::id(),
                ];

                $campaignId = $this->campaigns->create($campaignData);

                Logger::info('Newsletter campaign created', [
                    'campaign_id' => $campaignId,
                    'title' => $input['title'],
                    'audience' => $input['audience'],
                    'created_by' => Auth::id(),
                ]);

                Session::flash('success', 'Newsletter campaign created successfully.');
                redirect('admin/newsletter-campaigns');

            } catch (RuntimeException $exception) {
                return $this->renderCreatePage($input, [], [$exception->getMessage()]);
            }
        }

        return $this->renderCreatePage();
    }

    public function edit(): string
    {
        Auth::requireRole('admin');

        $campaignId = (int) ($_GET['id'] ?? 0);

        if ($campaignId <= 0) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        $campaign = $this->campaigns->findById($campaignId);

        if ($campaign === null) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        if ($campaign['status'] !== 'draft' && $campaign['status'] !== 'failed') {
            Session::flash('error', 'Only draft or failed campaigns can be edited.');
            redirect('admin/newsletter-campaigns');
        }

        if (isPostRequest()) {
            Csrf::verifyRequest();

            $input = Validator::trim($_POST);
            $fieldErrors = $this->validateCampaignFields($input);

            if ($fieldErrors !== []) {
                return $this->renderEditPage($campaign, $input, $fieldErrors);
            }

            try {
                $updateData = [
                    'title' => $input['title'],
                    'subject' => $input['subject'],
                    'content' => $input['content'],
                    'audience' => $input['audience'],
                ];

                $updated = $this->campaigns->update($campaignId, $updateData);

                if ($updated) {
                    Logger::info('Newsletter campaign updated', [
                        'campaign_id' => $campaignId,
                        'updated_by' => Auth::id(),
                    ]);

                    Session::flash('success', 'Campaign updated successfully.');
                    redirect('admin/newsletter-campaigns');
                } else {
                    return $this->renderEditPage($campaign, $input, [], ['No changes made to the campaign.']);
                }

            } catch (RuntimeException $exception) {
                return $this->renderEditPage($campaign, $input, [], [$exception->getMessage()]);
            }
        }

        return $this->renderEditPage($campaign);
    }

    public function show(): string
    {
        Auth::requireRole('admin');

        $campaignId = (int) ($_GET['id'] ?? 0);

        if ($campaignId <= 0) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        $campaign = $this->campaigns->findById($campaignId);

        if ($campaign === null) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        $stats = $this->campaigns->getCampaignStats($campaignId);
        $deliveryReport = $this->campaigns->getDeliveryReport($campaignId);
        $subscribers = $this->campaigns->getSubscribersByAudience($campaign['audience']);

        return View::render('admin/newsletter_campaign_show', [
            'pageTitle' => 'Campaign Details',
            'campaign' => $campaign,
            'stats' => $stats,
            'deliveryReport' => $deliveryReport,
            'subscribers' => $subscribers,
        ]);
    }

    public function schedule(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/newsletter-campaigns');

        $campaignId = (int) ($_POST['campaign_id'] ?? 0);
        $scheduledAt = $_POST['scheduled_at'] ?? null;

        if ($campaignId <= 0) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        $campaign = $this->campaigns->findById($campaignId);

        if ($campaign === null) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        if (!in_array($campaign['status'], ['draft', 'failed'])) {
            Session::flash('error', 'Only draft or failed campaigns can be scheduled.');
            redirect('admin/newsletter-campaigns');
        }

        try {
            $scheduleDateTime = null;
            if ($scheduledAt !== null && $scheduledAt !== '') {
                $scheduleDateTime = new DateTimeImmutable($scheduledAt);
                
                if ($scheduleDateTime <= new DateTimeImmutable()) {
                    Session::flash('error', 'Scheduled time must be in the future.');
                    redirect('admin/newsletter-campaigns');
                }
            }

            $scheduled = $this->campaigns->scheduleCampaign($campaignId, $scheduleDateTime);

            if ($scheduled) {
                Logger::info('Newsletter campaign scheduled', [
                    'campaign_id' => $campaignId,
                    'scheduled_at' => $scheduleDateTime?->format('Y-m-d H:i:s'),
                    'scheduled_by' => Auth::id(),
                ]);

                $message = $scheduleDateTime 
                    ? 'Campaign scheduled for ' . $scheduleDateTime->format('M j, Y \a\t g:i A')
                    : 'Campaign scheduled for immediate sending';
                
                Session::flash('success', $message);
            } else {
                Session::flash('error', 'Failed to schedule campaign.');
            }

        } catch (Exception $exception) {
            Session::flash('error', 'Error scheduling campaign: ' . $exception->getMessage());
        }

        redirect('admin/newsletter-campaigns');
    }

    public function send(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/newsletter-campaigns');

        $campaignId = (int) ($_POST['campaign_id'] ?? 0);

        if ($campaignId <= 0) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        $campaign = $this->campaigns->findById($campaignId);

        if ($campaign === null) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        if ($campaign['status'] !== 'scheduled') {
            Session::flash('error', 'Only scheduled campaigns can be sent.');
            redirect('admin/newsletter-campaigns');
        }

        try {
            $results = $this->campaigns->sendCampaign($campaignId);

            $message = "Campaign sent to {$results['total']} subscribers. "
                     . "Success: {$results['sent']}, Failed: {$results['failed']}";
            
            if ($results['failed'] > 0) {
                Session::flash('warning', $message);
            } else {
                Session::flash('success', $message);
            }

            Logger::info('Newsletter campaign sent manually', [
                'campaign_id' => $campaignId,
                'results' => $results,
                'sent_by' => Auth::id(),
            ]);

        } catch (Exception $exception) {
            Session::flash('error', 'Error sending campaign: ' . $exception->getMessage());
            Logger::error('Newsletter campaign manual send failed', [
                'campaign_id' => $campaignId,
                'error' => $exception->getMessage(),
                'sent_by' => Auth::id(),
            ]);
        }

        redirect('admin/newsletter-campaigns');
    }

    public function delete(): string
    {
        Auth::requireRole('admin');
        verifyPostRequest('admin/newsletter-campaigns');

        $campaignId = (int) ($_POST['campaign_id'] ?? 0);

        if ($campaignId <= 0) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        $campaign = $this->campaigns->findById($campaignId);

        if ($campaign === null) {
            Session::flash('error', 'Campaign not found.');
            redirect('admin/newsletter-campaigns');
        }

        if (!in_array($campaign['status'], ['draft', 'failed'])) {
            Session::flash('error', 'Only draft or failed campaigns can be deleted.');
            redirect('admin/newsletter-campaigns');
        }

        try {
            $deleted = $this->campaigns->delete($campaignId);

            if ($deleted) {
                Logger::info('Newsletter campaign deleted', [
                    'campaign_id' => $campaignId,
                    'deleted_by' => Auth::id(),
                ]);

                Session::flash('success', 'Campaign deleted successfully.');
            } else {
                Session::flash('error', 'Failed to delete campaign.');
            }

        } catch (Exception $exception) {
            Session::flash('error', 'Error deleting campaign: ' . $exception->getMessage());
        }

        redirect('admin/newsletter-campaigns');
    }

    private function validateCampaignFields(array $input): array
    {
        $errors = [];

        if (trim($input['title'] ?? '') === '') {
            $errors['title'][] = 'Campaign title is required.';
        } elseif (strlen($input['title']) > 255) {
            $errors['title'][] = 'Campaign title must be less than 255 characters.';
        }

        if (trim($input['subject'] ?? '') === '') {
            $errors['subject'][] = 'Email subject is required.';
        } elseif (strlen($input['subject']) > 255) {
            $errors['subject'][] = 'Email subject must be less than 255 characters.';
        }

        if (trim($input['content'] ?? '') === '') {
            $errors['content'][] = 'Email content is required.';
        }

        if (!in_array($input['audience'] ?? '', ['all', 'client', 'tasker', 'partner'])) {
            $errors['audience'][] = 'Invalid audience selection.';
        }

        return $errors;
    }

    private function renderCreatePage(array $input = [], array $fieldErrors = [], array $errors = []): string
    {
        return View::render('admin/newsletter_campaign_create', [
            'pageTitle' => 'Create Newsletter Campaign',
            'input' => $input,
            'fieldErrors' => $fieldErrors,
            'errors' => $errors,
        ]);
    }

    private function renderEditPage(array $campaign, array $input = [], array $fieldErrors = [], array $errors = []): string
    {
        if (empty($input)) {
            $input = [
                'title' => $campaign['title'],
                'subject' => $campaign['subject'],
                'content' => $campaign['content'],
                'audience' => $campaign['audience'],
            ];
        }

        return View::render('admin/newsletter_campaign_edit', [
            'pageTitle' => 'Edit Newsletter Campaign',
            'campaign' => $campaign,
            'input' => $input,
            'fieldErrors' => $fieldErrors,
            'errors' => $errors,
        ]);
    }
}
