<?php
/**
 * System Setting Model
 * 
 * @package XooPress
 * @subpackage Modules\System
 */

namespace XooPress\Modules\System\Models;

use XooPress\Core\Model;
use XooPress\Core\Database;

class Setting extends Model
{
    protected string $table = 'settings';
    protected string $primaryKey = 'id';
    protected array $fillable = ['key', 'value', 'autoload'];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->findBy('key', $key);
        return $setting ? $setting['value'] : $default;
    }

    public function set(string $key, mixed $value): bool
    {
        $existing = $this->findBy('key', $key);
        if ($existing) {
            $this->update($existing['id'], ['value' => (string) $value]);
        } else {
            $this->create(['key' => $key, 'value' => (string) $value]);
        }
        return true;
    }

    public function getAllAutoload(): array
    {
        return $this->where(['autoload' => 1]);
    }

    public function deleteByKey(string $key): bool
    {
        $setting = $this->findBy('key', $key);
        if ($setting) {
            $this->delete($setting['id']);
            return true;
        }
        return false;
    }
}