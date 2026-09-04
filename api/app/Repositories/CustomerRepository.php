<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function find(string $id): ?Customer
    {
        return Customer::find($id);
    }

    public function create(array $attributes): Customer
    {
        return Customer::create($attributes);
    }

    public function update(Customer $customer, array $attributes): Customer
    {
        $customer->fill($attributes);
        $customer->save();

        return $customer->refresh();
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function emailExists(string $email, ?string $excludeId = null): bool
    {
        return Customer::query()
            ->where('email', $email)
            ->when($excludeId, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }
}
