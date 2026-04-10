<?php

declare(strict_types=1);

final class MomoApi
{
    public function requestToPay(array $payload): array
    {
        $referenceId = $payload['reference_id'];
        $callbackUrl = absolute_url('subscriptions/callback', [
            'ref' => $referenceId,
            'sig' => momo_callback_signature((string) $referenceId),
        ]);

        $response = $this->request(
            'POST',
            '/collection/v1_0/requesttopay',
            [
                'X-Reference-Id: ' . $referenceId,
                'X-Target-Environment: ' . app_config('momo.target_environment', 'sandbox'),
                'X-Callback-Url: ' . $callbackUrl,
            ],
            [
                'amount' => (string) $payload['amount_rwf'],
                'currency' => app_config('momo.currency', 'RWF'),
                'externalId' => $payload['external_id'],
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $payload['phone'],
                ],
                'payerMessage' => $payload['payer_message'],
                'payeeNote' => $payload['payee_note'],
            ]
        );

        return [
            'reference_id' => $referenceId,
            'status_code' => $response['status_code'],
            'body' => $response['body'],
        ];
    }

    public function getRequestToPayStatus(string $referenceId): array
    {
        $response = $this->request(
            'GET',
            '/collection/v1_0/requesttopay/' . rawurlencode($referenceId),
            [
                'X-Target-Environment: ' . app_config('momo.target_environment', 'sandbox'),
            ]
        );

        return [
            'status_code' => $response['status_code'],
            'body' => $response['body'],
        ];
    }

    private function request(string $method, string $path, array $headers = [], ?array $body = null): array
    {
        $token = $this->accessToken();
        $url = rtrim((string) app_config('momo.base_url', ''), '/') . $path;

        if ($url === $path) {
            throw new RuntimeException('MoMo base URL is missing.');
        }

        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Unable to initialize MoMo request.');
        }

        $httpHeaders = array_merge([
            'Authorization: Bearer ' . $token,
            'Ocp-Apim-Subscription-Key: ' . app_config('momo.primary_key', ''),
            'Content-Type: application/json',
        ], $headers);

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $httpHeaders,
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        }

        $rawBody = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($rawBody === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('MoMo request failed: ' . $error);
        }

        curl_close($curl);

        $decoded = json_decode($rawBody, true);

        return [
            'status_code' => $statusCode,
            'body' => is_array($decoded) ? $decoded : ['raw' => $rawBody],
        ];
    }

    private function accessToken(): string
    {
        $url = rtrim((string) app_config('momo.base_url', ''), '/') . '/collection/token/';
        if ($url === '/collection/token/') {
            throw new RuntimeException('MoMo base URL is missing.');
        }

        $credentials = base64_encode(
            trim((string) app_config('momo.api_user', '')) . ':' . trim((string) app_config('momo.api_key', ''))
        );

        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Unable to initialize MoMo token request.');
        }

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Authorization: Basic ' . $credentials,
                'Ocp-Apim-Subscription-Key: ' . app_config('momo.primary_key', ''),
            ],
        ]);

        $rawBody = curl_exec($curl);
        if ($rawBody === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException('MoMo token request failed: ' . $error);
        }

        curl_close($curl);

        $decoded = json_decode($rawBody, true);
        $token = trim((string) ($decoded['access_token'] ?? ''));

        if ($token === '') {
            throw new RuntimeException('MoMo token response did not include an access token.');
        }

        return $token;
    }
}
