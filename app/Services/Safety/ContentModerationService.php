<?php

namespace App\Services\Safety;

use Illuminate\Support\Facades\Log;

class ContentModerationService
{
    // Level 1: Allow (Default)
    // Level 2: Flag (Publish but notify admin)
    // Level 3: Hold (Do not publish, wait for review)
    // Level 4: Block (Do not publish, delete/reject)

    protected $blocklist = [
        'hate' => ['kill', 'murder', 'terrorist', 'bomb', 'suicide', 'die'],
        'pii' => ['phone number', 'address', 'credit card', 'passport'],
        'illegal' => ['drugs', 'smuggle', 'weed', 'cocaine', 'heroin'],
    ];

    protected $flaglist = [
        'stupid', 'idiot', 'dumb', 'ugly', 'fat', 'scam', 'fake'
    ];

    protected $holdlist = [
        'protest', 'riot', 'attack', 'violence', 'blood'
    ];

    /**
     * Analyze content and return moderation result
     * Returns array: ['action' => 'allow|flag|hold|block', 'reason' => '...']
     */
    public function analyze(string $content): array
    {
        $text = strtolower($content);

        // 1. Check Blocklist (Level 4)
        foreach ($this->blocklist as $category => $keywords) {
            foreach ($keywords as $word) {
                if (str_contains($text, $word)) {
                    return ['action' => 'block', 'reason' => "Contains restricted content ($category): $word"];
                }
            }
        }

        // 2. Check Holdlist (Level 3)
        foreach ($this->holdlist as $word) {
            if (str_contains($text, $word)) {
                return ['action' => 'hold', 'reason' => "Contains sensitive term: $word"];
            }
        }

        // 3. Check Flaglist (Level 2)
        foreach ($this->flaglist as $word) {
            if (str_contains($text, $word)) {
                return ['action' => 'flag', 'reason' => "Contains flagged term: $word"];
            }
        }

        // 4. Default Allow (Level 1)
        return ['action' => 'allow', 'reason' => 'Safe'];
    }
}
