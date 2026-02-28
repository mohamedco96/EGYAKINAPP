<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

        Schema::withoutForeignKeyConstraints(function () {
            // Delete from pivot tables first (roles are referenced in these tables)
            DB::table('model_has_roles')->truncate();
            DB::table('role_has_permissions')->truncate();

            // Delete all roles
            DB::table('roles')->truncate();
        });

        $this->command->info('✓ All existing roles dropped');
    }

    /**
     * Drop all existing permissions and their relationships
     */
    private function dropExistingPermissions(): void
    {
        $this->command->info('🗑️  Dropping existing permissions...');

        Schema::withoutForeignKeyConstraints(function () {
            // Delete from pivot tables first
            // Note: role_has_permissions was already truncated in dropExistingRoles()
            DB::table('model_has_permissions')->truncate();

            // Delete all permissions
            DB::table('permissions')->truncate();
        });

        $this->command->info('✓ All existing permissions dropped');
    }

    /**
     * Create all permissions based on Flutter frontend endpoints
     *
     * NOTE: The following endpoints DO NOT need permissions (public/auth endpoints):
     * - POST /api/v2/login
     * - POST /api/v2/register
     * - POST /api/v2/logout
     * - POST /api/v2/forgotpassword
     * - POST /api/v2/resetpasswordverification
     * - POST /api/v2/resetpassword
     * - POST /api/v2/sendverificationmail
     * - POST /api/v2/emailverification
     * - POST /api/v2/auth/social/google
     * - GET /api/v2/settings (app settings - public)
     * - POST /api/v2/contact (contact us - public)
     */
    private function createPermissions(): void
    {
        $this->command->info('📝 Creating permissions based on Flutter endpoints...');

        $permissions = [
            // Home & Dashboard
            ['name' => 'access-home', 'category' => 'home', 'description' => 'Access home dashboard', 'endpoint' => 'GET /api/v2/homeNew'],

            // Patient Management
            ['name' => 'view-all-patients', 'category' => 'patients', 'description' => 'View all patients', 'endpoint' => 'GET /api/v2/allPatientsNew'],
            ['name' => 'view-current-patients', 'category' => 'patients', 'description' => 'View current/assigned patients', 'endpoint' => 'GET /api/v2/currentPatientsNew'],
            ['name' => 'search-patients', 'category' => 'patients', 'description' => 'Search patients', 'endpoint' => 'POST /api/v2/searchNew'],
            ['name' => 'view-patient-sections', 'category' => 'patients', 'description' => 'View patient sections', 'endpoint' => 'GET /api/v2/showSections/{patientId}'],
            ['name' => 'view-patient-details', 'category' => 'patients', 'description' => 'View patient section details', 'endpoint' => 'GET /api/v2/patient/{sectionId}/{patientId}'],
            ['name' => 'create-patient', 'category' => 'patients', 'description' => 'Create new patient', 'endpoint' => 'POST /api/v2/patient'],
            ['name' => 'update-patient-section', 'category' => 'patients', 'description' => 'Update patient section', 'endpoint' => 'PUT /api/v2/patientsection/{sectionId}/{patientId}'],
            ['name' => 'delete-patient', 'category' => 'patients', 'description' => 'Delete patient', 'endpoint' => 'DELETE /api/v2/patient/{patientId}'],
            ['name' => 'get-patient-questions', 'category' => 'patients', 'description' => 'Get patient questions', 'endpoint' => 'GET /api/v2/questions/{sectionId}'],
            ['name' => 'submit-patient-outcome', 'category' => 'patients', 'description' => 'Submit patient outcome', 'endpoint' => 'PUT /api/v2/patient/{sectionId}/{patientId}'],
            ['name' => 'final-submit-patient', 'category' => 'patients', 'description' => 'Final submit patient', 'endpoint' => 'PUT /api/v2/submitStatus/{patientId}'],
            ['name' => 'generate-patient-pdf', 'category' => 'patients', 'description' => 'Generate patient PDF report', 'endpoint' => 'GET /api/v2/generatePDF/{patientId}'],
            ['name' => 'mark-patient', 'category' => 'patients', 'description' => 'Mark/bookmark patient', 'endpoint' => 'POST /api/v2/markedPatients/{patientId}'],
            ['name' => 'unmark-patient', 'category' => 'patients', 'description' => 'Unmark patient', 'endpoint' => 'POST /api/v2/markedPatients/{patientId}'],
            ['name' => 'apply-patient-filters', 'category' => 'patients', 'description' => 'Apply patient filters', 'endpoint' => 'POST /api/v2/patientFilters'],
            ['name' => 'get-patient-filters', 'category' => 'patients', 'description' => 'Get patient filter options', 'endpoint' => 'GET /api/v2/patientFilters'],
            ['name' => 'export-filtered-patients', 'category' => 'patients', 'description' => 'Export filtered patients', 'endpoint' => 'POST /api/v2/exportFilteredPatients'],

            // Patient Comments
            ['name' => 'view-patient-comments', 'category' => 'patient-comments', 'description' => 'View patient comments', 'endpoint' => 'GET /api/v2/comment/{patientId}'],
            ['name' => 'create-patient-comment', 'category' => 'patient-comments', 'description' => 'Create patient comment', 'endpoint' => 'POST /api/v2/comment'],
            ['name' => 'delete-patient-comment', 'category' => 'patient-comments', 'description' => 'Delete patient comment', 'endpoint' => 'DELETE /api/v2/comment/{commentId}'],

            // Recommendations
            ['name' => 'view-recommendations', 'category' => 'recommendations', 'description' => 'View patient recommendations', 'endpoint' => 'GET /api/v2/recommendations/{patientId}'],
            ['name' => 'create-recommendation', 'category' => 'recommendations', 'description' => 'Create patient recommendation', 'endpoint' => 'POST /api/v2/recommendations/{patientId}'],
            ['name' => 'update-recommendation', 'category' => 'recommendations', 'description' => 'Update patient recommendation', 'endpoint' => 'PUT /api/v2/recommendations/{patientId}'],
            ['name' => 'delete-recommendation', 'category' => 'recommendations', 'description' => 'Delete patient recommendation', 'endpoint' => 'DELETE /api/v2/recommendations/{patientId}'],

            // Doses/Medications
            ['name' => 'search-doses', 'category' => 'doses', 'description' => 'Search for doses/medications', 'endpoint' => 'GET /api/v2/dose/search/{dose}'],
            ['name' => 'create-dose', 'category' => 'doses', 'description' => 'Create new medicine/dose', 'endpoint' => 'POST /api/v2/dose'],

            // User Profile
            ['name' => 'update-profile', 'category' => 'profile', 'description' => 'Update user profile', 'endpoint' => 'PUT /api/v2/users'],
            ['name' => 'upload-profile-image', 'category' => 'profile', 'description' => 'Upload profile image', 'endpoint' => 'POST /api/v2/upload-profile-image'],
            ['name' => 'upload-syndicate-card', 'category' => 'profile', 'description' => 'Upload syndicate card', 'endpoint' => 'POST /api/v2/uploadSyndicateCard'],
            ['name' => 'change-password', 'category' => 'profile', 'description' => 'Change password', 'endpoint' => 'POST /api/v2/changePassword'],
            ['name' => 'view-doctor-profile', 'category' => 'profile', 'description' => 'View doctor profile', 'endpoint' => 'GET /api/v2/showAnotherProfile/{doctorId}'],
            ['name' => 'view-doctor-patients', 'category' => 'profile', 'description' => 'View doctor patients', 'endpoint' => 'GET /api/v2/doctorProfileGetPatients/{doctorId}'],
            ['name' => 'view-doctor-score-history', 'category' => 'profile', 'description' => 'View doctor score history', 'endpoint' => 'GET /api/v2/doctorProfileGetScoreHistory/{doctorId}'],
            ['name' => 'view-doctor-achievements', 'category' => 'profile', 'description' => 'View doctor achievements', 'endpoint' => 'GET /api/v2/users/{doctorId}/achievements'],

            // Admin User Management (Admin only)
            ['name' => 'verify-syndicate-card', 'category' => 'admin', 'description' => 'Verify syndicate card (Admin)', 'endpoint' => 'PUT /api/v2/users/{doctorId}'],
            ['name' => 'block-user', 'category' => 'admin', 'description' => 'Block/unblock user (Admin)', 'endpoint' => 'PUT /api/v2/users/{doctorId}'],
            ['name' => 'verify-user-email', 'category' => 'admin', 'description' => 'Verify user email (Admin)', 'endpoint' => 'PUT /api/v2/users/{doctorId}'],

            // File Uploads
            ['name' => 'upload-patient-files', 'category' => 'files', 'description' => 'Upload patient files', 'endpoint' => 'POST /api/v2/uploadFileNew'],

            // Consultations
            ['name' => 'search-consultation-doctors', 'category' => 'consultations', 'description' => 'Search doctors for consultation', 'endpoint' => 'POST /api/v2/consultationDoctorSearch/{searchContent}'],
            ['name' => 'create-consultation', 'category' => 'consultations', 'description' => 'Create consultation', 'endpoint' => 'POST /api/v2/consultations'],
            ['name' => 'view-sent-consultations', 'category' => 'consultations', 'description' => 'View sent consultations', 'endpoint' => 'GET /api/v2/consultations/sent'],
            ['name' => 'view-received-consultations', 'category' => 'consultations', 'description' => 'View received consultations', 'endpoint' => 'GET /api/v2/consultations/received'],
            ['name' => 'view-consultation-details', 'category' => 'consultations', 'description' => 'View consultation details', 'endpoint' => 'GET /api/v2/consultations/{consultationId}'],
            ['name' => 'reply-consultation', 'category' => 'consultations', 'description' => 'Reply to consultation', 'endpoint' => 'PUT /api/v2/consultations/{consultationId}'],
            ['name' => 'view-consultation-members', 'category' => 'consultations', 'description' => 'View consultation members', 'endpoint' => 'GET /api/v2/consultations/{consultationId}/members'],
            ['name' => 'toggle-consultation-status', 'category' => 'consultations', 'description' => 'Lock/unlock consultation', 'endpoint' => 'PUT /api/v2/consultations/{consultationId}/toggle-status'],
            ['name' => 'remove-consultation-member', 'category' => 'consultations', 'description' => 'Remove member from consultation', 'endpoint' => 'DELETE /api/v2/consultations/{consultationId}/doctors/{doctorId}'],
            ['name' => 'add-consultation-doctors', 'category' => 'consultations', 'description' => 'Add doctors to consultation', 'endpoint' => 'POST /api/v2/consultations/{consultationId}/add-doctors'],

            // AI Consultations
            ['name' => 'view-ai-consultation-history', 'category' => 'ai', 'description' => 'View AI consultation history', 'endpoint' => 'GET /api/v2/AIconsultation-history/{patientId}'],
            ['name' => 'send-ai-consultation', 'category' => 'ai', 'description' => 'Send AI consultation request', 'endpoint' => 'POST /api/v2/AIconsultation/{patientId}'],

            // Feed Posts
            ['name' => 'view-feed-posts', 'category' => 'feed', 'description' => 'View feed posts', 'endpoint' => 'GET /api/v2/feed/posts'],
            ['name' => 'create-feed-post', 'category' => 'feed', 'description' => 'Create feed post', 'endpoint' => 'POST /api/v2/feed/posts'],
            ['name' => 'edit-feed-post', 'category' => 'feed', 'description' => 'Edit feed post', 'endpoint' => 'POST /api/v2/feed/posts/{postId}'],
            ['name' => 'delete-feed-post', 'category' => 'feed', 'description' => 'Delete feed post', 'endpoint' => 'DELETE /api/v2/feed/posts/{postId}'],
            ['name' => 'like-feed-post', 'category' => 'feed', 'description' => 'Like/unlike feed post', 'endpoint' => 'POST /api/v2/feed/posts/{postId}/likeOrUnlikePost'],
            ['name' => 'save-feed-post', 'category' => 'feed', 'description' => 'Save/unsave feed post', 'endpoint' => 'POST /api/v2/feed/posts/{postId}/saveOrUnsavePost'],
            ['name' => 'view-feed-post', 'category' => 'feed', 'description' => 'View single feed post', 'endpoint' => 'GET /api/v2/feed/posts/{postId}'],
            ['name' => 'view-trending-posts', 'category' => 'feed', 'description' => 'View trending posts', 'endpoint' => 'GET /api/v2/feed/trendingPosts'],
            ['name' => 'search-feed-posts', 'category' => 'feed', 'description' => 'Search feed posts', 'endpoint' => 'POST /api/v2/feed/searchPosts'],
            ['name' => 'view-doctor-posts', 'category' => 'feed', 'description' => 'View doctor posts', 'endpoint' => 'GET /api/v2/doctorposts/{doctorId}'],
            ['name' => 'view-saved-posts', 'category' => 'feed', 'description' => 'View saved posts', 'endpoint' => 'GET /api/v2/doctorsavedposts/{doctorId}'],

            // Feed Comments
            ['name' => 'view-feed-comments', 'category' => 'feed-comments', 'description' => 'View feed post comments', 'endpoint' => 'GET /api/v2/posts/{postId}/comments'],
            ['name' => 'create-feed-comment', 'category' => 'feed-comments', 'description' => 'Create feed comment', 'endpoint' => 'POST /api/v2/feed/posts/{postId}/comment'],
            ['name' => 'delete-feed-comment', 'category' => 'feed-comments', 'description' => 'Delete feed comment', 'endpoint' => 'DELETE /api/v2/feed/comments/{commentId}'],
            ['name' => 'like-feed-comment', 'category' => 'feed-comments', 'description' => 'Like/unlike feed comment', 'endpoint' => 'POST /api/v2/comments/{commentId}/likeOrUnlikeComment'],
            ['name' => 'reply-feed-comment', 'category' => 'feed-comments', 'description' => 'Reply to feed comment', 'endpoint' => 'POST /api/v2/feed/posts/{postId}/comment'],

            // Legacy Posts (Old system)
            ['name' => 'view-legacy-posts', 'category' => 'legacy-posts', 'description' => 'View legacy posts', 'endpoint' => 'GET /api/v2/post'],
            ['name' => 'view-legacy-post-comments', 'category' => 'legacy-posts', 'description' => 'View legacy post comments', 'endpoint' => 'GET /api/v2/Postcomments/{postId}'],
            ['name' => 'create-legacy-post-comment', 'category' => 'legacy-posts', 'description' => 'Create legacy post comment', 'endpoint' => 'POST /api/v2/Postcomments'],
            ['name' => 'delete-legacy-post-comment', 'category' => 'legacy-posts', 'description' => 'Delete legacy post comment', 'endpoint' => 'DELETE /api/v2/Postcomments/{commentId}'],

            // Groups
            ['name' => 'view-groups', 'category' => 'groups', 'description' => 'View all groups', 'endpoint' => 'GET /api/v2/groups'],
            ['name' => 'view-groups-tab', 'category' => 'groups', 'description' => 'View groups tab', 'endpoint' => 'GET /api/v2/latest-groups-with-random-posts'],
            ['name' => 'view-group-details', 'category' => 'groups', 'description' => 'View group details', 'endpoint' => 'GET /api/v2/groups/{groupId}/detailsWithPosts'],
            ['name' => 'create-group', 'category' => 'groups', 'description' => 'Create group', 'endpoint' => 'POST /api/v2/groups'],
            ['name' => 'update-group', 'category' => 'groups', 'description' => 'Update group', 'endpoint' => 'POST /api/v2/groups/{groupId}'],
            ['name' => 'delete-group', 'category' => 'groups', 'description' => 'Delete group', 'endpoint' => 'DELETE /api/v2/groups/{groupId}'],
            ['name' => 'join-group', 'category' => 'groups', 'description' => 'Join group', 'endpoint' => 'POST /api/v2/groups/{groupId}/join'],
            ['name' => 'leave-group', 'category' => 'groups', 'description' => 'Leave group', 'endpoint' => 'POST /api/v2/groups/{groupId}/leave'],
            ['name' => 'view-group-members', 'category' => 'groups', 'description' => 'View group members', 'endpoint' => 'GET /api/v2/groups/{groupId}/members'],
            ['name' => 'view-my-groups', 'category' => 'groups', 'description' => 'View my groups', 'endpoint' => 'GET /api/v2/mygroups'],
            ['name' => 'send-group-invitation', 'category' => 'groups', 'description' => 'Send group invitation', 'endpoint' => 'POST /api/v2/groups/{groupId}/invite'],
            ['name' => 'remove-group-member', 'category' => 'groups', 'description' => 'Remove group member', 'endpoint' => 'POST /api/v2/groups/{groupId}/removeMember'],
            ['name' => 'view-group-invitations', 'category' => 'groups', 'description' => 'View group invitations', 'endpoint' => 'GET /api/v2/groups/invitations/{doctorId}'],
            ['name' => 'handle-group-invitation', 'category' => 'groups', 'description' => 'Accept/decline group invitation', 'endpoint' => 'POST /api/v2/groups/{groupId}/invitation'],

            // Polls
            ['name' => 'vote-poll', 'category' => 'polls', 'description' => 'Vote on poll', 'endpoint' => 'POST /api/v2/polls/{pollId}/vote'],
            ['name' => 'add-poll-option', 'category' => 'polls', 'description' => 'Add poll option', 'endpoint' => 'POST /api/v2/polls/{pollId}/options'],
            ['name' => 'view-poll-voters', 'category' => 'polls', 'description' => 'View poll voters', 'endpoint' => 'GET /api/v2/polls/{pollId}/options/{optionId}/voters'],

            // Post Likes
            ['name' => 'view-post-likes', 'category' => 'feed', 'description' => 'View post likes', 'endpoint' => 'GET /api/v2/posts/{postId}/likes'],
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

        $this->command->info("✓ Created {$permissionCount} permissions based on Flutter endpoints");
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

        // Admin Role - Most permissions except some admin-only actions
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminPermissions = Permission::where('guard_name', 'web')
            ->whereNotIn('name', []) // Add any admin-only permissions here if needed
            ->get();
        $admin->syncPermissions($adminPermissions);
        $this->command->info('✓ Admin role created');

        // Doctor Role - Standard medical professional permissions
        $doctor = Role::firstOrCreate(['name' => 'doctor', 'guard_name' => 'web']);
        $doctorPermissions = [
            // Home
            'access-home',

            // Patients - Full access
            'view-all-patients', 'view-current-patients', 'search-patients',
            'view-patient-sections', 'view-patient-details', 'create-patient',
            'update-patient-section', 'delete-patient', 'get-patient-questions',
            'submit-patient-outcome', 'final-submit-patient', 'generate-patient-pdf',
            'mark-patient', 'unmark-patient', 'apply-patient-filters', 'get-patient-filters',

            // Patient Comments
            'view-patient-comments', 'create-patient-comment', 'delete-patient-comment',

            // Recommendations
            'view-recommendations', 'create-recommendation', 'update-recommendation', 'delete-recommendation',

            // Doses
            'search-doses', 'create-dose',

            // Profile
            'update-profile', 'upload-profile-image', 'upload-syndicate-card',
            'change-password', 'view-doctor-profile', 'view-doctor-patients',
            'view-doctor-score-history', 'view-doctor-achievements',

            // Files
            'upload-patient-files',

            // Consultations
            'search-consultation-doctors', 'create-consultation', 'view-sent-consultations',
            'view-received-consultations', 'view-consultation-details', 'reply-consultation',
            'view-consultation-members', 'toggle-consultation-status', 'remove-consultation-member',
            'add-consultation-doctors',

            // AI
            'view-ai-consultation-history', 'send-ai-consultation',

            // Feed
            'view-feed-posts', 'create-feed-post', 'edit-feed-post', 'delete-feed-post',
            'like-feed-post', 'save-feed-post', 'view-feed-post', 'view-trending-posts',
            'search-feed-posts', 'view-doctor-posts', 'view-saved-posts', 'view-post-likes',

            // Feed Comments
            'view-feed-comments', 'create-feed-comment', 'delete-feed-comment',
            'like-feed-comment', 'reply-feed-comment',

            // Legacy Posts
            'view-legacy-posts', 'view-legacy-post-comments', 'create-legacy-post-comment',
            'delete-legacy-post-comment',

            // Groups
            'view-groups', 'view-groups-tab', 'view-group-details', 'create-group',
            'update-group', 'delete-group', 'join-group', 'leave-group', 'view-group-members',
            'view-my-groups', 'send-group-invitation', 'remove-group-member',
            'view-group-invitations', 'handle-group-invitation',

            // Polls
            'vote-poll', 'add-poll-option', 'view-poll-voters',
        ];
        $doctor->syncPermissions(Permission::whereIn('name', $doctorPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Doctor role created');

        // Junior Doctor Role - Limited permissions
        $juniorDoctor = Role::firstOrCreate(['name' => 'junior-doctor', 'guard_name' => 'web']);
        $juniorDoctorPermissions = [
            'access-home',
            'view-current-patients', 'search-patients', 'view-patient-sections',
            'view-patient-details', 'create-patient', 'update-patient-section',
            'get-patient-questions', 'submit-patient-outcome', 'generate-patient-pdf',
            'view-patient-comments', 'create-patient-comment',
            'view-recommendations', 'create-recommendation',
            'search-doses',
            'update-profile', 'upload-profile-image', 'change-password',
            'view-doctor-profile', 'view-doctor-patients',
            'upload-patient-files',
            'view-sent-consultations', 'view-received-consultations', 'view-consultation-details',
            'view-feed-posts', 'create-feed-post', 'edit-feed-post', 'delete-feed-post',
            'like-feed-post', 'save-feed-post', 'view-feed-comments', 'create-feed-comment',
            'view-groups', 'view-group-details', 'join-group', 'leave-group', 'view-my-groups',
            'handle-group-invitation', 'vote-poll',
        ];
        $juniorDoctor->syncPermissions(Permission::whereIn('name', $juniorDoctorPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Junior Doctor role created');

        // Viewer Role - Read-only access
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewerPermissions = [
            'access-home',
            'view-all-patients', 'view-current-patients', 'view-patient-sections',
            'view-patient-details', 'view-patient-comments', 'view-recommendations',
            'view-doctor-profile',
            'view-feed-posts', 'view-feed-post', 'view-trending-posts',
            'view-feed-comments', 'view-groups', 'view-group-details',
        ];
        $viewer->syncPermissions(Permission::whereIn('name', $viewerPermissions)->where('guard_name', 'web')->get());
        $this->command->info('✓ Viewer role created');

        $this->command->info('✓ All roles created and configured');
    }
}
