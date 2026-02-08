<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates permissions based on actual Flutter frontend endpoints.
     * All existing permissions are dropped and recreated.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Permissions and Roles Seeder...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('✓ Cleared permission cache');

        // Drop all existing roles and permissions
        $this->dropExistingRoles();
        $this->dropExistingPermissions();

        // Create all permissions based on Flutter endpoints
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();

        $this->command->info('✅ Permissions and Roles seeded successfully!');
    }

    /**
     * Drop all existing roles and their relationships
     */
    private function dropExistingRoles(): void
    {
        $this->command->info('🗑️  Dropping existing roles...');

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete from pivot tables first (roles are referenced in these tables)
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();

        // Delete all roles
        DB::table('roles')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✓ All existing roles dropped');
    }

    /**
     * Drop all existing permissions and their relationships
     */
    private function dropExistingPermissions(): void
    {
        $this->command->info('🗑️  Dropping existing permissions...');

        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Delete from pivot tables first
        // Note: role_has_permissions was already truncated in dropExistingRoles()
        DB::table('model_has_permissions')->truncate();

        // Delete all permissions
        DB::table('permissions')->truncate();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('✓ All existing permissions dropped');
    }

    /**
     * Create all permissions
     */
    private function createPermissions(): void
    {
        $this->command->info('📝 Creating permissions...');

        $permissions = [
            // UI Visibility Permissions
            ['name' => 'view-patients-name', 'category' => 'ui', 'description' => 'View patient names'],
            ['name' => 'view-explore-community-button', 'category' => 'ui', 'description' => 'View explore community button'],
            ['name' => 'view-home-slider', 'category' => 'ui', 'description' => 'View home slider'],
            ['name' => 'view-top-doctor', 'category' => 'ui', 'description' => 'View top doctor section'],
            ['name' => 'view-admin-side-in-profiles', 'category' => 'ui', 'description' => 'View admin side in profiles'],
            ['name' => 'view-trend-hashtags-in-home', 'category' => 'ui', 'description' => 'View trending hashtags in home'],
            ['name' => 'view-groups-in-home', 'category' => 'ui', 'description' => 'View groups in home'],
            ['name' => 'view-clear-update-message-button-in-more', 'category' => 'ui', 'description' => 'View clear update message button in more'],

            // Home & Dashboard
            ['name' => 'access-home', 'category' => 'home', 'description' => 'Access home dashboard'],

            // Patient Management
            ['name' => 'view-all-patients', 'category' => 'patients', 'description' => 'View all patients'],
            ['name' => 'view-current-patients', 'category' => 'patients', 'description' => 'View current/assigned patients'],
            ['name' => 'mark-patient', 'category' => 'patients', 'description' => 'Mark/bookmark patient'],
            ['name' => 'unmark-patient', 'category' => 'patients', 'description' => 'Unmark patient'],
            ['name' => 'delete-patient', 'category' => 'patients', 'description' => 'Delete patient'],
            ['name' => 'final-submit-patient', 'category' => 'patients', 'description' => 'Final submit patient'],

            // Patient Comments
            ['name' => 'view-patient-comments', 'category' => 'patient-comments', 'description' => 'View patient comments'],
            ['name' => 'create-patient-comment', 'category' => 'patient-comments', 'description' => 'Create patient comment'],
            ['name' => 'delete-patient-comment', 'category' => 'patient-comments', 'description' => 'Delete patient comment'],

            // Polls
            ['name' => 'vote-poll', 'category' => 'polls', 'description' => 'Vote on poll'],
            ['name' => 'add-poll-option', 'category' => 'polls', 'description' => 'Add poll option'],
            ['name' => 'view-poll-voters', 'category' => 'polls', 'description' => 'View poll voters'],

            // Feed Comments
            ['name' => 'view-feed-comments', 'category' => 'feed-comments', 'description' => 'View feed post comments'],
            ['name' => 'create-feed-comment', 'category' => 'feed-comments', 'description' => 'Create feed comment'],
            ['name' => 'delete-feed-comment', 'category' => 'feed-comments', 'description' => 'Delete feed comment'],
            ['name' => 'like-feed-comment', 'category' => 'feed-comments', 'description' => 'Like/unlike feed comment'],
            ['name' => 'reply-feed-comment', 'category' => 'feed-comments', 'description' => 'Reply to feed comment'],

            // Admin User Management
            ['name' => 'verify-syndicate-card', 'category' => 'admin', 'description' => 'Verify syndicate card (Admin)'],
            ['name' => 'block-user', 'category' => 'admin', 'description' => 'Block/unblock user (Admin)'],
            ['name' => 'verify-user-email', 'category' => 'admin', 'description' => 'Verify user email (Admin)'],

            // File Uploads
            ['name' => 'upload-patient-files', 'category' => 'files', 'description' => 'Upload patient files'],

            // Recommendations
            ['name' => 'create-recommendation', 'category' => 'recommendations', 'description' => 'Create patient recommendation'],

            // Doses/Medications
            ['name' => 'search-doses', 'category' => 'doses', 'description' => 'Search for doses/medications'],
            ['name' => 'create-dose', 'category' => 'doses', 'description' => 'Create new medicine/dose'],
        ];

        $permissionCount = 0;
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'guard_name' => 'web',
                'category' => $permission['category'],
                'description' => $permission['description'],
            ]);
            $permissionCount++;
        }

        $this->command->info("✓ Created {$permissionCount} permissions");
    }

    /**
     * Create roles and assign permissions
     */
    private function createRoles(): void
    {
        $this->command->info('👥 Creating roles...');

        // Admin Role - No permissions assigned (to be configured later)
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $this->command->info('✓ Admin role created');

        // User Role - Basic user permissions (to be configured later)
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
        $this->command->info('✓ User role created');

        // Doctor Role - No permissions assigned (to be configured later)
        Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $this->command->info('✓ Doctor role created');

        // Tester Role - For testing purposes
        Role::firstOrCreate(['name' => 'tester', 'guard_name' => 'web']);
        $this->command->info('✓ Tester role created');

        $this->command->info('✓ All roles created');
    }
}
