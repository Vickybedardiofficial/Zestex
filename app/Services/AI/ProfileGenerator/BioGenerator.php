<?php

namespace App\Services\AI\ProfileGenerator;

use App\Services\AI\AIProviderManager;

class BioGenerator
{
    protected AIProviderManager $aiManager;

    public function __construct()
    {
        $this->aiManager = new AIProviderManager();
    }

    public function generateBio(string $personality, string $country, string $language, string $name): string
    {
        $prompt = $this->buildPrompt($personality, $country, $language, $name);

        try {
            $bio = $this->aiManager->generateText($prompt, null, [
                'temperature' => 0.8,
                'max_tokens' => 150
            ]);

            // Clean up
            $bio = trim($bio);
            $bio = str_replace(['"', '"', '"'], '', $bio);
            
            // Limit to 160 characters
            if (strlen($bio) > 160) {
                $bio = substr($bio, 0, 157) . '...';
            }

            return $bio;

        } catch (\Exception $e) {
            // Fallback to template-based bio
            return $this->getTemplateBio($personality, $country);
        }
    }

    protected function buildPrompt(string $personality, string $country, string $language, string $name): string
    {
        $countryNames = [
            'IN' => 'India',
            'US' => 'United States',
            'PK' => 'Pakistan',
            'GB' => 'United Kingdom',
            'BD' => 'Bangladesh'
        ];

        $countryName = $countryNames[$country] ?? 'India';

        $prompts = [
            'political' => "Write a short Twitter bio (max 160 characters) for a person named {$name} from {$countryName} who is passionate about politics and current affairs. Write in {$language} language. Make it realistic and engaging.",
            
            'sports' => "Write a short Twitter bio (max 160 characters) for a sports enthusiast named {$name} from {$countryName}. They love cricket and sports. Write in {$language} language. Make it realistic.",
            
            'tech' => "Write a short Twitter bio (max 160 characters) for a tech person named {$name} from {$countryName}. They're into technology, coding, and AI. Write in {$language} language.",
            
            'entertainment' => "Write a short Twitter bio (max 160 characters) for an entertainment lover named {$name} from {$countryName}. They love movies, memes, and pop culture. Write in {$language} language.",
            
            'general' => "Write a short Twitter bio (max 160 characters) for a regular person named {$name} from {$countryName}. Make it relatable and realistic. Write in {$language} language."
        ];

        return $prompts[$personality] ?? $prompts['general'];
    }

    protected function getTemplateBio(string $personality, string $country): string
    {
        $templates = [
            'IN' => [
                'political' => [
                    'Delhi se hoon, politics aur desh ki haalat pe baat karna pasand hai. Sach bolne se nahi darrta.',
                    'Indian politics enthusiast. Questioning everything, accepting nothing blindly. 🇮🇳',
                    'Desh ki haalat pe nazar rakhta hoon. Real issues, real talk. No filter.',
                    'Political observer. Aam aadmi ki awaaz. Sach kadwa hota hai lekin zaroori hai.'
                ],
                'sports' => [
                    'Cricket fanatic. Team India supporter. Dhoni ka fan. 🏏',
                    'Sports lover. Cricket, football, kabaddi - sab dekhta hoon. India Zindabad!',
                    'Die-hard cricket fan. Virat Kohli supremacy. IPL addict.',
                    'Sports enthusiast from India. Cricket runs in my blood. 🇮🇳🏏'
                ],
                'tech' => [
                    'Software engineer. AI enthusiast. Building the future one line of code at a time.',
                    'Tech geek from India. Love coding, AI, and innovation. Always learning.',
                    'Developer | Tech blogger | AI explorer. Making cool stuff with code.',
                    'Technology enthusiast. Passionate about AI and machine learning. India 🇮🇳'
                ],
                'entertainment' => [
                    'Bollywood lover. Meme creator. Entertainment ki duniya mein kho jaata hoon.',
                    'Movies, memes, and music. That\'s my life. Bollywood fanatic.',
                    'Entertainment addict. Filmy baatein karna pasand hai. Shah Rukh fan forever.',
                    'Pop culture enthusiast. Bollywood, Hollywood, sab dekhta hoon. Memes are life.'
                ],
                'general' => [
                    'Aam insaan hoon. Zindagi ke ups and downs dekhte hain. Real talk only.',
                    'Regular guy from India. Sharing thoughts on life, society, and everything.',
                    'Just another Indian trying to make sense of this world. Real and unfiltered.',
                    'Common man with uncommon thoughts. India se hoon, India ki baat karunga.'
                ]
            ],
            'US' => [
                'political' => [
                    'American patriot. Questioning the system, demanding accountability. 🇺🇸',
                    'Political junkie. Left or right, I call out BS when I see it.',
                    'US politics observer. Democracy advocate. Truth matters.',
                    'American citizen tired of the same old politics. Time for change.'
                ],
                'sports' => [
                    'Sports fanatic. NFL, NBA, MLB - I watch it all. Go team! 🏈',
                    'Die-hard sports fan from the USA. Living and breathing athletics.',
                    'American sports enthusiast. Football season is the best season.',
                    'Sports lover. Patriots fan. Living the American dream through sports.'
                ],
                'tech' => [
                    'Silicon Valley mindset. Tech entrepreneur. Innovation is my passion.',
                    'Software engineer from the US. Building the future with code and AI.',
                    'Tech geek. Startup enthusiast. Always chasing the next big thing.',
                    'American developer. Love tech, AI, and disrupting industries.'
                ],
                'general' => [
                    'Regular American. Sharing my thoughts on life, liberty, and everything.',
                    'Just a guy from the USA trying to navigate this crazy world.',
                    'American citizen. Real talk about real issues. No BS.',
                    'Living the American dream, one day at a time. Real and honest.'
                ]
            ],
            'PK' => [
                'political' => [
                    'Pakistan se hoon. Siyasat aur mulk ki haalat pe nazar rakhta hoon. 🇵🇰',
                    'Pakistani political observer. Questioning everything. Pakistan Zindabad.',
                    'Mulk ki fikr hai. Sach bolna zaroori hai. Pakistan first.',
                    'Political enthusiast from Pakistan. Real issues, real solutions.'
                ],
                'sports' => [
                    'Cricket lover from Pakistan. Babar Azam fan. Green jersey pride. 🏏',
                    'Pakistani sports enthusiast. Cricket is life. Pakistan Zindabad!',
                    'Die-hard Pakistan cricket fan. 1992 World Cup yaad hai.',
                    'Sports fanatic. Pakistan cricket team supporter. Green and proud.'
                ],
                'general' => [
                    'Pakistan se hoon. Zindagi ke tajurbe share karta hoon.',
                    'Regular Pakistani. Real thoughts, real life. Pakistan Zindabad.',
                    'Just a Pakistani trying to make sense of this world.',
                    'Common man from Pakistan. Sharing my perspective on life.'
                ]
            ]
        ];

        $countryTemplates = $templates[$country] ?? $templates['IN'];
        $personalityTemplates = $countryTemplates[$personality] ?? $countryTemplates['general'];

        return $personalityTemplates[array_rand($personalityTemplates)];
    }
}
