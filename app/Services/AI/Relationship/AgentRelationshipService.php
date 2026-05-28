<?php

namespace App\Services\AI\Relationship;

use App\Models\AiAgent;
use Illuminate\Support\Collection;

class AgentRelationshipService
{
    /**
     * Determine deterministic relationship between two agents.
     *
     * @return string ally|rival|mentor|neutral
     */
    public function getRelationship(AiAgent $actor, AiAgent $target): string
    {
        if ($actor->id === $target->id) {
            return 'neutral';
        }

        if ($this->isMentorPair($actor, $target)) {
            return 'mentor';
        }

        $actorLean = strtolower(trim((string) ($actor->political_leaning ?? '')));
        $targetLean = strtolower(trim((string) ($target->political_leaning ?? '')));

        if ($actorLean !== '' && $targetLean !== '') {
            if ($this->isOpposingLeaning($actorLean, $targetLean)) {
                return 'rival';
            }

            if ($this->isAlignedLeaning($actorLean, $targetLean)) {
                return 'ally';
            }
        }

        if ($actor->country === $target->country && $actor->personality_type === $target->personality_type) {
            return 'ally';
        }

        return 'neutral';
    }

    /**
     * Map relationship to comment trigger used by CommentGenerator.
     */
    public function toCommentTrigger(string $relationship): string
    {
        return match ($relationship) {
            'ally' => 'ally',
            'rival' => 'rival',
            'mentor' => 'mentor',
            default => 'general',
        };
    }

    /**
     * Rank participants for chain reactions.
     */
    public function rankParticipants(AiAgent $author, Collection $candidates, int $limit = 3): Collection
    {
        return $candidates
            ->map(function (AiAgent $candidate) use ($author) {
                $relationship = $this->getRelationship($candidate, $author);
                $score = match ($relationship) {
                    'rival' => 100,
                    'ally' => 80,
                    'mentor' => 70,
                    default => 50,
                };

                if ($candidate->country !== $author->country) {
                    $score += 10; // cross-country flavor
                }

                return ['agent' => $candidate, 'relationship' => $relationship, 'score' => $score];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    protected function isMentorPair(AiAgent $actor, AiAgent $target): bool
    {
        $actorAge = (int) ($actor->age ?? 0);
        $targetAge = (int) ($target->age ?? 0);

        return $actorAge > 0 && $targetAge > 0 && ($actorAge - $targetAge) >= 12;
    }

    protected function isOpposingLeaning(string $a, string $b): bool
    {
        $left = ['left', 'far left', 'center-left', 'socialist', 'liberal'];
        $right = ['right', 'far right', 'center-right', 'conservative', 'nationalist'];

        return (in_array($a, $left, true) && in_array($b, $right, true))
            || (in_array($a, $right, true) && in_array($b, $left, true));
    }

    protected function isAlignedLeaning(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        $left = ['left', 'far left', 'center-left', 'socialist', 'liberal'];
        $right = ['right', 'far right', 'center-right', 'conservative', 'nationalist'];

        return (in_array($a, $left, true) && in_array($b, $left, true))
            || (in_array($a, $right, true) && in_array($b, $right, true));
    }
}

