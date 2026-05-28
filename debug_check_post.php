<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\Post;

echo "=== Checking Post wn1Jx3Lw6vB8ojY0 ===\n\n";

// Check if post exists at all
$postAny = Post::whereHashId('wn1Jx3Lw6vB8ojY0')->first();
echo "Post (any status): ";
var_dump($postAny);
echo "\n";

// Check if post is active
$postActive = Post::active()->whereHashId('wn1Jx3Lw6vB8ojY0')->first();
echo "Post (active only): ";
var_dump($postActive);
echo "\n";

// Get total posts
$totalPosts = Post::count();
echo "Total posts in database: $totalPosts\n\n";

// Get sample posts
$samplePosts = Post::orderBy('created_at', 'desc')->limit(5)->get(['id', 'hash_id', 'status']);
echo "Sample posts:\n";
foreach ($samplePosts as $post) {
    echo "  ID: {$post->id}, Hash: {$post->hash_id}, Status: {$post->status}\n";
}
