<?php

namespace pierresilva\AccessControl\Models;

use Config;
use Illuminate\Database\Eloquent\Model;
use pierresilva\AccessControl\Traits\PermissionTrait;

class Role extends Model
{
    use PermissionTrait {
        flushPermissionCache as parentFlushPermissionCache;
    }

    /**
     * The attributes that are fillable via mass assignment.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'description', 'special'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'roles';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setConnection(env('DB_DYNAMIC') ? 'dynamic' : env('DB_CONNECTION'));
    }

    /**
     * The sentinel cache tag used by the model.
     *
     * @return string
     */
    public static function getAccessControlTag()
    {
        return 'access-control.roles';
    }

    /**
     * Roles can belong to many users.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(config('auth.model') ?: config('auth.providers.users.model'))->withTimestamps();
    }

    /**
     * Get fresh permission slugs assigned to role from database.
     *
     * @return array
     */
    protected function getFreshPermissions()
    {
        return $this->permissions->pluck('slug')->all();
    }

    /**
     * Flush the permission cache repository.
     *
     * @return void
     */
    public function flushPermissionCache()
    {
        $userClass = config('auth.model') ?: config('auth.providers.users.model');
        $usersTag = call_user_func([$userClass, 'getAccessControlTag']);
        static::parentFlushPermissionCache([
          static::getAccessControlTag(),
          $usersTag,
        ]);
    }

    /**
     * Checks if the role has the given permission.
     *
     * @param string $permission
     *
     * @return bool
     */
    public function can($permission)
    {
        if ($this->special === 'no-access') {
            return false;
        }

        if ($this->special === 'all-access') {
            return true;
        }

        return $this->hasAllPermissions($permission, $this->getPermissions());
    }

    /**
     * Check if the role has at least one of the given permissions.
     *
     * @param array $permission
     *
     * @return bool
     */
    public function canAtLeast(array $permission = [])
    {
        if ($this->special === 'no-access') {
            return false;
        }

        if ($this->special === 'all-access') {
            return true;
        }

        return $this->hasAnyPermission($permission, $this->getPermissions());
    }
}
