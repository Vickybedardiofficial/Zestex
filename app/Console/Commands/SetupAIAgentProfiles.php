<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Services\AI\ProfileGenerator\NameGenerator;
use App\Services\AI\ProfileGenerator\BioGenerator;
use App\Services\AI\ProfileGenerator\ProfilePictureGenerator;
use App\Services\AI\ProfileGenerator\AutoFollowService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SetupAIAgentProfiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-agents:setup-profiles {--agent-id= : Specific agent ID} {--force : Force regenerate profile}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-setup realistic profiles for AI agents (name, bio, picture, follows)';

    protected NameGenerator $nameGenerator;
    protected BioGenerator $bioGenerator;
    protected ProfilePictureGenerator $pictureGenerator;
    protected AutoFollowService $followService;

    public function __construct()
    {
        parent::__construct();
        $this->nameGenerator = new NameGenerator();
        $this->bioGenerator = new BioGenerator();
        $this->pictureGenerator = new ProfilePictureGenerator();
        $this->followService = new AutoFollowService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $agentId = $this->option('agent-id');
        $force = $this->option('force');

        if ($agentId) {
            $agents = AiAgent::where('id', $agentId)->with('user')->get();
        } else {
            $agents = AiAgent::with('user')->get();
        }

        if ($agents->isEmpty()) {
            $this->error('No AI agents found.');
            return 1;
        }

        $this->info("Found {$agents->count()} agent(s). Setting up profiles...");

        $successCount = 0;
        $failCount = 0;

        foreach ($agents as $agent) {
            try {
                // Skip if profile already complete and not forcing
                if (!$force && $this->isProfileComplete($agent)) {
                    $this->line("⏭️  Skipping {$agent->user->name} (profile complete)");
                    continue;
                }

                $this->line("🤖 Setting up profile for Agent ID: {$agent->id}...");

                // Step 1: Generate realistic name
                $this->info("  📝 Generating name...");
                $nameData = $this->nameGenerator->generateName(
                    $agent->country,
                    $agent->personality_type
                );

                $username = $this->nameGenerator->generateUsername(
                    $nameData['first_name'],
                    $nameData['last_name'],
                    $agent->personality_type
                );

                // Update user name and username
                $agent->user->update([
                    'first_name' => $nameData['first_name'],
                    'last_name' => $nameData['last_name'],
                    'username' => $this->ensureUniqueUsername($username)
                ]);

                $this->info("  ✅ Name: {$nameData['full_name']} (@{$username})");

                // Step 2: Generate bio
                $this->info("  📝 Generating bio...");
                $bio = $this->bioGenerator->generateBio(
                    $agent->personality_type,
                    $agent->country,
                    $agent->language,
                    $nameData['full_name']
                );

                $agent->user->update(['bio' => $bio]);
                $this->info("  ✅ Bio: " . substr($bio, 0, 50) . "...");

                // Step 3: Generate profile picture
                $this->info("  🖼️  Generating profile picture...");
                $avatarUrl = $this->pictureGenerator->generateProfilePicture(
                    $agent->personality_type,
                    $agent->country,
                    $nameData['gender'],
                    $agent->image_provider
                );

                $agent->user->update(['avatar' => $avatarUrl]);
                if (Schema::hasColumn('ai_agents', 'avatar_source')) {
                    $agent->update(['avatar_source' => $avatarUrl]);
                }
                $this->info("  ✅ Profile picture generated");

                // Step 4: Auto-follow relevant accounts
                $this->info("  👥 Auto-following relevant accounts...");
                $followCount = $this->followService->autoFollow($agent);
                $this->info("  ✅ Followed {$followCount} accounts");

                // Step 5: Follow topics
                $topicCount = $this->followService->followTopics($agent);
                if ($topicCount > 0) {
                    $this->info("  ✅ Followed {$topicCount} topics");
                }

                // Log activity
                $agent->logActivity('profile_setup_complete', [
                    'name' => $nameData['full_name'],
                    'username' => $username,
                    'gender' => $nameData['gender'],
                    'follows' => $followCount,
                    'topics' => $topicCount
                ]);

                $this->info("✅ Profile setup complete for {$nameData['full_name']}");
                $this->newLine();

                $successCount++;

            } catch (\Exception $e) {
                $this->error("❌ Failed for Agent ID {$agent->id}: {$e->getMessage()}");
                Log::error("AI Agent Profile Setup Failed", [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("✨ Profile setup complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Success', $successCount],
                ['Failed', $failCount],
                ['Total', $successCount + $failCount],
            ]
        );

        return 0;
    }

    /**
     * Check if profile is complete
     */
    protected function isProfileComplete(AiAgent $agent): bool
    {
        return !empty($agent->user->first_name) &&
               !empty($agent->user->last_name) &&
               !empty($agent->user->bio) &&
               !empty($agent->user->avatar);
    }

    /**
     * Ensure username is unique
     */
    protected function ensureUniqueUsername(string $username): string
    {
        $originalUsername = $username;
        $counter = 1;

        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }
}


