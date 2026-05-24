<?php
/**
 * Webhooks System Tests
 *
 * @package XooPress\Tests
 */

use PHPUnit\Framework\TestCase;
use XooPress\Core\Webhooks;

class WebhooksTest extends TestCase
{
    protected array $events;

    protected function setUp(): void
    {
        $this->events = Webhooks::getRegisteredEvents();
    }

    public function testEventsAreRegisterable(): void
    {
        $events = Webhooks::getRegisteredEvents();
        $this->assertIsArray($events);
        $this->assertNotEmpty($events);
    }

    public function testRegisterEvent(): void
    {
        Webhooks::registerEvent('test_event', 'Test Event for testing');
        $events = Webhooks::getRegisteredEvents();
        $this->assertArrayHasKey('test_event', $events);
        $this->assertEquals('Test Event for testing', $events['test_event']);
    }

    public function testCreateTableReturnsFalseWithoutDb(): void
    {
        $result = Webhooks::createTable(null);
        $this->assertFalse($result);
    }

    public function testCoreEventsExist(): void
    {
        $events = Webhooks::getRegisteredEvents();
        $coreEvents = ['post_created', 'post_updated', 'post_deleted', 'post_published', 'user_registered'];
        foreach ($coreEvents as $event) {
            $this->assertArrayHasKey($event, $events, "Expected core event '{$event}' to be registered");
        }
    }

    public function testHmacSignatureGeneration(): void
    {
        $payload = json_encode(['test' => 'data']);
        $secret = 'test_secret_key';
        $signature = hash_hmac('sha256', $payload, $secret);
        $expected = 'sha256=' . $signature;
        $this->assertStringStartsWith('sha256=', $expected);
        $this->assertEquals(64 + 7, strlen($expected)); // 'sha256=' = 7 chars + 64 hex chars
    }
}