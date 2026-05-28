<?php

namespace App\Services\AI\Content;

use App\Models\NewsCache;
use Illuminate\Support\Facades\Log;

class TrollingEngine
{
    protected array $safetyRules = [
        'no_personal_abuse' => true,
        'no_private_info' => true,
        'no_hate_speech' => true,
        'public_figures_only' => true,
        'max_troll_intensity' => 7, // 1-10 scale
    ];

    protected array $publicFigureKeywords = [
        // Politicians
        'modi', 'rahul', 'kejriwal', 'amit shah', 'biden', 'trump', 'obama',
        'boris johnson', 'imran khan', 'nawaz sharif',
        
        // Parties
        'bjp', 'congress', 'aap', 'democrat', 'republican', 'labour', 'conservative',
        
        // Celebrities
        'salman', 'shah rukh', 'aamir', 'akshay', 'kardashian', 'musk', 'bezos',
        
        // Companies
        'google', 'facebook', 'twitter', 'amazon', 'apple', 'microsoft',
        
        // Generic
        'government', 'sarkar', 'neta', 'politician', 'celebrity', 'actor'
    ];

    /**
     * Check if situation warrants trolling
     */
    public function shouldTroll(array $context): bool
    {
        // Check if there's controversial news
        if (!empty($context['latest_news'])) {
            foreach ($context['latest_news'] as $news) {
                if ($this->isControversial($news['title'])) {
                    return rand(1, 100) <= 60; // 60% chance to troll
                }
            }
        }

        // Random trolling chance
        return rand(1, 100) <= 20; // 20% base chance
    }

    /**
     * Generate troll post
     */
    public function generateTroll(string $target, string $reason, string $language = 'en'): string
    {
        // Verify target is safe
        if (!$this->isSafeTarget($target)) {
            throw new \Exception("Target is not a public figure");
        }

        $intensity = rand(3, $this->safetyRules['max_troll_intensity']);

        $trollStyles = [
            'sarcasm' => $this->generateSarcasm($target, $reason, $language, $intensity),
            'question' => $this->generateQuestion($target, $reason, $language, $intensity),
            'comparison' => $this->generateComparison($target, $reason, $language, $intensity),
            'meme' => $this->generateMemeStyle($target, $reason, $language, $intensity)
        ];

        $style = array_rand($trollStyles);
        return $trollStyles[$style];
    }

    /**
     * Check if target is safe (public figure)
     */
    public function isSafeTarget(string $target): bool
    {
        $target = strtolower($target);

        foreach ($this->publicFigureKeywords as $keyword) {
            if (str_contains($target, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if news is controversial
     */
    protected function isControversial(string $title): bool
    {
        $controversialKeywords = [
            'scandal', 'controversy', 'protest', 'resign', 'arrested', 'accused',
            'scam', 'corruption', 'fraud', 'fail', 'crisis', 'outrage',
            'घोटाला', 'विवाद', 'गिरफ्तार', 'आरोप', 'भ्रष्टाचार'
        ];

        $title = strtolower($title);

        foreach ($controversialKeywords as $keyword) {
            if (str_contains($title, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate sarcastic troll
     */
    protected function generateSarcasm(string $target, string $reason, string $language, int $intensity): string
    {
        if ($language === 'hi' || $language === 'en-IN') {
            $templates = [
                "Bhai ne kaha {$reason}, chaliye inhe reality check dete hain 😂",
                "{$target} ki baat sun kar hassi aa gayi. Kya logic hai!",
                "Waah {$target}, kya baat hai. Aur kuch naya batao 🙄",
                "{$target} ne phir se prove kar diya - common sense optional hai",
            ];
        } else {
            $templates = [
                "{$target} said {$reason}. Let that sink in 😂",
                "Oh {$target}, that's some impressive logic right there 🙄",
                "{$target} proving once again that reality is optional",
                "When {$target} speaks, common sense takes a vacation",
            ];
        }

        return $templates[array_rand($templates)];
    }

    /**
     * Generate questioning troll
     */
    protected function generateQuestion(string $target, string $reason, string $language, int $intensity): string
    {
        if ($language === 'hi' || $language === 'en-IN') {
            $templates = [
                "{$target} ne kaha {$reason}. Koi inka answer de sakta hai?",
                "Sawaal yeh hai - {$target} sach bol rahe hain ya mazaak?",
                "{$target} ko koi samjhao please. Yeh kya ho raha hai?",
            ];
        } else {
            $templates = [
                "{$target} said {$reason}. Can someone explain this logic?",
                "Question: Is {$target} serious or just trolling us?",
                "Someone please help {$target} understand reality",
            ];
        }

        return $templates[array_rand($templates)];
    }

    /**
     * Generate comparison troll
     */
    protected function generateComparison(string $target, string $reason, string $language, int $intensity): string
    {
        if ($language === 'hi' || $language === 'en-IN') {
            $templates = [
                "{$target} ne kaha {$reason}. Waade aur reality mein zameen aasmaan ka farak",
                "Pehle {$target} ne kuch aur kaha tha, ab yeh. Consistency kahan gayi?",
                "{$target} ki baatein aur ground reality - dono alag duniya ke hain",
            ];
        } else {
            $templates = [
                "{$target} says {$reason}. Promises vs Reality - spot the difference",
                "{$target} said something else before. Consistency left the chat",
                "What {$target} says vs what actually happens - two different worlds",
            ];
        }

        return $templates[array_rand($templates)];
    }

    /**
     * Generate meme-style troll
     */
    protected function generateMemeStyle(string $target, string $reason, string $language, int $intensity): string
    {
        if ($language === 'hi' || $language === 'en-IN') {
            $templates = [
                "POV: {$target} explaining {$reason} 🤡",
                "When {$target} says {$reason} but reality is crying in corner 💀",
                "{$target} moment: {$reason} 😂",
            ];
        } else {
            $templates = [
                "POV: {$target} explaining {$reason} 🤡",
                "When {$target} says {$reason} but reality disagrees 💀",
                "That {$target} moment when {$reason} 😂",
            ];
        }

        return $templates[array_rand($templates)];
    }

    /**
     * Get troll intensity description
     */
    public function getIntensityDescription(int $intensity): string
    {
        $descriptions = [
            1 => 'Very mild',
            2 => 'Mild',
            3 => 'Light',
            4 => 'Moderate',
            5 => 'Medium',
            6 => 'Strong',
            7 => 'Very strong',
            8 => 'Intense',
            9 => 'Very intense',
            10 => 'Maximum'
        ];

        return $descriptions[$intensity] ?? 'Unknown';
    }

    /**
     * Validate troll content
     */
    public function validateTrollContent(string $content): bool
    {
        // Check for personal abuse
        $abuseKeywords = [
            'idiot', 'stupid', 'moron', 'fool', 'dumb',
            'bewakoof', 'gadha', 'ullu', 'pagal'
        ];

        $contentLower = strtolower($content);

        foreach ($abuseKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return false;
            }
        }

        // Check for hate speech
        $hateKeywords = [
            'hate', 'kill', 'death', 'violence',
            'nafrat', 'maar', 'maut'
        ];

        foreach ($hateKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return false;
            }
        }

        return true;
    }
}
