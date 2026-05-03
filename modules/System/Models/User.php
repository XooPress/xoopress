<?php
/**
 * System User Model
 * 
 * @package XooPress
 * @subpackage Modules\System
 */

namespace XooPress\Modules\System\Models;

use XooPress\Core\Model;
use XooPress\Core\Database;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = [
        'username', 'email', 'password', 'display_name',
        'role', 'status', 'last_login', 'user_theme',
    ];
    protected array $hidden = ['password'];

    public function __construct(Database $db)
    {
        parent::__construct($db);
    }

    public function authenticate(string $username, string $password): ?array
    {
        $user = $this->findBy('username', $username);

        if ($user && password_verify($password, $user['password'])) {
            $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);
            return $user;
        }

        return null;
    }

    public function createUser(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        return $this->create($data);
    }

    public function updatePassword(int $id, string $password): int
    {
        return $this->update($id, [
            'password' => password_hash($password, PASSWORD_BCRYPT),
        ]);
    }

    public function getByRole(string $role): array
    {
        return $this->findAllBy('role', $role);
    }

    public function getActiveUsers(): array
    {
        return $this->where(['status' => 'active']);
    }
}