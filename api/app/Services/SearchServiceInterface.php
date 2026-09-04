<?php

namespace App\Services;

use App\Models\Customer;

interface SearchServiceInterface
{
    /**
     * Create the index with an explicit mapping if it doesn't already exist.
     */
    public function ensureIndex(): void;

    /**
     * Upsert a single customer document (used on create/update).
     */
    public function index(Customer $customer): void;

    /**
     * Remove a customer document (used on delete).
     */
    public function delete(string $customerId): void;

    /**
     * Full-text search across name and email fields.
     *
     * @return array{total:int, hits:array<int, array<string,mixed>>}
     */
    public function search(string $query, int $page = 1, int $perPage = 15): array;
}
