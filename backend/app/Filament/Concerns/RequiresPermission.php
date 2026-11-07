<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Gates a resource by one permission area (User::PERMISSIONS). Owners pass every check.
 * Set `protected static string $permission` on the resource.
 */
trait RequiresPermission
{
    /** Vietnamese labels keep sentence case ("Danh mục sản phẩm", not "Danh Mục Sản Phẩm"). */
    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }

    public static function allowed(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission(static::$permission);
    }

    public static function canViewAny(): bool
    {
        return static::allowed();
    }

    public static function canCreate(): bool
    {
        return static::allowed();
    }

    public static function canEdit(Model $record): bool
    {
        return static::allowed();
    }

    public static function canDelete(Model $record): bool
    {
        return static::allowed();
    }

    public static function canDeleteAny(): bool
    {
        return static::allowed();
    }
}
