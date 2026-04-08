<?php

declare(strict_types=1);

final class SubscriptionPaymentProcessor
{
    private Subscription $subscriptions;
    private PromoCode $promoCodes;
    private MomoTransaction $transactions;

    public function __construct()
    {
        $this->subscriptions = new Subscription();
        $this->promoCodes = new PromoCode();
        $this->transactions = new MomoTransaction();
    }

    public function processSuccessfulTransaction(string $externalRef, array $payload = []): void
    {
        $transaction = $this->transactions->findByExternalRef($externalRef);
        if ($transaction === null) {
            throw new RuntimeException('Transaction not found.');
        }

        if ((string) $transaction['status'] === 'successful') {
            return;
        }

        if ((string) $transaction['status'] !== 'pending') {
            throw new RuntimeException('Only pending transactions can be activated.');
        }

        $metadata = json_decode((string) ($transaction['raw_payload_json'] ?? '{}'), true);
        $metadata = is_array($metadata) ? $metadata : [];
        $planId = (int) ($metadata['plan_id'] ?? 0);
        $promoCodeId = (int) ($metadata['promo_code_id'] ?? 0);
        $expectedAmount = (int) ($transaction['amount_rwf'] ?? 0);
        $expectedCurrency = strtoupper(trim((string) ($metadata['currency'] ?? app_config('momo.currency', 'RWF'))));
        $isInternalZeroCharge = (string) ($payload['financialTransactionId'] ?? '') === 'promo-zero-charge';

        if ($planId <= 0) {
            throw new RuntimeException('Transaction metadata is missing the target plan.');
        }

        $actualAmount = $this->extractAmount($payload);
        $actualCurrency = $this->extractCurrency($payload);

        if (!$isInternalZeroCharge && ($actualAmount === null || $actualCurrency === null)) {
            $this->transactions->updateGatewayPayload($externalRef, [
                'validation_error' => 'missing_payment_fields',
            ], 'failed');
            throw new RuntimeException('Payment verification payload is incomplete.');
        }

        if ($actualAmount !== null && $actualAmount !== $expectedAmount) {
            $this->transactions->updateGatewayPayload($externalRef, [
                'validation_error' => 'amount_mismatch',
                'expected_amount' => $expectedAmount,
                'actual_amount' => $actualAmount,
            ], 'failed');
            throw new RuntimeException('Payment amount mismatch.');
        }

        if ($actualCurrency !== null && strtoupper($actualCurrency) !== $expectedCurrency) {
            $this->transactions->updateGatewayPayload($externalRef, [
                'validation_error' => 'currency_mismatch',
                'expected_currency' => $expectedCurrency,
                'actual_currency' => $actualCurrency,
            ], 'failed');
            throw new RuntimeException('Payment currency mismatch.');
        }

        $this->transactions->updateGatewayPayload($externalRef, ['activation' => 'success-ack'], 'successful');

        $this->subscriptions->activateFromSuccessfulPayment((int) $transaction['user_id'], $planId, $externalRef);

        if ($promoCodeId > 0) {
            try {
                $this->promoCodes->redeem($promoCodeId, (int) $transaction['user_id']);
            } catch (Throwable) {
                // Unique constraint makes this idempotent.
            }
        }

        $this->transactions->updateGatewayPayload($externalRef, [
            'activation_payload' => $payload,
        ], 'successful');
    }

    public function mapGatewayStatus(string $status): string
    {
        $normalized = strtoupper(trim($status));

        return match ($normalized) {
            'SUCCESSFUL', 'SUCCESS', 'COMPLETED' => 'successful',
            'FAILED', 'FAILURE', 'REJECTED', 'TIMEOUT', 'EXPIRED' => 'failed',
            default => 'pending',
        };
    }

    private function extractAmount(array $payload): ?int
    {
        $candidates = [
            $payload['amount'] ?? null,
            $payload['amount_rwf'] ?? null,
            $payload['paidAmount'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            if (!is_numeric($candidate)) {
                continue;
            }

            return (int) round((float) $candidate);
        }

        return null;
    }

    private function extractCurrency(array $payload): ?string
    {
        $candidates = [
            $payload['currency'] ?? null,
            $payload['currencyCode'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $value = strtoupper(trim((string) $candidate));
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
