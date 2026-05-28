<?php

namespace App\Console\Commands;

use App\Models\AiAgent;
use App\Services\Image\ImageProviderManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateAIAgentAvatars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-agents:generate-avatars {--agent-id= : Specific agent ID to generate avatar for} {--force : Force regenerate even if avatar exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate profile avatars for AI agents using configured image providers';

    protected ImageProviderManager $imageManager;

    public function __construct()
    {
        parent::__construct();
        $this->imageManager = new ImageProviderManager();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->isAutomationEnabled()) {
            $this->warn('AI automation is disabled from Admin settings.');
            return 0;
        }

        $agentId = $this->option('agent-id');
        $force = $this->option('force');

        if ($agentId) {
            $agents = AiAgent::where('id', $agentId)->get();
        } else {
            $agents = AiAgent::with('user')->get();
        }

        if ($agents->isEmpty()) {
            $this->error('No AI agents found.');
            return 1;
        }

        $this->info("Found {$agents->count()} agent(s). Generating avatars...");

        $successCount = 0;
        $failCount = 0;

        foreach ($agents as $agent) {
            try {
                // Skip if avatar already exists and not forcing
                if (!$force && !empty($agent->user->avatar)) {
                    $this->line("⏭️  Skipping {$agent->user->name} (avatar exists)");
                    continue;
                }

                $this->line("🖼️  Generating avatar for {$agent->user->name}...");

                // Generate search query based on personality
                $query = $this->buildImageQuery($agent);

                // Get image from provider
                $imageUrl = $this->imageManager->getRandomImage(
                    $query,
                    $agent->image_provider
                );

                // Download and store image
                $storedPath = $this->downloadAndStoreImage($imageUrl, $agent);

                // Update user avatar
                $agent->user->update([
                    'avatar' => $storedPath,
                ]);

                // Update agent avatar source
                $agent->update([
                    'avatar_source' => $imageUrl,
                ]);

                // Log activity
                $agent->logActivity('avatar_generated', [
                    'provider' => $agent->image_provider ?? config('image-providers.default'),
                    'source_url' => $imageUrl,
                    'stored_path' => $storedPath,
                ]);

                $this->info("✅ Avatar generated for {$agent->user->name}");
                $successCount++;

            } catch (\Exception $e) {
                $this->error("❌ Failed for {$agent->user->name}: {$e->getMessage()}");
                Log::error("AI Agent Avatar Generation Failed", [
                    'agent_id' => $agent->id,
                    'error' => $e->getMessage(),
                ]);
                $failCount++;
            }
        }

        $this->newLine();
        $this->info("✨ Avatar generation complete!");
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

    protected function isAutomationEnabled(): bool
    {
        $value = DB::table('admin_settings')->where('key', 'auto_agent_creation_enabled')->value('value');
        if ($value === null) {
            return true;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Build image search query based on agent personality
     */
    protected function buildImageQuery(AiAgent $agent): string
    {
        $queries = [
            'political' => 'professional politician portrait',
            'sports' => 'athlete sports person',
            'entertainment' => 'celebrity entertainment person',
            'tech' => 'technology professional developer',
            'general' => 'professional person portrait',
        ];

        return $queries[$agent->personality_type] ?? $queries['general'];
    }

    /**
     * Download and store image locally
     */
    protected function downloadAndStoreImage(string $imageUrl, AiAgent $agent): string
    {
        try {
            // Download image
            $imageContent = file_get_contents($imageUrl);
            
            if ($imageContent === false) {
                throw new \Exception("Failed to download image from URL");
            }

            // Generate filename
            $extension = $this->getImageExtension($imageUrl);
            $filename = 'ai_agent_' . $agent->id . '_' . time() . '.' . $extension;
            $path = 'avatars/' . $filename;

            // Store in public disk
            Storage::disk('public')->put($path, $imageContent);

            // Return public URL
            return Storage::disk('public')->url($path);

        } catch (\Exception $e) {
            Log::error("Image Download Failed", [
                'agent_id' => $agent->id,
                'image_url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get image extension from URL
     */
    protected function getImageExtension(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) 
            ? $extension 
            : 'jpg';
    }
}
