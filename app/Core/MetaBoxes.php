<?php
/**
 * XooPress Meta Boxes & Custom Fields API (ACF-style)
 *
 * Register meta boxes on post edit screens with various field types.
 * Values stored in xp_post_meta table.
 *
 * @package XooPress
 * @subpackage Core
 */

namespace XooPress\Core;

class MetaBoxes
{
    /** @var array<string, array> Registered meta boxes [id => config] */
    protected array $metaBoxes = [];

    /** @var array<string, array> Registered fields [meta_box_id => [field_key => field_config]] */
    protected array $fields = [];

    // ── Meta Box Registration ─────────────────────────────

    /**
     * Register a meta box
     *
     * @param string $id Unique meta box ID
     * @param string $title Display title
     * @param array $screens Array of post types this box appears on (default: ['post'])
     * @param string $context 'normal', 'side', 'advanced' (default: 'normal')
     * @param string $priority 'default', 'high', 'low' (default: 'default')
     * @return void
     */
    public function addMetaBox(string $id, string $title, array $screens = ['post'], string $context = 'normal', string $priority = 'default'): void
    {
        $this->metaBoxes[$id] = [
            'id'       => $id,
            'title'    => $title,
            'screens'  => $screens,
            'context'  => $context,
            'priority' => $priority,
            'fields'   => [],
        ];
    }

    /**
     * Register a field within a meta box
     *
     * @param string $metaBoxId Meta box ID
     * @param string $key Field key (stored as meta_key in post_meta)
     * @param array $config {
     *     @type string $type       Field type: text, textarea, number, email, url, select, radio, checkbox, image, wysiwyg
     *     @type string $label      Field label
     *     @type string $default    Default value
     *     @type array  $options    Options for select/radio/checkbox types
     *     @type string $placeholder Placeholder text
     *     @type bool   $required   Whether field is required
     * }
     * @return void
     */
    public function addField(string $metaBoxId, string $key, array $config): void
    {
        if (!isset($this->metaBoxes[$metaBoxId])) {
            return;
        }

        $defaults = [
            'type'        => 'text',
            'label'       => ucfirst($key),
            'default'     => '',
            'options'     => [],
            'placeholder' => '',
            'required'    => false,
        ];

        $this->metaBoxes[$metaBoxId]['fields'][$key] = array_merge($defaults, $config);
    }

    /**
     * Get all meta boxes for a given screen (post type)
     *
     * @param string $screen Post type
     * @return array
     */
    public function getMetaBoxes(string $screen = 'post'): array
    {
        $result = [];
        foreach ($this->metaBoxes as $id => $box) {
            if (in_array($screen, $box['screens'], true)) {
                $result[$id] = $box;
            }
        }
        return $result;
    }

    /**
     * Get a single meta box by ID
     *
     * @param string $id
     * @return array|null
     */
    public function getMetaBox(string $id): ?array
    {
        return $this->metaBoxes[$id] ?? null;
    }

    /**
     * Render all meta boxes for a given post type and post
     *
     * @param string $screen Post type
     * @param array $post Post data (with meta values already loaded)
     * @return string HTML
     */
    public function renderMetaBoxes(string $screen = 'post', array $post = []): string
    {
        $boxes = $this->getMetaBoxes($screen);
        if (empty($boxes)) {
            return '';
        }

        $html = '';
        foreach ($boxes as $box) {
            $html .= '<div class="meta-box" style="background:#f9f9f9;border:1px solid #ddd;border-radius:4px;margin-bottom:15px;">';
            $html .= '<h3 style="margin:0;padding:12px 15px;background:#f1f1f1;border-bottom:1px solid #ddd;font-size:0.95rem;">'
                   . htmlspecialchars($box['title']) . '</h3>';
            $html .= '<div style="padding:15px;">';

            foreach ($box['fields'] as $key => $field) {
                $html .= $this->renderField($key, $field, $post);
            }

            $html .= '</div></div>';
        }

        return $html;
    }

    // ── Field Rendering ──────────────────────────────────

    /**
     * Render a single field
     *
     * @param string $key
     * @param array $field
     * @param array $post
     * @return string
     */
    protected function renderField(string $key, array $field, array $post): string
    {
        $value = $post[$key] ?? $post['meta'][$key] ?? $field['default'] ?? '';
        $name = 'meta[' . $key . ']';
        $id = 'field-' . $key;
        $required = !empty($field['required']) ? ' required' : '';
        $label = htmlspecialchars($field['label']);
        $placeholder = htmlspecialchars($field['placeholder']);

        $html = '<div class="meta-field" style="margin-bottom:12px;">';
        $html .= '<label for="' . $id . '" style="display:block;font-weight:600;margin-bottom:4px;font-size:0.9rem;">'
               . $label . ($required ? ' <span style="color:#c00;">*</span>' : '') . '</label>';

        switch ($field['type']) {
            case 'textarea':
                $html .= '<textarea id="' . $id . '" name="' . $name . '" placeholder="' . $placeholder . '"'
                       . $required . ' style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;min-height:100px;font-size:0.9rem;">'
                       . htmlspecialchars((string)$value) . '</textarea>';
                break;

            case 'number':
                $html .= '<input type="number" id="' . $id . '" name="' . $name . '" value="' . htmlspecialchars((string)$value) . '"'
                       . ' placeholder="' . $placeholder . '"' . $required
                       . ' style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.9rem;">';
                break;

            case 'email':
                $html .= '<input type="email" id="' . $id . '" name="' . $name . '" value="' . htmlspecialchars((string)$value) . '"'
                       . ' placeholder="' . $placeholder . '"' . $required
                       . ' style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.9rem;">';
                break;

            case 'url':
                $html .= '<input type="url" id="' . $id . '" name="' . $name . '" value="' . htmlspecialchars((string)$value) . '"'
                       . ' placeholder="' . $placeholder . '"' . $required
                       . ' style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.9rem;">';
                break;

            case 'select':
                $html .= '<select id="' . $id . '" name="' . $name . '"' . $required
                       . ' style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.9rem;">';
                $html .= '<option value="">— Select —</option>';
                foreach ($field['options'] as $optValue => $optLabel) {
                    $selected = ((string)$value === (string)$optValue) ? ' selected' : '';
                    $html .= '<option value="' . htmlspecialchars((string)$optValue) . '"' . $selected . '>'
                           . htmlspecialchars((string)$optLabel) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'radio':
                $html .= '<div style="display:flex;flex-wrap:wrap;gap:10px;padding-top:4px;">';
                foreach ($field['options'] as $optValue => $optLabel) {
                    $checked = ((string)$value === (string)$optValue) ? ' checked' : '';
                    $html .= '<label style="display:flex;align-items:center;gap:4px;font-size:0.9rem;cursor:pointer;">'
                           . '<input type="radio" name="' . $name . '" value="' . htmlspecialchars((string)$optValue) . '"' . $checked . '>'
                           . htmlspecialchars((string)$optLabel) . '</label>';
                }
                $html .= '</div>';
                break;

            case 'checkbox':
                $checked = !empty($value) ? ' checked' : '';
                $html .= '<label style="display:flex;align-items:center;gap:6px;font-size:0.9rem;cursor:pointer;">'
                       . '<input type="hidden" name="' . $name . '" value="0">'
                       . '<input type="checkbox" id="' . $id . '" name="' . $name . '" value="1"' . $checked . '>'
                       . htmlspecialchars($field['label']) . '</label>';
                break;

            case 'image':
                $preview = '';
                if (!empty($value)) {
                    $preview = '<div style="margin-top:6px;"><img src="' . htmlspecialchars((string)$value) . '" style="max-width:150px;max-height:150px;border:1px solid #ddd;border-radius:4px;"></div>';
                }
                $html .= '<div style="display:flex;gap:8px;align-items:center;">';
                $html .= '<input type="text" id="' . $id . '" name="' . $name . '" value="' . htmlspecialchars((string)$value) . '"'
                       . ' placeholder="' . $placeholder . '"'
                       . ' style="flex:1;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.9rem;">';
                $html .= '<button type="button" onclick="alert(\'Media picker coming in Phase 6.2\')" style="padding:8px 12px;border:1px solid #ddd;border-radius:4px;background:#f5f5f5;cursor:pointer;">Select</button>';
                $html .= '</div>' . $preview;
                break;

            case 'wysiwyg':
                $html .= '<textarea id="' . $id . '" name="' . $name . '"' . $required
                       . ' style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;min-height:150px;font-size:0.9rem;">'
                       . htmlspecialchars((string)$value) . '</textarea>';
                break;

            case 'text':
            default:
                $html .= '<input type="text" id="' . $id . '" name="' . $name . '" value="' . htmlspecialchars((string)$value) . '"'
                       . ' placeholder="' . $placeholder . '"' . $required
                       . ' style="width:100%;padding:8px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.9rem;">';
                break;
        }

        $html .= '</div>';
        return $html;
    }

    // ── Data Persistence ─────────────────────────────────

    /**
     * Save meta fields for a post
     *
     * @param int $postId
     * @param array $data Raw $_POST['meta'] data
     * @param string $screen Post type
     * @return void
     */
    public function saveFields(int $postId, array $data, string $screen = 'post'): void
    {
        $boxes = $this->getMetaBoxes($screen);
        if (empty($boxes)) {
            return;
        }

        foreach ($boxes as $box) {
            foreach ($box['fields'] as $key => $field) {
                $value = $data[$key] ?? '';
                $this->saveField($postId, $key, $value);
            }
        }
    }

    /**
     * Save a single meta field to the database
     *
     * @param int $postId
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function saveField(int $postId, string $key, mixed $value): void
    {
        try {
            $container = $GLOBALS['xoopress_container'] ?? null;
            if (!$container || !$container->has('database')) {
                return;
            }
            $db = $container->get('database');
            $prefix = $db->getPrefix();

            // Check if meta key exists
            $existing = $db->selectOne(
                "SELECT id FROM {$prefix}post_meta WHERE post_id = ? AND meta_key = ?",
                [$postId, $key]
            );

            if ($existing) {
                $db->update($prefix . 'post_meta', ['meta_value' => is_array($value) ? json_encode($value) : (string)$value], ['id' => $existing['id']]);
            } else {
                $db->insert($prefix . 'post_meta', [
                    'post_id'    => $postId,
                    'meta_key'   => $key,
                    'meta_value' => is_array($value) ? json_encode($value) : (string)$value,
                ]);
            }
        } catch (\Throwable $e) {
            error_log("MetaBoxes: failed to save field '{$key}' for post {$postId}: " . $e->getMessage());
        }
    }

    /**
     * Get a meta value for a post
     *
     * @param int $postId
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getValue(int $postId, string $key, mixed $default = null): mixed
    {
        try {
            $container = $GLOBALS['xoopress_container'] ?? null;
            if (!$container || !$container->has('database')) {
                return $default;
            }
            $db = $container->get('database');
            $prefix = $db->getPrefix();

            $row = $db->selectOne(
                "SELECT meta_value FROM {$prefix}post_meta WHERE post_id = ? AND meta_key = ? LIMIT 1",
                [$postId, $key]
            );

            if ($row && isset($row['meta_value'])) {
                $decoded = json_decode($row['meta_value'], true);
                return $decoded !== null ? $decoded : $row['meta_value'];
            }

            return $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Get all meta values for a post
     *
     * @param int $postId
     * @return array
     */
    public function getValues(int $postId): array
    {
        try {
            $container = $GLOBALS['xoopress_container'] ?? null;
            if (!$container || !$container->has('database')) {
                return [];
            }
            $db = $container->get('database');
            $prefix = $db->getPrefix();

            $rows = $db->select(
                "SELECT meta_key, meta_value FROM {$prefix}post_meta WHERE post_id = ?",
                [$postId]
            );

            $values = [];
            foreach ($rows as $row) {
                $decoded = json_decode($row['meta_value'], true);
                $values[$row['meta_key']] = $decoded !== null ? $decoded : $row['meta_value'];
            }

            return $values;
        } catch (\Throwable $e) {
            return [];
        }
    }
}