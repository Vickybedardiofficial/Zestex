<?php

namespace App\Console\Commands;

use App\Services\News\NewsAggregator;
use Illuminate\Console\Command;

class FetchNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest news from all configured sources';

    protected NewsAggregator $newsAggregator;

    public function __construct()
    {
        parent::__construct();
        $this->newsAggregator = new NewsAggregator();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🗞️  Fetching latest news from all sources...');

        $results = $this->newsAggregator->fetchAllNews();

        $this->newLine();
        $this->info('✅ News fetch complete!');
        $this->table(
            ['Source', 'Articles Fetched'],
            [
                ['Google News', $results['google_news']],
                ['RSS Feeds', $results['rss']],
                ['Total', $results['total']],
            ]
        );

        // Show trending topics
        $trending = $this->newsAggregator->getTrendingTopics();
        if (!empty($trending)) {
            $this->newLine();
            $this->info('🔥 Trending Topics:');
            $this->line('  ' . implode(', ', array_slice($trending, 0, 5)));
        }

        return 0;
    }
}
