<?php

namespace App\Console\Commands;

use App\Modules\Achievements\Services\AchievementService;
use Illuminate\Console\Command;

class AssignAchievements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'achievements:assign {--all : Assign achievements to all users}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign achievements to users based on their current scores and patient counts';

    protected AchievementService $achievementService;

    /**
     * Create a new command instance.
     */
    public function __construct(AchievementService $achievementService)
    {
        parent::__construct();
        $this->achievementService = $achievementService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏆 Starting achievement assignment process...');

        if ($this->option('all')) {
            $this->info('Processing achievements for ALL users...');

            $result = $this->achievementService->checkAndAssignAchievementsForAllUsers();

            if ($result['value']) {
                $this->info('✅ '.$result['message']);
                $this->info('🎉 All users have been processed for achievements!');

                return Command::SUCCESS;
            } else {
                $this->error('❌ '.$result['message']);

                return Command::FAILURE;
            }
        }

        $this->warn('Please use the --all flag to process all users:');
        $this->line('php artisan achievements:assign --all');

        return Command::SUCCESS;
    }
}
