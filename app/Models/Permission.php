<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'guard_name',
        'category',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get permissions by category.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function byCategory(string $category)
    {
        return static::where('category', $category)->get();
    }

    /**
     * Get all categories.
     */
    public static function getCategories(): array
    {
        return [
            'users' => 'User Management',
            'roles' => 'Role Management',
            'posts' => 'Content Management',
            'reports' => 'Reports & Analytics',
            'settings' => 'System Settings',
            'other' => 'Other',
        ];
    }

    /**
     * Get formatted category name.
     */
    public function getCategoryLabelAttribute(): string
    {
        $categories = static::getCategories();

        return $categories[$this->category] ?? 'Uncategorized';
    }
}
