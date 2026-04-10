<?php

declare(strict_types=1);

final class EmailTemplateCatalog
{
    public function render(string $templateName, array $data): array
    {
        $definition = $this->definition($templateName);
        $missing = $this->missingVariables($definition['required'], $data);
        if ($missing !== []) {
            throw new RuntimeException('Missing template variables: ' . implode(', ', $missing));
        }

        $html = $this->renderHtml($templateName, $data);
        $text = $this->toPlainText($html);
        $subject = trim((string) ($data['subject'] ?? ''));
        if ($subject === '') {
            throw new RuntimeException('Email subject is required.');
        }

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => $text,
            'required' => $definition['required'],
        ];
    }

    public function validatePayload(string $templateName, array $data): array
    {
        $definition = $this->definition($templateName);

        return $this->missingVariables($definition['required'], $data);
    }

    private function definition(string $templateName): array
    {
        $definitions = [
            'payment_submitted_admin' => ['required' => ['subject', 'platform_name', 'plan_name', 'amount', 'submitted_at', 'deadline_at', 'intended_activation_at', 'user_name']],
            'payment_submitted_user' => ['required' => ['subject', 'platform_name', 'plan_name', 'amount', 'submitted_at', 'deadline_at', 'intended_activation_at']],
            'payment_approved' => ['required' => ['subject', 'platform_name', 'plan_name', 'amount', 'reviewed_at', 'intended_activation_at']],
            'payment_rejected' => ['required' => ['subject', 'platform_name', 'plan_name', 'amount', 'reviewed_at', 'deadline_at', 'intended_activation_at', 'rejection_reason']],
            'generic_notification' => ['required' => ['subject', 'platform_name', 'message']],
        ];

        if (!isset($definitions[$templateName])) {
            throw new RuntimeException('Unknown email template: ' . $templateName);
        }

        return $definitions[$templateName];
    }

    private function renderHtml(string $templateName, array $data): string
    {
        $safeTemplate = preg_replace('/[^a-z0-9_\-]/i', '', $templateName) ?? '';
        $templatePath = BASE_PATH . '/templates/emails/' . $safeTemplate . '.php';
        if ($safeTemplate === '' || !is_file($templatePath)) {
            throw new RuntimeException('Email template not found.');
        }

        $data['platform_name'] = (string) ($data['platform_name'] ?? app_config('name', 'Kazilink'));
        $data['support_email'] = (string) ($data['support_email'] ?? email_config('support_email', app_config('contact.email', 'support@yourdomain.com')));
        $data['support_phone'] = (string) ($data['support_phone'] ?? email_config('support_phone', app_config('contact.phone', '+250 000 000 000')));

        ob_start();
        require $templatePath;

        return (string) ob_get_clean();
    }

    private function toPlainText(string $html): string
    {
        $decoded = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], PHP_EOL, $html)), ENT_QUOTES, 'UTF-8');
        $decoded = preg_replace("/[\r\n]{3,}/", PHP_EOL . PHP_EOL, $decoded) ?? $decoded;

        return trim($decoded);
    }

    private function missingVariables(array $required, array $data): array
    {
        $missing = [];
        foreach ($required as $key) {
            if (!array_key_exists($key, $data) || trim((string) $data[$key]) === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}
