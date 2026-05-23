<?php
/**
 * XooPress Hook/Event System
 *
 * WordPress-style actions and filters for modular extensibility.
 * Actions: execute callbacks at specific points.
 * Filters: modify values through callback chains.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Hooks
{
    /** @var array<string, array> Registered actions [hook => [priority => [callback, ...]]] */
    protected array $actions = [];

    /** @var array<string, array> Registered filters [hook => [priority => [callback, ...]]] */
    protected array $filters = [];

    // ── Actions ───────────────────────────────────────────

    /**
     * Register an action callback
     *
     * @param string $hook Hook name
     * @param callable $callback Callable to execute
     * @param int $priority Execution order (lower = earlier)
     * @return void
     */
    public function addAction(string $hook, callable $callback, int $priority = 10): void
    {
        $this->actions[$hook][$priority][] = $callback;
    }

    /**
     * Execute all callbacks registered for an action hook
     *
     * @param string $hook Hook name
     * @param mixed ...$args Arguments passed to each callback
     * @return void
     */
    public function doAction(string $hook, mixed ...$args): void
    {
        if (!isset($this->actions[$hook])) {
            return;
        }

        ksort($this->actions[$hook]);

        foreach ($this->actions[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * Remove an action callback (or all for a hook if no callback given)
     *
     * @param string $hook Hook name
     * @param callable|null $callback Specific callback to remove (null = remove all)
     * @return void
     */
    public function removeAction(string $hook, ?callable $callback = null): void
    {
        if (!isset($this->actions[$hook])) {
            return;
        }

        if ($callback === null) {
            unset($this->actions[$hook]);
            return;
        }

        foreach ($this->actions[$hook] as $priority => $callbacks) {
            $this->actions[$hook][$priority] = array_filter(
                $callbacks,
                fn($cb) => $cb !== $callback
            );
            if (empty($this->actions[$hook][$priority])) {
                unset($this->actions[$hook][$priority]);
            }
        }

        if (empty($this->actions[$hook])) {
            unset($this->actions[$hook]);
        }
    }

    /**
     * Check if an action has registered callbacks
     *
     * @param string $hook Hook name
     * @return bool
     */
    public function hasAction(string $hook): bool
    {
        return !empty($this->actions[$hook]);
    }

    // ── Filters ───────────────────────────────────────────

    /**
     * Register a filter callback
     *
     * @param string $hook Hook name
     * @param callable $callback Callable that receives and returns a value
     * @param int $priority Execution order (lower = earlier)
     * @return void
     */
    public function addFilter(string $hook, callable $callback, int $priority = 10): void
    {
        $this->filters[$hook][$priority][] = $callback;
    }

    /**
     * Apply all registered filter callbacks to a value
     *
     * @param string $hook Hook name
     * @param mixed $value The value to filter
     * @param mixed ...$args Additional arguments passed to each callback
     * @return mixed The filtered value
     */
    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->filters[$hook])) {
            return $value;
        }

        ksort($this->filters[$hook]);

        foreach ($this->filters[$hook] as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            }
        }

        return $value;
    }

    /**
     * Remove a filter callback (or all for a hook if no callback given)
     *
     * @param string $hook Hook name
     * @param callable|null $callback Specific callback to remove (null = remove all)
     * @return void
     */
    public function removeFilter(string $hook, ?callable $callback = null): void
    {
        if (!isset($this->filters[$hook])) {
            return;
        }

        if ($callback === null) {
            unset($this->filters[$hook]);
            return;
        }

        foreach ($this->filters[$hook] as $priority => $callbacks) {
            $this->filters[$hook][$priority] = array_filter(
                $callbacks,
                fn($cb) => $cb !== $callback
            );
            if (empty($this->filters[$hook][$priority])) {
                unset($this->filters[$hook][$priority]);
            }
        }

        if (empty($this->filters[$hook])) {
            unset($this->filters[$hook]);
        }
    }

    /**
     * Check if a filter has registered callbacks
     *
     * @param string $hook Hook name
     * @return bool
     */
    public function hasFilter(string $hook): bool
    {
        return !empty($this->filters[$hook]);
    }

    // ── Debugging ─────────────────────────────────────────

    /**
     * Get all registered actions
     *
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get all registered filters
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}