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
            'ui' => 'UI Visibility',
            'home' => 'Home & Dashboard',
            'patients' => 'Patient Management',
            'patient-comments' => 'Patient Comments',
            'recommendations' => 'Recommendations',
            'doses' => 'Doses/Medications',
            'admin' => 'Admin Management',
            'files' => 'File Uploads',
            'feed-comments' => 'Feed Comments',
            'polls' => 'Polls',
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
