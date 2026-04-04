<?php

declare(strict_types=1);

final class MarketingController
{
    public function about(): string
    {
        return View::render('marketing/about', [
            'pageTitle' => 'About',
            'plans' => array_values(pricing_plans()),
            'paymentsEnabled' => payments_enabled(),
        ]);
    }

    public function pricing(): string
    {
        return View::render('marketing/pricing', [
            'pageTitle' => 'Pricing',
            'plans' => array_values(pricing_plans()),
            'paymentsEnabled' => payments_enabled(),
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

        if (trim((string) ($input['company_website'] ?? '')) !== '') {
            Session::flash('success', 'Thanks. You are on the list.');
            redirect($redirectRoute);
        }

        $fieldErrors = Validator::newsletterFields($input);

        if ($fieldErrors !== []) {
            Session::flash('error', (string) ($fieldErrors['email'][0] ?? 'Please enter a valid email address.'));
            redirect($redirectRoute);
        }

        $saved = LeadCapture::append('newsletter', [
            'email' => (string) $input['email'],
            'audience' => normalize_whitespace((string) ($input['audience'] ?? '')),
        ]);

        Session::flash($saved ? 'success' : 'warning', $saved
            ? 'Thanks. We will share product and launch updates with you.'
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

        $saved = LeadCapture::append('contact', [
            'name' => (string) $input['name'],
            'email' => (string) $input['email'],
            'company' => (string) $input['company'],
            'topic' => (string) $input['topic'],
            'message' => (string) $input['message'],
        ]);

        Session::clearOldInput();
        Session::flash($saved ? 'success' : 'warning', $saved
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
