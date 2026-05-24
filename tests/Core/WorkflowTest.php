<?php
/**
 * Workflow System Tests
 *
 * @package XooPress\Tests
 */

use PHPUnit\Framework\TestCase;
use XooPress\Core\Workflow;

class WorkflowTest extends TestCase
{
    public function testGetTransitionsReturnsDefault(): void
    {
        $transitions = Workflow::getTransitions();
        $this->assertIsArray($transitions);
        $this->assertNotEmpty($transitions);
    }

    public function testTransitionExists(): void
    {
        $transitions = Workflow::getTransitions();
        // Check for standard workflow transitions
        $hasTransition = false;
        foreach ($transitions as $t) {
            if ($t['from'] === 'draft' && $t['to'] === 'published') {
                $hasTransition = true;
                break;
            }
        }
        $this->assertTrue($hasTransition, 'Expected transition from draft to published');
    }

    public function testGetValidActionsForDraft(): void
    {
        $actions = Workflow::getValidActions('draft');
        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
        $actionLabels = array_column($actions, 'to');
        $this->assertContains('published', $actionLabels);
    }

    public function testGetValidActionsForUnknown(): void
    {
        $actions = Workflow::getValidActions('nonexistent');
        $this->assertIsArray($actions);
        $this->assertEmpty($actions);
    }

    public function testCanTransitionDraftToPublished(): void
    {
        $allowed = Workflow::canTransition('draft', 'published');
        $this->assertTrue($allowed);
    }

    public function testCannotTransitionPublishedToDraft(): void
    {
        $allowed = Workflow::canTransition('published', 'draft');
        $this->assertFalse($allowed);
    }

    public function testCreateTableReturnsFalseWithoutDb(): void
    {
        $result = Workflow::createTable(null);
        $this->assertFalse($result);
    }
}