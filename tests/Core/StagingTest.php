<?php
/**
 * Content Staging Tests
 *
 * @package XooPress\Tests
 */

use PHPUnit\Framework\TestCase;
use XooPress\Core\Staging;

class StagingTest extends TestCase
{
    public function testCreateTableReturnsFalseWithoutDb(): void
    {
        $staging = new Staging(null);
        $result = $staging->createTable();
        $this->assertFalse($result);
    }

    public function testGenerateTokenReturnsString(): void
    {
        $staging = new Staging(null);
        $token = $staging->generateToken(1);
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }

    public function testStorePreviewReturnsFalseWithoutDb(): void
    {
        $staging = new Staging(null);
        $result = $staging->storePreview(1, ['title' => 'Test'], 'admin');
        $this->assertFalse($result);
    }

    public function testGetStagedChangesReturnsEmptyWithoutDb(): void
    {
        $staging = new Staging(null);
        $changes = $staging->getStagedChanges(1);
        $this->assertIsArray($changes);
        $this->assertEmpty($changes);
    }

    public function testPublishStageReturnsFalseWithoutDb(): void
    {
        $staging = new Staging(null);
        $result = $staging->publishStage(1, 'admin');
        $this->assertFalse($result);
    }

    public function testGetPreviewByTokenReturnsNullWithoutDb(): void
    {
        $staging = new Staging(null);
        $preview = $staging->getPreviewByToken('invalid_token');
        $this->assertNull($preview);
    }

    public function testDiscardStageReturnsFalseWithoutDb(): void
    {
        $staging = new Staging(null);
        $result = $staging->discardStage(1);
        $this->assertFalse($result);
    }
}