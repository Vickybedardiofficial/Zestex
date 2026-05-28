<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsCache extends Model
{
    protected $table = 'news_cache';

    protected $fillable = [
        'source',
        'category',
        'title',
        'description',
        'url',
        'image_url',
        'published_at',
        'is_trending'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_trending' => 'boolean'
    ];

    /**
     * Scope for trending news
     */
    public function scopeTrending($query)
    {
        return $query->where('is_trending', true);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for recent news
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for specific source
     */
    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }
}
