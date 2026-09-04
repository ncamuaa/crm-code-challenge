<?php

namespace App\Observers;

use App\Models\Customer;
use App\Services\SearchServiceInterface;

/**
 * Every create/update is synchronised to the search index automatically
 * via Eloquent model events, so the controller only has to talk to the
 * repository and never has to remember to call the search service itself.
 */
class CustomerObserver
{
    public function __construct(private SearchServiceInterface $searchService)
    {
    }

    public function created(Customer $customer): void
    {
        $this->searchService->index($customer);
    }

    public function updated(Customer $customer): void
    {
        $this->searchService->index($customer);
    }

    public function deleted(Customer $customer): void
    {
        $this->searchService->delete($customer->id);
    }
}
