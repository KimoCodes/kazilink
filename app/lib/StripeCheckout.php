<?php

declare(strict_types=1);

final class StripeCheckout
{
    private string $secretKey;
    private string $apiVersion;

    public function __construct()
    {
        $this->secretKey = trim((string) app_config('stripe.secret_key', ''));
        $this->apiVersion = trim((string) app_config('stripe.api_version', '2026-02-25.clover'));

        if ($this->secretKey === '') {
            throw new RuntimeException('Stripe is not configured.');
        }
    }

    public function createCheckoutSession(array $plan, array $options = []): array
    {
        $metadata = [
            'plan_id' => (string) ($plan['id'] ?? ''),
            'plan_name' => (string) ($plan['name'] ?? ''),
            'user_id' => (string) ($options['user_id'] ?? ''),
        ];

        foreach ((array) ($options['metadata'] ?? []) as $key => $value) {
            $metadata[(string) $key] = (string) $value;
        }

        $payload = [
            'mode' => 'payment',
            'success_url' => (string) ($options['success_url'] ?? ''),
            'cancel_url' => (string) ($options['cancel_url'] ?? ''),
            'billing_address_collection' => 'auto',
            'allow_promotion_codes' => 'true',
            'client_reference_id' => (string) ($options['client_reference_id'] ?? $plan['id']),
            'metadata' => $metadata,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower((string) app_config('stripe.currency', 'rwf')),
                    'unit_amount' => (int) ($plan['amount'] ?? 0),
                    'product_data' => [
                        'name' => (string) ($plan['name'] ?? 'Kazilink plan'),
                        'description' => (string) ($plan['description'] ?? 'Professional service coordination'),
                    ],
                ],
            ]],
        ];

        $customerEmail = trim((string) ($options['customer_email'] ?? ''));

        if ($customerEmail !== '' && filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $payload['customer_email'] = $customerEmail;
        }

        return $this->request('POST', '/v1/checkout/sessions', $payload);
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', '/v1/checkout/sessions/' . rawurlencode($sessionId));
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if (function_exists('curl_init')) {
            return $this->curlRequest($method, $path, $payload);
        }

        return $this->streamRequest($method, $path, $payload);
    }

    private function curlRequest(string $method, string $path, array $payload): array
    {
        $url = 'https://api.stripe.com' . $path;
        $method = strtoupper($method);

        if ($method === 'GET' && $payload !== []) {
            $url .= '?' . http_build_query($payload);
        }

        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Unable to initialize Stripe request.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->secretKey,
                'Stripe-Version: ' . $this->apiVersion,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false || $curlError !== '') {
            throw new RuntimeException('Unable to reach Stripe right now.');
        }

        return $this->decodeResponse($responseBody, $httpCode);
    }

    private function streamRequest(string $method, string $path, array $payload): array
    {
        $method = strtoupper($method);
        $url = 'https://api.stripe.com' . $path;
        $content = http_build_query($payload);

        if ($method === 'GET' && $payload !== []) {
            $url .= '?' . $content;
            $content = '';
        }

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Stripe-Version: ' . $this->apiVersion,
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $method === 'GET' ? '' : $content,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            throw new RuntimeException('Unable to reach Stripe right now.');
        }

        $statusLine = is_array($http_response_header ?? null) ? (string) ($http_response_header[0] ?? '') : '';
        preg_match('/\s(\d{3})\s/', $statusLine, $matches);
        $httpCode = isset($matches[1]) ? (int) $matches[1] : 500;

        return $this->decodeResponse($responseBody, $httpCode);
    }

    private function decodeResponse(string $responseBody, int $httpCode): array
    {
        $data = json_decode($responseBody, true);

        if (!is_array($data)) {
            throw new RuntimeException('Stripe returned an unexpected response.');
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return $data;
        }

        $message = (string) ($data['error']['message'] ?? 'Stripe request failed.');
        throw new RuntimeException($message);
    }
}
