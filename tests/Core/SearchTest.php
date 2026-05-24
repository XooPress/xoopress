<?php
/**
 * Search Engine Tests
 *
 * @package XooPress\Tests
 */

use PHPUnit\Framework\TestCase;
use XooPress\Core\Search;

class SearchTest extends TestCase
{
    public function testCreateTableReturnsFalseWithoutDb(): void
    {
        $search = new Search(null);
        $result = $search->createTable();
        $this->assertFalse($result);
    }

    public function testRebuildIndexReturnsZeroWithoutDb(): void
    {
        $search = new Search(null);
        $count = $search->rebuildIndex();
        $this->assertEquals(0, $count);
    }

    public function testSearchReturnsEmptyWithoutDb(): void
    {
        $search = new Search(null);
        $results = $search->search('test');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testSearchWithEmptyQueryReturnsEmpty(): void
    {
        $search = new Search(null);
        $results = $search->search('');
        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testIndexPostReturnsFalseWithoutDb(): void
    {
        $search = new Search(null);
        $result = $search->indexPost(['id' => 1, 'title' => 'Test', 'content' => 'Test content']);
        $this->assertFalse($result);
    }

    public function testRemovePostReturnsFalseWithoutDb(): void
    {
        $search = new Search(null);
        $result = $search->removePost(1);
        $this->assertFalse($result);
    }
}