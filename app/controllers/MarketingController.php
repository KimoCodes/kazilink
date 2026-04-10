<?php

declare(strict_types=1);

final class MarketingController
{
    private Plan $plans;

    public function __construct()
    {
        $this->plans = new Plan();
    }

    public function about(): string
    {
        return View::render('marketing/about', [
            'pageTitle' => 'About',
        ]);
    }

    public function pricing(): string
    {
        return View::render('marketing/pricing', [
            'pageTitle' => 'Pricing',
            'plans' => $this->plans->allActive(),
        ]);
    }

    public function contact(): string
    {
        Session::clearOldInput();
        Session::put('contact_form_started_at', time());

        return View::render('marketing/contact', [
            'pageTitle' => 'Contact',
            'errors' => [],
            'fieldErrors' => [],
            'contact' => app_config('contact', []),
        ]);
    }

    public function newsletter(): string
    {
        verifyPostRequest('home/index');

        $redirectRoute = $this->safeMarketingRoute((string) ($_POST['redirect_route'] ?? 'home/index'));
        $input = Validator::trim($_POST);
        $input['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $input['audience'] = trim((string) ($input['audience'] ?? 'client'));

        if (trim((string) ($input['company_website'] ?? '')) !== '') {
            Session::flash('success', 'Thanks. You are on the list.');
            redirect($redirectRoute);
        }

        $fieldErrors = Validator::newsletterFields($input);

        if ($fieldErrors !== []) {
            Session::flash('error', (string) ($fieldErrors['email'][0] ?? 'Please enter a valid email address.'));
            redirect($redirectRoute);
        }

        if (LeadCapture::newsletterAlreadySubscribed((string) $input['email'])) {
            Session::flash('success', 'You are already on the updates list. We will only email you when there is something worth sharing.');
            redirect($redirectRoute);
        }

        $audienceMap = [
            'client' => 'Hiring clients',
            'tasker' => 'Taskers',
            'partner' => 'Partners',
        ];
        $audienceCopyMap = [
            'client' => 'updates for hiring clients',
            'tasker' => 'updates for taskers',
            'partner' => 'partner updates',
        ];
        $audienceLabel = $audienceMap[(string) $input['audience']] ?? 'Community';
        $audienceSuccessCopy = $audienceCopyMap[(string) $input['audience']] ?? 'platform updates';
        $subscription = [
            'email' => (string) $input['email'],
            'audience' => normalize_whitespace((string) ($input['audience'] ?? '')),
            'audience_label' => $audienceLabel,
            'source_route' => $redirectRoute,
            'consent_text' => 'Subscriber asked to receive launch notes, product updates, and practical service tips.',
        ];
        $saved = LeadCapture::append('newsletter', $subscription);
        $notified = LeadCapture::deliverNewsletterNotification($subscription);

        Session::flash(($saved || $notified) ? 'success' : 'warning', ($saved || $notified)
            ? 'You are in. We will send occasional ' . $audienceSuccessCopy . ', launch notes, and practical tips.'
            : 'Your email looks good, but we could not save it right now. Please try again in a moment.');

        redirect($redirectRoute);
    }

    public function submitContact(): string
    {
        verifyPostRequest('marketing/contact');

        $input = Validator::trim($_POST);
        $input['name'] = normalize_whitespace((string) ($input['name'] ?? ''));
        $input['email'] = mb_strtolower(trim((string) ($input['email'] ?? '')));
        $input['topic'] = normalize_whitespace((string) ($input['topic'] ?? ''));
        $input['company'] = normalize_whitespace((string) ($input['company'] ?? ''));
        $input['message'] = trim((string) ($input['message'] ?? ''));

        Session::setOldInput([
            'name' => (string) $input['name'],
            'email' => (string) $input['email'],
            'company' => (string) $input['company'],
            'topic' => (string) $input['topic'],
            'message' => (string) $input['message'],
        ]);

        $fieldErrors = Validator::contactFields($input);

        if (trim((string) ($input['website'] ?? '')) !== '') {
            Session::clearOldInput();
            Session::flash('success', 'Thanks for reaching out. We will review your message.');
            redirect('marketing/contact');
        }

        $startedAt = (int) Session::get('contact_form_started_at', 0);

        if ($startedAt > 0 && (time() - $startedAt) < 3) {
            $fieldErrors['message'][] = 'Please take a moment and submit the form again.';
        }

        if ($fieldErrors !== []) {
            return View::render('marketing/contact', [
                'pageTitle' => 'Contact',
                'errors' => Validator::flattenFieldErrors($fieldErrors),
                'fieldErrors' => $fieldErrors,
                'contact' => app_config('contact', []),
            ]);
        }

        $submission = [
            'name' => (string) $input['name'],
            'email' => (string) $input['email'],
            'company' => (string) $input['company'],
            'topic' => (string) $input['topic'],
            'message' => (string) $input['message'],
        ];

        $saved = LeadCapture::append('contact', $submission);
        $emailed = LeadCapture::deliverContactEmail($submission);

        Session::clearOldInput();
        Session::flash(($saved || $emailed) ? 'success' : 'warning', ($saved || $emailed)
            ? 'Thanks for reaching out. We will follow up soon.'
            : 'Your message looks good, but we could not save it right now. Please try again shortly.');
        redirect('marketing/contact');
    }

    private function safeMarketingRoute(string $route): string
    {
        $allowedRoutes = ['home/index', 'marketing/about', 'marketing/pricing', 'marketing/contact'];

        return in_array($route, $allowedRoutes, true) ? $route : 'home/index';
    }
}
