<?php
/**
 * XooPress Webhooks System
 *
 * Outgoing webhooks dispatch system with event-based triggers.
 * Supports events: post_published, post_updated, post_deleted,
 * user_registered, module_installed, module_uninstalled.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Webhooks
{
    /**
     * Available webhook events
     */
    const EVENTS = [
        'post_published',
        'post_updated',
        'post_deleted',
        'user_registered',
        'user_updated',
        'module_installed',
        'module_uninstalled',
        'theme_activated',
    ];

    /**
     * Create the webhooks table
     *
     * @param Database $db
     * @return void
     */
    public static function createTable(Database $db): void
    {
        $prefix = $db->getPrefix();
        $db->query("CREATE TABLE IF NOT EXISTS {$prefix}webhooks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event VARCHAR(64) NOT NULL,
            url VARCHAR(512) NOT NULL,
            secret VARCHAR(128) DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            timeout INT DEFAULT 5,
            retry_count INT DEFAULT 0,
            last_triggered_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_event (event),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /**
     * Register a new webhook
     *
     * @param Database $db
     * @param string $event Event name (must be in EVENTS array)
     * @param string $url Callback URL
     * @param string|null $secret HMAC secret for signing
     * @param string|null $description Optional description
     * @param int $timeout Timeout in seconds
     * @return array ['success' => bool, 'message' => string, 'id' => int|null]
     */
    public static function register(Database $db, string $event, string $url, ?string $secret = null, ?string $description = null, int $timeout = 5): array
    {
        if (!in_array($event, self::EVENTS)) {
            return ['success' => false, 'message' => "Invalid event: {$event}", 'id' => null];
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => "Invalid URL: {$url}", 'id' => null];
        }

        try {
            $prefix = $db->getPrefix();
            $id = $db->insert("{$prefix}webhooks", [
                'event' => $event,
                'url' => $url,
                'secret' => $secret ?? '',
                'description' => $description,
                'is_active' => 1,
                'timeout' => max(1, min(30, $timeout)),
            ]);
            return ['success' => true, 'message' => 'Webhook created.', 'id' => $id];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'id' => null];
        }
    }

    /**
     * Update an existing webhook
     *
     * @param Database $db
     * @param int $id
     * @param array $data Fields to update
     * @return array
     */
    public static function update(Database $db, int $id, array $data): array
    {
        $allowed = ['event', 'url', 'secret', 'description', 'is_active', 'timeout'];

        $updateData = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $updateData[$key] = $data[$key];
            }
        }

        if (isset($updateData['event']) && !in_array($updateData['event'], self::EVENTS)) {
            return ['success' => false, 'message' => "Invalid event: {$updateData['event']}"];
        }

        if (isset($updateData['url']) && !filter_var($updateData['url'], FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => "Invalid URL: {$updateData['url']}"];
        }

        try {
            $prefix = $db->getPrefix();
            $db->update("{$prefix}webhooks", $updateData, ['id' => $id]);
            return ['success' => true, 'message' => 'Webhook updated.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete a webhook
     *
     * @param Database $db
     * @param int $id
     * @return array
     */
    public static function delete(Database $db, int $id): array
    {
        try {
            $prefix = $db->getPrefix();
            $db->delete("{$prefix}webhooks", ['id' => $id]);
            return ['success' => true, 'message' => 'Webhook deleted.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get all webhooks, optionally filtered by event
     *
     * @param Database $db
     * @param string|null $event Optional event filter
     * @return array
     */
    public static function getAll(Database $db, ?string $event = null): array
    {
        $prefix = $db->getPrefix();
        if ($event) {
            return $db->select("SELECT * FROM {$prefix}webhooks WHERE event = ? ORDER BY created_at DESC", [$event]);
        }
        return $db->select("SELECT * FROM {$prefix}webhooks ORDER BY created_at DESC");
    }

    /**
     * Get a single webhook by ID
     *
     * @param Database $db
     * @param int $id
     * @return array|null
     */
    public static function getById(Database $db, int $id): ?array
    {
        $prefix = $db->getPrefix();
        return $db->selectOne("SELECT * FROM {$prefix}webhooks WHERE id = ?", [$id]);
    }

    /**
     * Dispatch a webhook event to all active subscribers
     *
     * Uses non-blocking cURL multi-exec for concurrent dispatch.
     *
     * @param Database $db
     * @param string $event Event name
     * @param array $payload The payload to send (will be JSON-encoded)
     * @return array Results of each dispatch attempt
     */
    public static function dispatch(Database $db, string $event, array $payload = []): array
    {
        $prefix = $db->getPrefix();
        $webhooks = $db->select(
            "SELECT * FROM {$prefix}webhooks WHERE event = ? AND is_active = 1",
            [$event]
        );

        if (empty($webhooks)) {
            return [];
        }

        $results = [];
        $payload['event'] = $event;
        $payload['timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        $payloadJson = json_encode($payload);

        $multiHandle = curl_multi_init();
        $curlHandles = [];

        foreach ($webhooks as $webhook) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $webhook['url'],
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payloadJson,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payloadJson),
                    'User-Agent: XooPress-Webhook/1.0',
                    'XooPress-Event: ' . $event,
                    !empty($webhook['secret']) ? 'XooPress-Signature: ' . self::signPayload($payloadJson, $webhook['secret']) : '',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => (int)($webhook['timeout'] ?? 5),
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
            ]);
            curl_multi_add_handle($multiHandle, $ch);
            $curlHandles[$webhook['id']] = ['handle' => $ch, 'webhook' => $webhook];
        }

        // Execute all handles concurrently
        $running = null;
        do {
            curl_multi_exec($multiHandle, $running);
            curl_multi_select($multiHandle, 1);
        } while ($running > 0);

        // Collect results
        foreach ($curlHandles as $id => $data) {
            $ch = $data['handle'];
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            $body = curl_multi_getcontent($ch);
            curl_multi_remove_handle($multiHandle, $ch);
            curl_close($ch);

            $success = $httpCode >= 200 && $httpCode < 300;
            $results[] = [
                'webhook_id' => $id,
                'url' => $data['webhook']['url'],
                'event' => $event,
                'http_code' => $httpCode,
                'success' => $success,
                'error' => $error ?: null,
                'response' => $success ? substr($body, 0, 1024) : null,
            ];

            // Update last_triggered_at and retry_count
            try {
                $updateData = ['last_triggered_at' => date('Y-m-d H:i:s')];
                if (!$success) {
                    $updateData['retry_count'] = ($data['webhook']['retry_count'] ?? 0) + 1;
                }
                $db->update("{$prefix}webhooks", $updateData, ['id' => $id]);
            } catch (\Throwable $e) {}
        }

        curl_multi_close($multiHandle);
        return $results;
    }

    /**
     * Dispatch a webhook event synchronously (single request, blocking)
     * Useful for testing/debugging
     *
     * @param string $url
     * @param string $event
     * @param array $payload
     * @param string|null $secret
     * @return array
     */
    public static function testDispatch(string $url, string $event, array $payload = [], ?string $secret = null): array
    {
        $payload['event'] = $event;
        $payload['timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
        $payloadJson = json_encode($payload);

        $headers = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payloadJson),
            'User-Agent: XooPress-Webhook/1.0',
            'XooPress-Event: ' . $event,
        ];

        if ($secret) {
            $headers[] = 'XooPress-Signature: ' . self::signPayload($payloadJson, $secret);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payloadJson,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return [
            'http_code' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300,
            'error' => $error ?: null,
            'response' => substr($body ?? '', 0, 2048),
        ];
    }

    /**
     * Verify an incoming webhook signature
     *
     * @param string $payload Raw request body
     * @param string $signature The XooPress-Signature header value
     * @param string $secret Shared secret
     * @return bool
     */
    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = self::signPayload($payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Sign a payload with HMAC-SHA256
     *
     * @param string $payload JSON-encoded payload
     * @param string $secret Shared secret
     * @return string
     */
    protected static function signPayload(string $payload, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }
}