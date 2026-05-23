<?php
/**
 * XooPress Shortcode System
 *
 * WordPress-style shortcodes: [tag], [tag attr="value"], [tag]content[/tag]
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class Shortcodes
{
    /** @var array<string, callable> Registered shortcodes [tag => handler] */
    protected array $shortcodes = [];

    /** @var array<string, int> Track nested shortcode depth to prevent infinite recursion */
    protected array $depth = [];

    /** @var int Maximum nesting depth for shortcodes */
    protected int $maxDepth = 10;

    // ── Registration ──────────────────────────────────────

    /**
     * Register a shortcode handler
     *
     * @param string $tag Shortcode tag (e.g. 'gallery' for [gallery])
     * @param callable $handler Function(string $atts, ?string $content, string $tag): string
     * @return void
     */
    public function add(string $tag, callable $handler): void
    {
        $this->shortcodes[$tag] = $handler;
    }

    /**
     * Remove a shortcode
     *
     * @param string $tag
     * @return void
     */
    public function remove(string $tag): void
    {
        unset($this->shortcodes[$tag]);
    }

    /**
     * Check if a shortcode is registered
     *
     * @param string $tag
     * @return bool
     */
    public function has(string $tag): bool
    {
        return isset($this->shortcodes[$tag]);
    }

    /**
     * Get all registered shortcode tags
     *
     * @return array<string>
     */
    public function getTags(): array
    {
        return array_keys($this->shortcodes);
    }

    // ── Parsing ───────────────────────────────────────────

    /**
     * Parse shortcodes in a string and replace them with rendered output
     *
     * @param string $content Content containing shortcodes
     * @return string Content with shortcodes replaced
     */
    public function parse(string $content): string
    {
        if (empty($this->shortcodes) || empty($content)) {
            return $content;
        }

        $this->depth = [];
        $pattern = $this->buildPattern();
        return $this->doParse($content, $pattern);
    }

    /**
     * Build the regex pattern to match all registered shortcodes
     *
     * @return string
     */
    protected function buildPattern(): string
    {
        $tags = array_map('preg_quote', array_keys($this->shortcodes), array_fill(0, count($this->shortcodes), '/'));
        return '/\[(\/?)(' . implode('|', $tags) . ')((?:\s+[a-zA-Z_][a-zA-Z0-9_-]*(?:\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s"\']+))?)*)\s*(\/)?\](?:([^[]*?(?:\[(?!\/?\2(?:\s|\]),?)[^[]*)*)\[\/\2\])?/s';
    }

    /**
     * Recursively parse and replace shortcodes
     *
     * @param string $content
     * @param string $pattern
     * @return string
     */
    protected function doParse(string $content, string $pattern): string
    {
        return preg_replace_callback($pattern, function ($matches) {
            $isClosing = $matches[1] === '/';
            $tag = $matches[2];
            $attsString = trim($matches[3] ?? '');
            $selfClosing = $matches[4] === '/';
            $innerContent = $matches[5] ?? '';

            // Skip closing tags
            if ($isClosing) {
                return $matches[0];
            }

            // Check nesting depth
            $currentDepth = ($this->depth[$tag] ?? 0);
            if ($currentDepth >= $this->maxDepth) {
                return $matches[0];
            }

            // Parse attributes
            $atts = $this->parseAtts($attsString);

            // Parse nested shortcodes in inner content
            $this->depth[$tag] = $currentDepth + 1;
            if (!empty($innerContent) && !$selfClosing) {
                $innerContent = $this->doParse($innerContent, $this->buildPattern());
            }
            $this->depth[$tag] = $currentDepth;

            // Render
            $handler = $this->shortcodes[$tag] ?? null;
            if ($handler) {
                return call_user_func($handler, $atts, $selfClosing ? null : $innerContent, $tag);
            }

            return $matches[0];
        }, $content);
    }

    /**
     * Parse shortcode attribute string into an associative array
     *
     * @param string $string Attribute string
     * @return array
     */
    protected function parseAtts(string $string): array
    {
        $atts = [];

        if (empty($string)) {
            return $atts;
        }

        // Match key="value", key='value', or key=value
        preg_match_all('/([a-zA-Z_][a-zA-Z0-9_-]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+)))?/s', $string, $matches, PREG_SET_ORDER);

        foreach ($matches as $m) {
            $key = $m[1];
            $value = $m[2] ?? $m[3] ?? $m[4] ?? '';
            $atts[$key] = $value;
        }

        return $atts;
    }

    // ── Built-in shortcodes ──────────────────────────────

    /**
     * Register built-in shortcodes
     *
     * @return void
     */
    public function registerBuiltIn(): void
    {
        $this->add('button', function ($atts, $content) {
            $url = $atts['url'] ?? '#';
            $color = $atts['color'] ?? 'primary';
            $target = !empty($atts['target']) ? ' target="' . htmlspecialchars($atts['target']) . '"' : '';
            $class = $atts['class'] ?? '';
            $style = '';
            switch ($color) {
                case 'secondary': $style = 'background:#6c757d;'; break;
                case 'success': $style = 'background:#28a745;'; break;
                case 'danger': $style = 'background:#dc3545;'; break;
                case 'warning': $style = 'background:#ffc107;color:#000;'; break;
                default: $style = 'background:#007bff;'; break;
            }
            return '<a href="' . htmlspecialchars($url) . '"' . $target . ' class="xp-button ' . htmlspecialchars($class) . '" style="' . $style . 'color:#fff;padding:10px 20px;border-radius:4px;text-decoration:none;display:inline-block;">'
                . htmlspecialchars($content ?? 'Button') . '</a>';
        });

        $this->add('accordion', function ($atts, $content) {
            $id = 'accordion-' . uniqid();
            return '<div class="xp-accordion" id="' . $id . '">' . ($content ?? '') . '</div>';
        });

        $this->add('accordion-item', function ($atts, $content) {
            $title = htmlspecialchars($atts['title'] ?? 'Item');
            $active = !empty($atts['active']) ? ' show' : '';
            $parent = $atts['parent'] ?? '';
            $id = 'accordion-item-' . uniqid();
            $dataParent = $parent ? ' data-parent="#' . htmlspecialchars($parent) . '"' : '';
            return '<div class="xp-accordion-item" style="border:1px solid #ddd;margin-bottom:4px;border-radius:4px;">'
                . '<div class="xp-accordion-header" style="padding:10px 15px;background:#f8f9fa;cursor:pointer;font-weight:600;"'
                . ' onclick="var n=this.nextElementSibling;n.style.display=n.style.display===\'block\'?\'none\':\'block\';">'
                . $title . '</div>'
                . '<div class="xp-accordion-body' . $active . '" style="padding:15px;display:none;">' . ($content ?? '') . '</div>'
                . '</div>';
        });

        $this->add('tabs', function ($atts, $content) {
            $id = 'tabs-' . uniqid();
            return '<div class="xp-tabs" id="' . $id . '">' . ($content ?? '') . '</div>';
        });

        $this->add('tab', function ($atts, $content) {
            $title = htmlspecialchars($atts['title'] ?? 'Tab');
            if (!isset($GLOBALS['_xoopress_tab_count'])) {
                $GLOBALS['_xoopress_tab_count'] = 0;
            }
            $GLOBALS['_xoopress_tab_count']++;
            $idx = $GLOBALS['_xoopress_tab_count'];
            $active = !empty($atts['active']) ? ' active' : '';
            return '<div class="xp-tab' . $active . '" data-tab-index="' . $idx . '">'
                . '<h4>' . $title . '</h4>'
                . '<div>' . ($content ?? '') . '</div>'
                . '</div>';
        });
    }
}