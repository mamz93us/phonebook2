<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $fillable = ['role', 'permission'];

    /** In-request cache: role -> [permission, ...] */
    private static array $cache = [];

    /**
     * All available permissions grouped by category.
     * Keys are permission slugs, values are display labels.
     */
    public static function allPermissions(): array
    {
        return [
            'Branches' => [
                'view-branches'   => 'View Branches',
                'manage-branches' => 'Create / Edit / Delete Branches',
            ],
            'Contacts' => [
                'view-contacts'   => 'View Contacts',
                'manage-contacts' => 'Create / Edit / Delete Contacts',
                'export-contacts' => 'Export Contacts (CSV)',
            ],
            'Logs' => [
                'view-activity-logs' => 'View Activity Logs',
                'view-phone-logs'    => 'View Phone XML Logs',
                'sync-phone-logs'    => 'Sync Phone XML Logs',
            ],
            'PBX' => [
                'view-extensions'   => 'View Extensions',
                'manage-extensions' => 'Create / Edit / Delete Extensions',
                'view-trunks'       => 'View VoIP Trunks',
            ],
            'Administration' => [
                'manage-settings'    => 'Access & Edit Settings',
                'manage-users'       => 'Manage Users',
                'manage-permissions' => 'Manage Role Permissions',
            ],
        ];
    }

    /**
     * Flat list of all permission slugs.
     */
    public static function allSlugs(): array
    {
        return collect(static::allPermissions())
            ->flatMap(fn($perms) => array_keys($perms))
            ->all();
    }

    /**
     * Default permissions per role.
     */
    public static function defaultPermissions(): array
    {
        $all         = static::allSlugs();
        $adminPerms  = array_values(array_diff($all, ['manage-users', 'manage-permissions']));
        $viewerPerms = [
            'view-branches', 'view-contacts',
            'view-activity-logs', 'view-phone-logs',
            'view-extensions', 'view-trunks',
        ];

        return [
            'super_admin' => $all,
            'admin'       => $adminPerms,
            'viewer'      => $viewerPerms,
        ];
    }

    /**
     * Get all permission slugs for a role (cached per-request).
     */
    public static function forRole(string $role): array
    {
        if (!isset(static::$cache[$role])) {
            static::$cache[$role] = static::where('role', $role)->pluck('permission')->all();
        }
        return static::$cache[$role];
    }

    /**
     * Check if a role has a specific permission.
     */
    public static function roleHas(string $role, string $permission): bool
    {
        return in_array($permission, static::forRole($role));
    }

    /**
     * Clear the in-request cache (call after saving).
     */
    public static function clearCache(): void
    {
        static::$cache = [];
    }
}
