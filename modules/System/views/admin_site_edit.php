<?php
/**
 * Site Edit View
 *
 * Create or edit a multisite network site.
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var bool $isNew */
/** @var array $site */
/** @var string $csrfToken */
/** @var array $adminMenu */
?>
<?php $pageTitle = ($isNew ? 'Add New Site' : 'Edit Site') . ' - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
<div class="wrap">
    <h1><?php echo $isNew ? '➕ Add New Site' : '✏️ Edit Site'; ?></h1>

    <form method="post" action="<?php echo $isNew ? '/admin/sites/create' : '/admin/sites/save'; ?>">
        <?php echo '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($csrfToken) . '">'; ?>
        <?php if (!$isNew): ?>
        <input type="hidden" name="id" value="<?php echo (int)$site['id']; ?>">
        <?php endif; ?>

        <table class="form-table">
            <tr>
                <th><label for="domain">Domain *</label></th>
                <td>
                    <input type="text" name="domain" id="domain" value="<?php echo htmlspecialchars($site['domain'] ?? ''); ?>" required class="regular-text" placeholder="e.g. site1.example.com">
                </td>
            </tr>
            <tr>
                <th><label for="name">Site Name</label></th>
                <td><input type="text" name="name" id="name" value="<?php echo htmlspecialchars($site['name'] ?? ''); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><label for="description">Description</label></th>
                <td><textarea name="description" id="description" rows="3" class="large-text"><?php echo htmlspecialchars($site['description'] ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><label for="aliases">Aliases</label></th>
                <td>
                    <textarea name="aliases" id="aliases" rows="3" class="large-text" placeholder="One domain per line"><?php
                        $aliases = !empty($site['aliases']) ? json_decode($site['aliases'], true) : [];
                        echo htmlspecialchars(is_array($aliases) ? implode("\n", $aliases) : '');
                    ?></textarea>
                    <p class="description">Additional domains that should resolve to this site (one per line).</p>
                </td>
            </tr>
            <tr>
                <th><label for="status">Status</label></th>
                <td>
                    <select name="status" id="status">
                        <option value="active" <?php echo ($site['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($site['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="maintenance" <?php echo ($site['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="theme">Theme Override</label></th>
                <td>
                    <input type="text" name="theme" id="theme" value="<?php echo htmlspecialchars($site['theme'] ?? ''); ?>" class="regular-text" placeholder="e.g. xoopress-lite">
                    <p class="description">Leave empty to use the default active theme.</p>
                </td>
            </tr>
            <tr>
                <th><label for="language">Language Override</label></th>
                <td>
                    <input type="text" name="language" id="language" value="<?php echo htmlspecialchars($site['language'] ?? ''); ?>" class="regular-text" placeholder="e.g. en_US">
                    <p class="description">Leave empty to use the default locale.</p>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php echo $isNew ? 'Create Site' : 'Update Site'; ?></button>
            <a href="/admin/sites" class="button">Cancel</a>
        </p>
    </form>
</div>
<?php include __DIR__ . '/_admin_footer.php'; ?>