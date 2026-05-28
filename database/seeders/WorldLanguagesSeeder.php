<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Locale;
use App\Actions\Locale\CDLocaleAction;
use App\Support\Languages;
use Illuminate\Support\Facades\Log;

class WorldLanguagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['name' => 'Hindi', 'native_name' => 'हिन्दी', 'alpha_2' => 'hi', 'direction' => 'ltr'],
            ['name' => 'Spanish', 'native_name' => 'Español', 'alpha_2' => 'es', 'direction' => 'ltr'],
            ['name' => 'French', 'native_name' => 'Français', 'alpha_2' => 'fr', 'direction' => 'ltr'],
            ['name' => 'German', 'native_name' => 'Deutsch', 'alpha_2' => 'de', 'direction' => 'ltr'],
            ['name' => 'Chinese', 'native_name' => '中文', 'alpha_2' => 'zh', 'direction' => 'ltr'],
            ['name' => 'Arabic', 'native_name' => 'العربية', 'alpha_2' => 'ar', 'direction' => 'rtl'],
            ['name' => 'Portuguese', 'native_name' => 'Português', 'alpha_2' => 'pt', 'direction' => 'ltr'],
            ['name' => 'Russian', 'native_name' => 'Русский', 'alpha_2' => 'ru', 'direction' => 'ltr'],
            ['name' => 'Japanese', 'native_name' => '日本語', 'alpha_2' => 'ja', 'direction' => 'ltr'],
            ['name' => 'Italian', 'native_name' => 'Italiano', 'alpha_2' => 'it', 'direction' => 'ltr'],
            ['name' => 'Korean', 'native_name' => '한국어', 'alpha_2' => 'ko', 'direction' => 'ltr'],
            ['name' => 'Turkish', 'native_name' => 'Türkçe', 'alpha_2' => 'tr', 'direction' => 'ltr'],
            ['name' => 'Dutch', 'native_name' => 'Nederlands', 'alpha_2' => 'nl', 'direction' => 'ltr'],
            ['name' => 'Polish', 'native_name' => 'Polski', 'alpha_2' => 'pl', 'direction' => 'ltr'],
            ['name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia', 'alpha_2' => 'id', 'direction' => 'ltr'],
            ['name' => 'Vietnamese', 'native_name' => 'Tiếng Việt', 'alpha_2' => 'vi', 'direction' => 'ltr'],
            ['name' => 'Thai', 'native_name' => 'ไทย', 'alpha_2' => 'th', 'direction' => 'ltr'],
            ['name' => 'Bengali', 'native_name' => 'বাংলা', 'alpha_2' => 'bn', 'direction' => 'ltr'],
        ];

        foreach ($languages as $lang) {
            if (Locale::where('alpha_2_code', $lang['alpha_2'])->exists()) {
                $this->command->info("Skipping {$lang['name']} (already exists)");
                continue;
            }

            try {
                $locale = Locale::create([
                    'name' => $lang['name'],
                    'native_name' => $lang['native_name'],
                    'alpha_2_code' => $lang['alpha_2'],
                    'direction' => $lang['direction'],
                    'status' => true,
                    'is_default' => false,
                ]);

                // Create locale files
                try {
                    (new CDLocaleAction($locale->alpha_2_code))->createLocale();
                    $this->command->info("Created {$lang['name']} and generated files.");
                } catch (\Exception $e) {
                    $this->command->warn("Created {$lang['name']} DB record, but file generation failed: " . $e->getMessage());
                    // We don't delete the DB record here as files might partially exist or be manually manageable.
                }

            } catch (\Exception $e) {
                $this->command->error("Failed to create {$lang['name']}: " . $e->getMessage());
            }
        }

        (new Languages())->refreshCache();
    }
}
