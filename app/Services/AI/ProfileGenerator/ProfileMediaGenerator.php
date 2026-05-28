<?php

namespace App\Services\AI\ProfileGenerator;

use App\Models\AiAgent;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileMediaGenerator
{
    public function generateAvatarForUser(User $user): string
    {
        $initials = $this->initials((string) ($user->name ?? 'AI Bot'));
        $country = strtoupper((string) ($user->country ?? 'GL'));
        $svg = $this->buildAvatarSvg('#39424e', $initials, 'GEN', $country);
        $path = "uploads/users/avatars/ai_agents/user_{$user->id}.svg";
        Storage::disk(static_storage_disk())->put($path, $svg);
        return $path;
    }

    public function generateCoverForUser(User $user): string
    {
        $name = trim((string) ($user->name ?? "Agent {$user->id}"));
        $country = strtoupper((string) ($user->country ?? 'GLOBAL'));
        $subtitle = "Coverage: World News & Trends | {$country}";
        $svg = $this->buildCoverSvg('#2a313a', '#59697d', $name, $subtitle);
        $path = "uploads/users/covers/ai_agents/user_{$user->id}.svg";
        Storage::disk(static_storage_disk())->put($path, $svg);
        return $path;
    }

    public function generateAvatar(AiAgent $agent): string
    {
        $user = $agent->user;
        $personality = strtolower((string) $agent->personality_type);
        $palette = $this->paletteFor($personality);
        $country = strtoupper((string) ($agent->country ?? 'GL'));
        $topic = strtoupper(substr($personality ?: 'GEN', 0, 3));

        $initials = $this->initials((string) ($user?->name ?? 'AI Bot'));
        $svg = $this->buildAvatarSvg($palette['solid'], $initials, $topic, $country);
        $path = "uploads/users/avatars/ai_agents/agent_{$agent->id}.svg";

        Storage::disk(static_storage_disk())->put($path, $svg);

        return $path;
    }

    public function generateCover(AiAgent $agent): string
    {
        $user = $agent->user;
        $personality = strtolower((string) $agent->personality_type);
        $palette = $this->paletteFor($personality);

        $name = trim((string) ($user?->name ?? "Agent {$agent->id}"));
        $country = strtoupper((string) ($agent->country ?? 'GLOBAL'));
        $topic = $this->topicLabel($personality);
        $subtitle = "Coverage: {$topic} | {$country}";

        $svg = $this->buildCoverSvg($palette['from'], $palette['to'], $name, $subtitle);
        $path = "uploads/users/covers/ai_agents/agent_{$agent->id}.svg";

        Storage::disk(static_storage_disk())->put($path, $svg);

        return $path;
    }

    protected function buildAvatarSvg(string $bg, string $initials, string $topic, string $country): string
    {
        $bg = $this->escapeXml($bg);
        $initials = $this->escapeXml($initials);
        $topic = $this->escapeXml($topic);
        $country = $this->escapeXml($country);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <rect width="512" height="512" rx="80" fill="{$bg}" />
  <circle cx="256" cy="210" r="116" fill="rgba(255,255,255,0.15)" />
  <text x="256" y="236" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="120" font-weight="700">{$initials}</text>
  <rect x="132" y="300" width="248" height="56" rx="28" fill="rgba(0,0,0,0.25)" />
  <text x="256" y="338" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="700">AI</text>
  <text x="256" y="404" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="24" font-weight="700">{$topic} • {$country}</text>
</svg>
SVG;
    }

    protected function buildCoverSvg(string $from, string $to, string $name, string $subtitle): string
    {
        $from = $this->escapeXml($from);
        $to = $this->escapeXml($to);
        $name = $this->escapeXml(Str::limit($name, 44, ''));
        $subtitle = $this->escapeXml(Str::limit($subtitle, 64, ''));

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1500" height="500" viewBox="0 0 1500 500">
  <defs>
    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
      <stop offset="0%" stop-color="{$from}" />
      <stop offset="100%" stop-color="{$to}" />
    </linearGradient>
  </defs>
  <rect width="1500" height="500" fill="url(#g)" />
  <circle cx="1280" cy="100" r="140" fill="rgba(255,255,255,0.12)" />
  <circle cx="1360" cy="380" r="180" fill="rgba(0,0,0,0.12)" />
  <rect x="80" y="90" width="760" height="320" rx="28" fill="rgba(0,0,0,0.25)" />
  <text x="120" y="195" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="64" font-weight="700">{$name}</text>
  <text x="120" y="258" fill="#f4f4f4" font-family="Arial, Helvetica, sans-serif" font-size="30" font-weight="500">{$subtitle}</text>
  <rect x="120" y="300" width="236" height="62" rx="31" fill="rgba(255,255,255,0.20)" />
  <text x="238" y="342" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="34" font-weight="700">AI Powered</text>
</svg>
SVG;
    }

    protected function paletteFor(string $personality): array
    {
        return match ($personality) {
            'political' => ['solid' => '#1f4b99', 'from' => '#12356e', 'to' => '#2f6ed7'],
            'sports' => ['solid' => '#0f7a4f', 'from' => '#0b5b3b', 'to' => '#18a66b'],
            'tech' => ['solid' => '#2f3a4a', 'from' => '#1e2530', 'to' => '#4b5f7b'],
            'entertainment' => ['solid' => '#9b3d12', 'from' => '#7b2f0f', 'to' => '#d1611f'],
            'troll' => ['solid' => '#6b1e7f', 'from' => '#4f1660', 'to' => '#9b39b8'],
            default => ['solid' => '#39424e', 'from' => '#2a313a', 'to' => '#59697d'],
        };
    }

    protected function topicLabel(string $personality): string
    {
        return match ($personality) {
            'political' => 'Politics & Public Affairs',
            'sports' => 'Sports & Live Reactions',
            'tech' => 'Technology & Innovation',
            'entertainment' => 'Entertainment & Pop Culture',
            'troll' => 'Satire & Troll Commentary',
            default => 'World News & Trends',
        };
    }

    protected function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $parts = array_values(array_filter($parts));

        if (empty($parts)) {
            return 'AI';
        }

        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 1));
        }

        return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
    }

    protected function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
