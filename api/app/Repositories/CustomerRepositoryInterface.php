<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(string $id): ?Customer;

    public function create(array $attributes): Customer;

    public function update(Customer $customer, array $attributes): Customer;

    public function delete(Customer $customer): void;

    public function emailExists(string $email, ?string $excludeId = null): bool;
}
