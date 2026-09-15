<?php

declare(strict_types=1);

namespace common\services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Yii;
use yii\base\Component;

class MpesaService extends Component
{
    private Client $http;

    public function init(): void
    {
        parent::init();
        $this->http = new Client([
            'timeout' => 30,
            'http_errors' => false,
            'verify' => $this->resolveTlsVerify(),
        ]);
    }

    /**
     * Prefer the bundled CA bundle on Windows/IIS where curl.cainfo is often unset.
     *
     * @return bool|string
     */
    private function resolveTlsVerify(): bool|string
    {
        $cfg = $this->config();
        if (array_key_exists('verify', $cfg)) {
            return $cfg['verify'];
        }

        $caBundle = dirname(__DIR__) . '/config/cacert.pem';
        if (is_file($caBundle)) {
            return $caBundle;
        }

        if (($cfg['env'] ?? 'sandbox') === 'sandbox') {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function config(): array
    {
        return Yii::$app->params['mpesa'] ?? [];
    }

    private function baseUrl(): string
    {
        return ($this->config()['env'] ?? 'sandbox') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    public function getAccessToken(): string
    {
        $env = (string) ($this->config()['env'] ?? 'sandbox');
        $cacheKey = "mpesa_access_token_{$env}";
        $cached = Yii::$app->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $consumerKey = (string) ($this->config()['consumerKey'] ?? '');
        $consumerSecret = (string) ($this->config()['consumerSecret'] ?? '');

        if ($consumerKey === '' || $consumerSecret === '') {
            throw new RuntimeException('M-Pesa consumer key/secret are not configured.');
        }

        try {
            $response = $this->http->get($this->baseUrl() . '/oauth/v1/generate', [
                'query' => ['grant_type' => 'client_credentials'],
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode("{$consumerKey}:{$consumerSecret}"),
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException(
                'Could not reach M-Pesa OAuth endpoint. ' . $e->getMessage(),
                0,
                $e,
            );
        }

        $payload = json_decode((string) $response->getBody(), true);
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid M-Pesa OAuth response.');
        }

        $token = (string) ($payload['access_token'] ?? '');
        if ($token === '') {
            $message = (string) ($payload['errorMessage'] ?? $payload['error'] ?? 'Unknown OAuth error');
            throw new RuntimeException("M-Pesa OAuth failed: {$message}");
        }

        $expiresIn = max(60, (int) ($payload['expires_in'] ?? 3500) - 60);
        Yii::$app->cache->set($cacheKey, $token, $expiresIn);

        return $token;
    }

    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '254' . substr($digits, 1);
        } elseif (str_starts_with($digits, '7') && strlen($digits) === 9) {
            $digits = '254' . $digits;
        }

        if (!preg_match('/^2547\d{8}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    /**
     * @return array{password: string, timestamp: string}
     */
    private function buildPassword(): array
    {
        $shortcode = (string) ($this->config()['shortcode'] ?? '');
        $passkey = (string) ($this->config()['passkey'] ?? '');
        $timestamp = date('YmdHis');

        return [
            'password' => base64_encode($shortcode . $passkey . $timestamp),
            'timestamp' => $timestamp,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function stkPush(string $phone, int $amount, string $accountReference, string $description): array
    {
        $cfg = $this->config();
        $shortcode = (string) ($cfg['shortcode'] ?? '');
        $callbackUrl = (string) ($cfg['callbackUrl'] ?? '');

        if ($shortcode === '' || ($cfg['passkey'] ?? '') === '') {
            throw new RuntimeException('M-Pesa shortcode/passkey are not configured.');
        }

        if ($callbackUrl === '') {
            throw new RuntimeException('M-Pesa callback URL is not configured. Set mpesa.callbackUrl in params-local.php (use ngrok for local testing).');
        }

        $normalizedPhone = self::normalizePhone($phone);
        if ($normalizedPhone === null) {
            throw new RuntimeException('Enter a valid Safaricom number (e.g. 0712345678).');
        }

        if ($amount < 1) {
            throw new RuntimeException('Payment amount must be at least KES 1.');
        }

        $auth = $this->buildPassword();
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $auth['password'],
            'Timestamp' => $auth['timestamp'],
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $normalizedPhone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $normalizedPhone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => mb_substr($accountReference, 0, 12),
            'TransactionDesc' => mb_substr($description, 0, 13),
        ];

        return $this->postJson('/mpesa/stkpush/v1/processrequest', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function stkQuery(string $checkoutRequestId): array
    {
        $shortcode = (string) ($this->config()['shortcode'] ?? '');
        if ($shortcode === '' || ($this->config()['passkey'] ?? '') === '') {
            throw new RuntimeException('M-Pesa shortcode/passkey are not configured.');
        }

        $auth = $this->buildPassword();
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $auth['password'],
            'Timestamp' => $auth['timestamp'],
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        return $this->postJson('/mpesa/stkpushquery/v1/query', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postJson(string $path, array $payload): array
    {
        try {
            $response = $this->http->post($this->baseUrl() . $path, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Could not reach M-Pesa API.', 0, $e);
        }

        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
