<?php

namespace Tests\Doubles;

use App\Models\Customer;
use App\Services\SearchServiceInterface;

class FakeSearchService implements SearchServiceInterface
{
    /** @var array<string, array<string,mixed>> */
    public array $documents = [];

    public function ensureIndex(): void
    {
    }

    public function index(Customer $customer): void
    {
        $this->documents[$customer->id] = $customer->toSearchDocument();
    }

    public function delete(string $customerId): void
    {
        unset($this->documents[$customerId]);
    }

    public function search(string $query, int $page = 1, int $perPage = 15): array
    {
        $matches = array_values(array_filter(
            $this->documents,
            fn ($doc) => str_contains(strtolower($doc['full_name']), strtolower($query))
                || str_contains(strtolower($doc['email']), strtolower($query))
        ));

        return ['total' => count($matches), 'hits' => $matches];
    }
}
