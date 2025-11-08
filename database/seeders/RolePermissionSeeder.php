<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder creates all permissions and roles for the EGYAKIN application.
     * See docs/api/permissions/COMPREHENSIVE_PERMISSIONS_GUIDE.md for full documentation.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Permissions and Roles Seeder...');

        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->command->info('✓ Cleared permission cache');

        // Create all permissions
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();

        $this->command->info('✅ Permissions and Roles seeded successfully!');
    }

    /**
     * Create all permissions organized by category
     */
    private function createPermissions(): void
    {
        $this->command->info('📝 Creating permissions...');

        $permissions = [
            // User Management (14 permissions)
            ['name' => 'view-users', 'category' => 'users', 'description' => 'View list of users'],
            ['name' => 'view-user-profile', 'category' => 'users', 'description' => 'View user profiles'],
            ['name' => 'create-users', 'category' => 'users', 'description' => 'Register new users'],
            ['name' => 'edit-users', 'category' => 'users', 'description' => 'Edit user details'],
            ['name' => 'delete-users', 'category' => 'users', 'description' => 'Delete users'],
            ['name' => 'change-user-password', 'category' => 'users', 'description' => 'Change user passwords'],
            ['name' => 'upload-profile-image', 'category' => 'users', 'description' => 'Upload profile images'],
            ['name' => 'upload-syndicate-card', 'category' => 'users', 'description' => 'Upload syndicate cards'],
            ['name' => 'view-user-achievements', 'category' => 'users', 'description' => 'View user achievements'],
            ['name' => 'view-doctor-patients', 'category' => 'users', 'description' => 'View doctor patient lists'],
            ['name' => 'view-doctor-score-history', 'category' => 'users', 'description' => 'View doctor scores'],
            ['name' => 'block-users', 'category' => 'users', 'description' => 'Block/unblock users'],
            ['name' => 'limit-users', 'category' => 'users', 'description' => 'Limit user access'],
            ['name' => 'manage-user-locale', 'category' => 'users', 'description' => 'Manage user language'],

            // Patient Management (16 permissions)
            ['name' => 'view-patients', 'category' => 'patients', 'description' => 'View patient list'],
            ['name' => 'view-patient-details', 'category' => 'patients', 'description' => 'View patient details'],
            ['name' => 'create-patients', 'category' => 'patients', 'description' => 'Create patient records'],
            ['name' => 'edit-patients', 'category' => 'patients', 'description' => 'Edit patient records'],
            ['name' => 'delete-patients', 'category' => 'patients', 'description' => 'Delete patient records'],
            ['name' => 'search-patients', 'category' => 'patients', 'description' => 'Search patients'],
            ['name' => 'view-current-patients', 'category' => 'patients', 'description' => 'View assigned patients'],
            ['name' => 'view-all-patients', 'category' => 'patients', 'description' => 'View all patients'],
            ['name' => 'mark-patients', 'category' => 'patients', 'description' => 'Bookmark patients'],
            ['name' => 'view-marked-patients', 'category' => 'patients', 'description' => 'View bookmarked patients'],
            ['name' => 'upload-patient-files', 'category' => 'patients', 'description' => 'Upload patient files'],
            ['name' => 'filter-patients', 'category' => 'patients', 'description' => 'Filter patient lists'],
            ['name' => 'export-patients', 'category' => 'patients', 'description' => 'Export patient data'],
            ['name' => 'generate-patient-pdf', 'category' => 'patients', 'description' => 'Generate patient PDFs'],
            ['name' => 'submit-patient-sections', 'category' => 'patients', 'description' => 'Submit patient sections'],
            ['name' => 'view-patient-sections', 'category' => 'patients', 'description' => 'View patient sections'],

            // Medical Data (16 permissions)
            ['name' => 'view-questions', 'category' => 'medical', 'description' => 'View medical questions'],
            ['name' => 'create-questions', 'category' => 'medical', 'description' => 'Create questions'],
            ['name' => 'edit-questions', 'category' => 'medical', 'description' => 'Edit questions'],
            ['name' => 'delete-questions', 'category' => 'medical', 'description' => 'Delete questions'],
            ['name' => 'view-sections', 'category' => 'medical', 'description' => 'View medical sections'],
            ['name' => 'create-sections', 'category' => 'medical', 'description' => 'Create sections'],
            ['name' => 'edit-sections', 'category' => 'medical', 'description' => 'Edit sections'],
            ['name' => 'delete-sections', 'category' => 'medical', 'description' => 'Delete sections'],
            ['name' => 'view-scores', 'category' => 'medical', 'description' => 'View scores'],
            ['name' => 'create-scores', 'category' => 'medical', 'description' => 'Create scores'],
            ['name' => 'edit-scores', 'category' => 'medical', 'description' => 'Edit scores'],
            ['name' => 'view-score-history', 'category' => 'medical', 'description' => 'View score history'],
            ['name' => 'view-recommendations', 'category' => 'medical', 'description' => 'View recommendations'],
            ['name' => 'create-recommendations', 'category' => 'medical', 'description' => 'Create recommendations'],
            ['name' => 'edit-recommendations', 'category' => 'medical', 'description' => 'Edit recommendations'],
            ['name' => 'delete-recommendations', 'category' => 'medical', 'description' => 'Delete recommendations'],

            // Content Management - Posts (15 permissions)
            ['name' => 'view-posts', 'category' => 'posts', 'description' => 'View posts'],
            ['name' => 'create-posts', 'category' => 'posts', 'description' => 'Create posts'],
            ['name' => 'edit-posts', 'category' => 'posts', 'description' => 'Edit own posts'],
            ['name' => 'delete-posts', 'category' => 'posts', 'description' => 'Delete own posts'],
            ['name' => 'edit-any-post', 'category' => 'posts', 'description' => 'Edit any post'],
            ['name' => 'delete-any-post', 'category' => 'posts', 'description' => 'Delete any post'],
            ['name' => 'moderate-posts', 'category' => 'posts', 'description' => 'Moderate posts'],
            ['name' => 'like-posts', 'category' => 'posts', 'description' => 'Like/unlike posts'],
            ['name' => 'save-posts', 'category' => 'posts', 'description' => 'Save/bookmark posts'],
            ['name' => 'view-post-likes', 'category' => 'posts', 'description' => 'View post likes'],
            ['name' => 'view-trending-posts', 'category' => 'posts', 'description' => 'View trending posts'],
            ['name' => 'search-posts', 'category' => 'posts', 'description' => 'Search posts'],
            ['name' => 'search-hashtags', 'category' => 'posts', 'description' => 'Search hashtags'],
            ['name' => 'view-doctor-posts', 'category' => 'posts', 'description' => 'View doctor posts'],
            ['name' => 'view-saved-posts', 'category' => 'posts', 'description' => 'View saved posts'],

            // Comments (11 permissions)
            ['name' => 'view-comments', 'category' => 'comments', 'description' => 'View comments'],
            ['name' => 'create-comments', 'category' => 'comments', 'description' => 'Create comments'],
            ['name' => 'edit-comments', 'category' => 'comments', 'description' => 'Edit own comments'],
            ['name' => 'delete-comments', 'category' => 'comments', 'description' => 'Delete own comments'],
            ['name' => 'delete-any-comment', 'category' => 'comments', 'description' => 'Delete any comment'],
            ['name' => 'like-comments', 'category' => 'comments', 'description' => 'Like comments'],
            ['name' => 'moderate-comments', 'category' => 'comments', 'description' => 'Moderate comments'],
            ['name' => 'view-patient-comments', 'category' => 'comments', 'description' => 'View patient comments'],
            ['name' => 'create-patient-comments', 'category' => 'comments', 'description' => 'Create patient comments'],
            ['name' => 'edit-patient-comments', 'category' => 'comments', 'description' => 'Edit patient comments'],
            ['name' => 'delete-patient-comments', 'category' => 'comments', 'description' => 'Delete patient comments'],

            // Groups (16 permissions)
            ['name' => 'view-groups', 'category' => 'groups', 'description' => 'View groups'],
            ['name' => 'view-group-details', 'category' => 'groups', 'description' => 'View group details'],
            ['name' => 'create-groups', 'category' => 'groups', 'description' => 'Create groups'],
            ['name' => 'edit-groups', 'category' => 'groups', 'description' => 'Edit own groups'],
            ['name' => 'delete-groups', 'category' => 'groups', 'description' => 'Delete own groups'],
            ['name' => 'delete-any-group', 'category' => 'groups', 'description' => 'Delete any group'],
            ['name' => 'join-groups', 'category' => 'groups', 'description' => 'Join groups'],
            ['name' => 'leave-groups', 'category' => 'groups', 'description' => 'Leave groups'],
            ['name' => 'view-my-groups', 'category' => 'groups', 'description' => 'View my groups'],
            ['name' => 'invite-group-members', 'category' => 'groups', 'description' => 'Invite members'],
            ['name' => 'remove-group-members', 'category' => 'groups', 'description' => 'Remove members'],
            ['name' => 'handle-group-invitations', 'category' => 'groups', 'description' => 'Handle invitations'],
            ['name' => 'handle-join-requests', 'category' => 'groups', 'description' => 'Handle join requests'],
            ['name' => 'view-group-members', 'category' => 'groups', 'description' => 'View members'],
            ['name' => 'search-group-members', 'category' => 'groups', 'description' => 'Search members'],
            ['name' => 'view-group-invitations', 'category' => 'groups', 'description' => 'View invitations'],

            // Consultations (10 permissions)
            ['name' => 'view-consultations', 'category' => 'consultations', 'description' => 'View consultations'],
            ['name' => 'create-consultations', 'category' => 'consultations', 'description' => 'Create consultations'],
            ['name' => 'view-consultation-details', 'category' => 'consultations', 'description' => 'View details'],
            ['name' => 'edit-consultations', 'category' => 'consultations', 'description' => 'Edit consultations'],
            ['name' => 'add-consultation-doctors', 'category' => 'consultations', 'description' => 'Add doctors'],
            ['name' => 'remove-consultation-doctors', 'category' => 'consultations', 'description' => 'Remove doctors'],
            ['name' => 'toggle-consultation-status', 'category' => 'consultations', 'description' => 'Change status'],
            ['name' => 'view-consultation-members', 'category' => 'consultations', 'description' => 'View members'],
            ['name' => 'reply-consultations', 'category' => 'consultations', 'description' => 'Add replies'],
            ['name' => 'search-consultation-doctors', 'category' => 'consultations', 'description' => 'Search doctors'],

            // AI Chat (2 permissions)
            ['name' => 'use-ai-consultation', 'category' => 'ai', 'description' => 'Use AI consultation'],
            ['name' => 'view-ai-history', 'category' => 'ai', 'description' => 'View AI history'],

            // Communication (13 permissions)
            ['name' => 'view-notifications', 'category' => 'communication', 'description' => 'View notifications'],
            ['name' => 'view-new-notifications', 'category' => 'communication', 'description' => 'View new notifications'],
            ['name' => 'mark-notification-read', 'category' => 'communication', 'description' => 'Mark as read'],
            ['name' => 'mark-all-notifications-read', 'category' => 'communication', 'description' => 'Mark all as read'],
            ['name' => 'create-notifications', 'category' => 'communication', 'description' => 'Create notifications'],
            ['name' => 'delete-notifications', 'category' => 'communication', 'description' => 'Delete notifications'],
            ['name' => 'send-push-notifications', 'category' => 'communication', 'description' => 'Send push notifications'],
            ['name' => 'send-bulk-push-notifications', 'category' => 'communication', 'description' => 'Send bulk push'],
            ['name' => 'manage-fcm-tokens', 'category' => 'communication', 'description' => 'Manage FCM tokens'],
            ['name' => 'view-contacts', 'category' => 'communication', 'description' => 'View contacts'],
            ['name' => 'create-contacts', 'category' => 'communication', 'description' => 'Create contacts'],
            ['name' => 'edit-contacts', 'category' => 'communication', 'description' => 'Edit contacts'],
            ['name' => 'delete-contacts', 'category' => 'communication', 'description' => 'Delete contacts'],

            // Polls (5 permissions)
            ['name' => 'view-polls', 'category' => 'polls', 'description' => 'View polls'],
            ['name' => 'create-polls', 'category' => 'polls', 'description' => 'Create polls'],
            ['name' => 'vote-polls', 'category' => 'polls', 'description' => 'Vote in polls'],
            ['name' => 'view-poll-voters', 'category' => 'polls', 'description' => 'View voters'],
            ['name' => 'add-poll-options', 'category' => 'polls', 'description' => 'Add poll options'],

            // Doses (5 permissions)
            ['name' => 'view-doses', 'category' => 'doses', 'description' => 'View doses'],
            ['name' => 'create-doses', 'category' => 'doses', 'description' => 'Create doses'],
            ['name' => 'edit-doses', 'category' => 'doses', 'description' => 'Edit doses'],
            ['name' => 'delete-doses', 'category' => 'doses', 'description' => 'Delete doses'],
            ['name' => 'search-doses', 'category' => 'doses', 'description' => 'Search doses'],

            // Achievements (7 permissions)
            ['name' => 'view-achievements', 'category' => 'achievements', 'description' => 'View achievements'],
            ['name' => 'view-achievement-details', 'category' => 'achievements', 'description' => 'View details'],
            ['name' => 'create-achievements', 'category' => 'achievements', 'description' => 'Create achievements'],
            ['name' => 'edit-achievements', 'category' => 'achievements', 'description' => 'Edit achievements'],
            ['name' => 'delete-achievements', 'category' => 'achievements', 'description' => 'Delete achievements'],
            ['name' => 'view-user-achievements', 'category' => 'achievements', 'description' => 'View user achievements'],
            ['name' => 'assign-achievements', 'category' => 'achievements', 'description' => 'Assign achievements'],

            // Reports & Analytics (5 permissions)
            ['name' => 'view-reports', 'category' => 'reports', 'description' => 'View reports'],
            ['name' => 'export-patient-data', 'category' => 'reports', 'description' => 'Export patient data'],
            ['name' => 'export-filtered-patients', 'category' => 'reports', 'description' => 'Export filtered data'],
            ['name' => 'view-analytics', 'category' => 'reports', 'description' => 'View analytics'],
            ['name' => 'view-statistics', 'category' => 'reports', 'description' => 'View statistics'],

            // Settings (4 permissions)
            ['name' => 'view-settings', 'category' => 'settings', 'description' => 'View settings'],
            ['name' => 'edit-settings', 'category' => 'settings', 'description' => 'Edit settings'],
            ['name' => 'delete-settings', 'category' => 'settings', 'description' => 'Delete settings'],
            ['name' => 'manage-app-settings', 'category' => 'settings', 'description' => 'Manage app settings'],

            // Roles & Permissions (11 permissions)
            ['name' => 'view-roles', 'category' => 'roles', 'description' => 'View roles'],
            ['name' => 'create-roles', 'category' => 'roles', 'description' => 'Create roles'],
            ['name' => 'edit-roles', 'category' => 'roles', 'description' => 'Edit roles'],
            ['name' => 'delete-roles', 'category' => 'roles', 'description' => 'Delete roles'],
            ['name' => 'view-permissions', 'category' => 'roles', 'description' => 'View permissions'],
            ['name' => 'create-permissions', 'category' => 'roles', 'description' => 'Create permissions'],
            ['name' => 'edit-permissions', 'category' => 'roles', 'description' => 'Edit permissions'],
            ['name' => 'delete-permissions', 'category' => 'roles', 'description' => 'Delete permissions'],
            ['name' => 'assign-roles', 'category' => 'roles', 'description' => 'Assign roles'],
            ['name' => 'assign-permissions', 'category' => 'roles', 'description' => 'Assign permissions'],
            ['name' => 'check-permissions', 'category' => 'roles', 'description' => 'Check permissions'],

            // Media (4 permissions)
            ['name' => 'upload-images', 'category' => 'media', 'description' => 'Upload images'],
            ['name' => 'upload-videos', 'category' => 'media', 'description' => 'Upload videos'],
            ['name' => 'upload-files', 'category' => 'media', 'description' => 'Upload files'],
            ['name' => 'delete-media', 'category' => 'media', 'description' => 'Delete media'],

            // Sharing (3 permissions)
            ['name' => 'generate-share-urls', 'category' => 'sharing', 'description' => 'Generate share URLs'],
            ['name' => 'generate-bulk-share-urls', 'category' => 'sharing', 'description' => 'Generate bulk URLs'],
            ['name' => 'view-share-preview', 'category' => 'sharing', 'description' => 'View share preview'],

            // Admin Panel (5 permissions)
            ['name' => 'access-admin-panel', 'category' => 'admin', 'description' => 'Access admin panel'],
            ['name' => 'view-dashboard', 'category' => 'admin', 'description' => 'View dashboard'],
            ['name' => 'view-audit-logs', 'category' => 'admin', 'description' => 'View audit logs'],
            ['name' => 'export-audit-logs', 'category' => 'admin', 'description' => 'Export audit logs'],
            ['name' => 'manage-system-health', 'category' => 'admin', 'description' => 'Manage system health'],

            // Filament Resource Permissions
            ['name' => 'view role permissions', 'category' => 'filament', 'description' => 'View role permissions resource'],
            ['name' => 'view::role::permission', 'category' => 'filament', 'description' => 'View role permission details'],
            ['name' => 'create role permissions', 'category' => 'filament', 'description' => 'Create role permissions'],
            ['name' => 'update role permissions', 'category' => 'filament', 'description' => 'Update role permissions'],
            ['name' => 'delete role permissions', 'category' => 'filament', 'description' => 'Delete role permissions'],
            ['name' => 'view audit logs', 'category' => 'filament', 'description' => 'View audit logs resource'],
            ['name' => 'view fcm tokens', 'category' => 'filament', 'description' => 'View FCM tokens resource'],
            ['name' => 'view answers', 'category' => 'filament', 'description' => 'View answers resource'],
            ['name' => 'create answers', 'category' => 'filament', 'description' => 'Create answers'],
            ['name' => 'update answers', 'category' => 'filament', 'description' => 'Update answers'],
            ['name' => 'delete answers', 'category' => 'filament', 'description' => 'Delete answers'],
        ];

        $permissionCount = 0;
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'guard_name' => 'web',
                    'category' => $permission['category'],
                    'description' => $permission['description'],
                ]
            );
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

        // Super Admin - All permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->get());
        $this->command->info('✓ Super Admin role created with ALL permissions');

        // Admin Role
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPermissions = [
            // User management
            'view-users', 'view-user-profile', 'create-users', 'edit-users',
            'block-users', 'limit-users', 'view-user-achievements',
            'view-doctor-patients', 'view-doctor-score-history',

            // Patient management (full)
            'view-patients', 'view-patient-details', 'create-patients', 'edit-patients',
            'delete-patients', 'search-patients', 'view-current-patients', 'view-all-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files', 'filter-patients',
            'export-patients', 'generate-patient-pdf', 'submit-patient-sections', 'view-patient-sections',

            // Medical data (full)
            'view-questions', 'create-questions', 'edit-questions', 'delete-questions',
            'view-sections', 'create-sections', 'edit-sections', 'delete-sections',
            'view-scores', 'create-scores', 'edit-scores', 'view-score-history',
            'view-recommendations', 'create-recommendations', 'edit-recommendations', 'delete-recommendations',

            // Content moderation
            'view-posts', 'edit-any-post', 'delete-any-post', 'moderate-posts',
            'delete-any-comment', 'moderate-comments',
            'delete-any-group',

            // Reports
            'view-reports', 'export-patient-data', 'view-analytics', 'view-statistics',

            // Settings
            'view-settings', 'edit-settings', 'manage-app-settings',

            // Admin panel
            'access-admin-panel', 'view-dashboard', 'view-audit-logs',

            // Communication
            'send-push-notifications', 'send-bulk-push-notifications', 'create-notifications',
        ];
        $admin->syncPermissions(Permission::whereIn('name', $adminPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Admin role created');

        // Senior Doctor Role
        $seniorDoctor = Role::firstOrCreate(['name' => 'senior-doctor', 'guard_name' => 'web']);
        $seniorDoctorPermissions = [
            // Patient management (full)
            'view-patients', 'view-patient-details', 'create-patients', 'edit-patients',
            'delete-patients', 'search-patients', 'view-current-patients', 'view-all-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files', 'filter-patients',
            'export-patients', 'generate-patient-pdf', 'submit-patient-sections', 'view-patient-sections',

            // Medical data
            'view-questions', 'create-questions', 'edit-questions',
            'view-sections', 'view-scores', 'view-score-history',
            'view-recommendations', 'create-recommendations', 'edit-recommendations', 'delete-recommendations',

            // Consultations (full)
            'view-consultations', 'create-consultations', 'view-consultation-details',
            'edit-consultations', 'add-consultation-doctors', 'remove-consultation-doctors',
            'toggle-consultation-status', 'view-consultation-members', 'reply-consultations',
            'search-consultation-doctors',

            // AI
            'use-ai-consultation', 'view-ai-history',

            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'like-posts', 'save-posts',
            'view-trending-posts', 'search-posts', 'search-hashtags',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments', 'like-comments',

            // Groups
            'view-groups', 'view-group-details', 'create-groups', 'edit-groups', 'delete-groups',
            'join-groups', 'leave-groups', 'view-my-groups', 'invite-group-members',
            'remove-group-members', 'handle-group-invitations', 'view-group-members',

            // Communication
            'view-notifications', 'view-new-notifications', 'mark-notification-read',
            'mark-all-notifications-read', 'manage-fcm-tokens',

            // Profile
            'upload-profile-image', 'change-user-password', 'manage-user-locale',

            // Media
            'upload-images', 'upload-videos', 'upload-files',

            // Achievements
            'view-achievements', 'assign-achievements',

            // Doses
            'view-doses', 'create-doses', 'edit-doses', 'search-doses',
        ];
        $seniorDoctor->syncPermissions(Permission::whereIn('name', $seniorDoctorPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Senior Doctor role created');

        // Doctor (Standard) Role
        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctorPermissions = [
            // Patient management (own + view all)
            'view-patients', 'view-patient-details', 'create-patients', 'edit-patients',
            'search-patients', 'view-current-patients', 'view-all-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files', 'filter-patients',
            'generate-patient-pdf', 'submit-patient-sections', 'view-patient-sections',

            // Medical data (view + limited edit)
            'view-questions', 'view-sections', 'view-scores', 'view-score-history',
            'view-recommendations', 'create-recommendations', 'edit-recommendations',

            // Patient comments
            'view-patient-comments', 'create-patient-comments', 'edit-patient-comments', 'delete-patient-comments',

            // Consultations
            'view-consultations', 'create-consultations', 'view-consultation-details',
            'reply-consultations', 'search-consultation-doctors',

            // AI
            'use-ai-consultation', 'view-ai-history',

            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'like-posts', 'save-posts',
            'view-trending-posts', 'search-posts', 'search-hashtags',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments', 'like-comments',

            // Groups
            'view-groups', 'view-group-details', 'create-groups', 'edit-groups', 'delete-groups',
            'join-groups', 'leave-groups', 'view-my-groups', 'invite-group-members',
            'handle-group-invitations', 'view-group-members',

            // Polls
            'view-polls', 'create-polls', 'vote-polls', 'view-poll-voters',

            // Communication
            'view-notifications', 'view-new-notifications', 'mark-notification-read',
            'mark-all-notifications-read', 'manage-fcm-tokens',
            'create-contacts',

            // Profile
            'view-user-profile', 'upload-profile-image', 'upload-syndicate-card',
            'change-user-password', 'manage-user-locale',

            // Media
            'upload-images', 'upload-videos', 'upload-files',

            // Achievements
            'view-achievements', 'view-user-achievements',

            // Doses
            'view-doses', 'search-doses',

            // Sharing
            'generate-share-urls', 'view-share-preview',
        ];
        $doctor->syncPermissions(Permission::whereIn('name', $doctorPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Doctor role created');

        // Junior Doctor Role
        $juniorDoctor = Role::firstOrCreate(['name' => 'junior-doctor', 'guard_name' => 'web']);
        $juniorDoctorPermissions = [
            // Patient management (limited)
            'view-patients', 'view-patient-details', 'create-patients',
            'search-patients', 'view-current-patients',
            'mark-patients', 'view-marked-patients', 'upload-patient-files',
            'generate-patient-pdf', 'view-patient-sections',

            // Medical data (view only)
            'view-questions', 'view-sections', 'view-scores', 'view-recommendations',

            // Patient comments
            'view-patient-comments', 'create-patient-comments',

            // Consultations (view only)
            'view-consultations', 'view-consultation-details',

            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts', 'like-posts', 'save-posts',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments',

            // Groups
            'view-groups', 'view-group-details', 'join-groups', 'leave-groups',
            'view-my-groups', 'handle-group-invitations',

            // Communication
            'view-notifications', 'mark-notification-read', 'manage-fcm-tokens',

            // Profile
            'upload-profile-image', 'change-user-password', 'manage-user-locale',

            // Media
            'upload-images',

            // Doses
            'view-doses', 'search-doses',
        ];
        $juniorDoctor->syncPermissions(Permission::whereIn('name', $juniorDoctorPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Junior Doctor role created');

        // Moderator Role
        $moderator = Role::firstOrCreate(['name' => 'moderator', 'guard_name' => 'web']);
        $moderatorPermissions = [
            // Content moderation
            'view-posts', 'edit-any-post', 'delete-any-post', 'moderate-posts',
            'view-comments', 'delete-any-comment', 'moderate-comments',
            'view-groups', 'view-group-details', 'delete-any-group',

            // User management
            'view-users', 'view-user-profile', 'block-users',

            // Communication
            'send-push-notifications', 'create-notifications',
            'view-contacts', 'edit-contacts', 'delete-contacts',

            // Reports
            'view-reports',
        ];
        $moderator->syncPermissions(Permission::whereIn('name', $moderatorPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Moderator role created');

        // Content Manager Role
        $contentManager = Role::firstOrCreate(['name' => 'content-manager', 'guard_name' => 'web']);
        $contentManagerPermissions = [
            // Content
            'view-posts', 'create-posts', 'edit-posts', 'delete-posts',
            'view-comments', 'create-comments', 'edit-comments', 'delete-comments',
            'view-trending-posts', 'search-posts',

            // Groups
            'view-groups', 'create-groups', 'edit-groups', 'delete-groups',
            'invite-group-members', 'view-group-members',

            // Communication
            'send-push-notifications', 'create-notifications',

            // Media
            'upload-images', 'upload-videos',
        ];
        $contentManager->syncPermissions(Permission::whereIn('name', $contentManagerPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Content Manager role created');

        // Viewer Role
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerPermissions = [
            // View only
            'view-posts', 'view-trending-posts', 'search-posts',
            'view-comments',
            'view-groups', 'view-group-details',
            'view-notifications', 'mark-notification-read',
            'view-doses', 'search-doses',
        ];
        $viewer->syncPermissions(Permission::whereIn('name', $viewerPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Viewer role created');

        $this->command->info('✓ All 8 roles created and configured');
    }
}
