<?php

namespace App\Services\AI\ProfileGenerator;

use App\Services\AI\AIProviderManager;
use App\Services\Image\ImageProviderManager;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProfilePictureGenerator
{
    protected AIProviderManager $aiManager;
    protected ImageProviderManager $imageManager;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
        $this->imageManager = new ImageProviderManager();
    }

    /**
     * Generate profile picture based on personality and country
     */
    public function generateProfilePicture(
        string $personality,
        string $country,
        string $gender,
        ?string $preferredProvider = null
    ): string {
        try {
            // Try AI-generated realistic face first
            if ($this->shouldUseAIGeneration($preferredProvider)) {
                return $this->generateAIFace($personality, $country, $gender);
            }

            // Fallback to stock photo
            return $this->getStockPhoto($personality, $gender);

        } catch (\Exception $e) {
            Log::error('Profile Picture Generation Failed', [
                'personality' => $personality,
                'country' => $country,
                'error' => $e->getMessage()
            ]);

            // Ultimate fallback - default avatar
            return $this->getDefaultAvatar($gender);
        }
    }

    /**
     * Generate AI-powered realistic face
     */
    protected function generateAIFace(string $personality, string $country, string $gender): string
    {
        $prompt = $this->buildAIPrompt($personality, $country, $gender);

        try {
            // Generate image using AI provider
            $imageUrl = $this->aiManager->generateImage($prompt, null, [
                'size' => '512x512',
                'quality' => 'hd'
            ]);

            // Download and store
            return $this->downloadAndStore($imageUrl, 'ai_generated');

        } catch (\Exception $e) {
            throw new \Exception("AI face generation failed: " . $e->getMessage());
        }
    }

    /**
     * Build AI prompt for realistic face generation
     */
    protected function buildAIPrompt(string $personality, string $country, string $gender): string
    {
        $ethnicities = [
            'IN' => 'Indian',
            'US' => 'American',
            'PK' => 'Pakistani',
            'GB' => 'British',
            'BD' => 'Bangladeshi'
        ];

        $ethnicity = $ethnicities[$country] ?? 'Indian';
        $genderTerm = $gender === 'male' ? 'man' : 'woman';

        $ageRanges = [
            'political' => '35-50 years old',
            'sports' => '25-35 years old',
            'tech' => '25-40 years old',
            'entertainment' => '20-35 years old',
            'general' => '30-45 years old'
        ];

        $age = $ageRanges[$personality] ?? '30-40 years old';

        $styles = [
            'political' => 'professional, confident, wearing formal attire',
            'sports' => 'athletic, energetic, casual sportswear',
            'tech' => 'modern, smart casual, tech-savvy look',
            'entertainment' => 'stylish, trendy, fashionable',
            'general' => 'friendly, approachable, casual'
        ];

        $style = $styles[$personality] ?? 'friendly, approachable';

        return "A realistic professional headshot photo of a {$age} {$ethnicity} {$genderTerm}, {$style}. High quality portrait, neutral background, natural lighting, photorealistic, detailed facial features, looking at camera with a slight smile. Professional photography.";
    }

    /**
     * Get stock photo from image provider
     */
    protected function getStockPhoto(string $personality, string $gender): string
    {
        $queries = [
            'male' => [
                'political' => 'professional indian man portrait',
                'sports' => 'athletic man sports',
                'tech' => 'software developer man',
                'entertainment' => 'young man portrait',
                'general' => 'indian man portrait'
            ],
            'female' => [
                'political' => 'professional indian woman portrait',
                'sports' => 'athletic woman sports',
                'tech' => 'software developer woman',
                'entertainment' => 'young woman portrait',
                'general' => 'indian woman portrait'
            ]
        ];

        $query = $queries[$gender][$personality] ?? $queries[$gender]['general'];

        try {
            $imageUrl = $this->imageManager->getRandomImage($query);
            return $this->downloadAndStore($imageUrl, 'stock_photo');

        } catch (\Exception $e) {
            throw new \Exception("Stock photo fetch failed: " . $e->getMessage());
        }
    }

    /**
     * Download and store image
     */
    protected function downloadAndStore(string $imageUrl, string $source): string
    {
        try {
            // Download image
            $imageContent = file_get_contents($imageUrl);
            
            if ($imageContent === false) {
                throw new \Exception("Failed to download image");
            }

            // Generate filename
            $extension = $this->getImageExtension($imageUrl);
            $filename = 'profile_' . uniqid() . '_' . time() . '.' . $extension;
            $path = 'avatars/' . $filename;

            // Store in public disk
            Storage::disk('public')->put($path, $imageContent);

            // Return public URL
            return Storage::disk('public')->url($path);

        } catch (\Exception $e) {
            Log::error('Image Download Failed', [
                'url' => $imageUrl,
                'source' => $source,
                'error' => $e->getMessage()
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

    /**
     * Check if should use AI generation
     */
    protected function shouldUseAIGeneration(?string $provider): bool
    {
        if ($provider === 'ai_generated') {
            return true;
        }

        // Check if ChatGPT is available (has image generation)
        $availableProviders = $this->aiManager->getAvailableProviders();
        return in_array('chatgpt', $availableProviders);
    }

    /**
     * Get default avatar
     */
    protected function getDefaultAvatar(string $gender): string
    {
        $defaults = [
            'male' => '/images/default-avatar-male.png',
            'female' => '/images/default-avatar-female.png'
        ];

        return $defaults[$gender] ?? $defaults['male'];
    }
}
