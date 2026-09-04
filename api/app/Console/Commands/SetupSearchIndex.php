<?php

namespace App\Console\Commands;

use App\Services\SearchServiceInterface;
use Illuminate\Console\Command;

class SetupSearchIndex extends Command
{
    protected $signature = 'search:setup';
    protected $description = 'Create the Elasticsearch index and mapping for customers if it does not exist yet.';

    public function handle(SearchServiceInterface $searchService): int
    {
        $searchService->ensureIndex();
        $this->info('Search index is ready.');

        return self::SUCCESS;
    }
}
