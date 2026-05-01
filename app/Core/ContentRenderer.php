<?php
/**
 * XooPress Content Renderer
 * 
 * Renders post/page content based on content_type:
 * - html: Direct HTML output
 * - markdown: Markdown parsed to HTML
 * - php: PHP code evaluated and output
 * - wysiwyg: HTML from WYSIWYG editor (rendered as-is)
 * 
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class ContentRenderer
{
    /**
     * Render content based on its type
     * 
     * @param string $content Raw content
     * @param string $type Content type (html, markdown, php, wysiwyg)
     * @return string Rendered HTML
     */
    public function render(string $content, string $type = 'html'): string
    {
        return match ($type) {
            'markdown' => $this->renderMarkdown($content),
            'php'      => $this->renderPhp($content),
            'html'     => $content,
            'wysiwyg'  => $content,
            default    => $content,
        };
    }
    
    /**
     * Render Markdown content to HTML
     * Uses Parsedown if available, otherwise a simple fallback
     * 
     * @param string $content Raw Markdown
     * @return string HTML
     */
    protected function renderMarkdown(string $content): string
    {
        // Use Parsedown if available
        if (class_exists('Parsedown')) {
            $parsedown = new \Parsedown();
            return $parsedown->text($content);
        }
        
        // Fallback: basic Markdown conversion
        return $this->basicMarkdown($content);
    }
    
    /**
     * Render PHP content by evaluating it
     * 
     * @param string $content PHP code
     * @return string Evaluated output
     */
    protected function renderPhp(string $content): string
    {
        // Remove PHP open/close tags if present, so users can write either
        // a full PHP block or just raw PHP statements
        $content = preg_replace('/^<\?php\s*/i', '', $content);
        $content = preg_replace('/\s*\?>\s*$/', '', $content);
        
        ob_start();
        try {
            eval($content);
        } catch (\Throwable $e) {
            ob_end_clean();
            return '<div class="php-error" style="padding:15px;background:#fdd;border:1px solid #f99;border-radius:4px;margin:10px 0;">'
                 . '<strong>PHP Error:</strong> ' . htmlspecialchars($e->getMessage())
                 . '</div>';
        }
        return ob_get_clean();
    }
    
    /**
     * Basic Markdown fallback (when Parsedown is not installed)
     * 
     * @param string $text Raw Markdown
     * @return string HTML
     */
    protected function basicMarkdown(string $text): string
    {
        // Headers
        $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
        
        // Bold and italic
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        
        // Code blocks
        $text = preg_replace('/```(\w*)\n(.+?)```/s', '<pre><code>$2</code></pre>', $text);
        $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
        
        // Links
        $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);
        
        // Images
        $text = preg_replace('/!\[(.+?)\]\((.+?)\)/', '<img src="$2" alt="$1">', $text);
        
        // Horizontal rules
        $text = preg_replace('/^---$/m', '<hr>', $text);
        
        // Blockquotes
        $text = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $text);
        
        // Unordered lists
        $text = preg_replace('/^[-*] (.+)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $text);
        
        // Ordered lists
        $text = preg_replace('/^\d+\. (.+)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ol>$0</ol>', $text);
        
        // Paragraphs (double newlines)
        $paragraphs = explode("\n\n", $text);
        foreach ($paragraphs as &$p) {
            $p = trim($p);
            if (!empty($p) && !str_starts_with($p, '<')) {
                $p = '<p>' . $p . '</p>';
            }
        }
        $text = implode("\n", $paragraphs);
        
        // Line breaks
        $text = nl2br($text);
        
        return $text;
    }
    
    /**
     * Get available content types with labels
     * 
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            'wysiwyg'  => 'Visual Editor',
            'html'     => 'HTML',
            'markdown' => 'Markdown',
            'php'      => 'PHP',
        ];
    }
    
    /**
     * Get the icon/emoji for a content type
     * 
     * @param string $type
     * @return string
     */
    public static function getTypeIcon(string $type): string
    {
        return match ($type) {
            'wysiwyg'  => '🎨',
            'html'     => '🔤',
            'markdown' => '📝',
            'php'      => '⚡',
            default    => '📄',
        };
    }
}