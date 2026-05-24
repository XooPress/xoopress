<?php
/**
 * Capabilities System Tests
 *
 * @package XooPress\Tests
 */

use PHPUnit\Framework\TestCase;
use XooPress\Core\Capabilities;

class CapabilitiesTest extends TestCase
{
    public function testInitReturnsFalseWithoutDb(): void
    {
        $result = Capabilities::init(null);
        $this->assertFalse($result);
    }

    public function testGetRoleReturnsDefaultRoles(): void
    {
        $roles = Capabilities::getRoles();
        $this->assertIsArray($roles);
        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey('editor', $roles);
        $this->assertArrayHasKey('author', $roles);
        $this->assertArrayHasKey('subscriber', $roles);
    }

    public function testAdminHasAllCapabilities(): void
    {
        $caps = Capabilities::getRoleCapabilities('admin');
        $this->assertIsArray($caps);
        $this->assertTrue(in_array('manage_options', $caps));
        $this->assertTrue(in_array('publish_posts', $caps));
        $this->assertTrue(in_array('manage_webhooks', $caps));
        $this->assertTrue(in_array('approve_posts', $caps));
    }

    public function testSubscriberHasNoAdminCapabilities(): void
    {
        $caps = Capabilities::getRoleCapabilities('subscriber');
        $this->assertIsArray($caps);
        $this->assertFalse(in_array('manage_options', $caps));
        $this->assertFalse(in_array('publish_posts', $caps));
    }

    public function testUnknownRoleReturnsEmpty(): void
    {
        $caps = Capabilities::getRoleCapabilities('nonexistent');
        $this->assertIsArray($caps);
        $this->assertEmpty($caps);
    }
}