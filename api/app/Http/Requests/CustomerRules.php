<?php

namespace App\Http\Requests;

/**
 * Lumen doesn't ship FormRequest resolution the way full Laravel does,
 * so validation rule sets live here as plain, reusable, testable value
 * objects instead of being inlined (and duplicated) in the controller.
 */
class CustomerRules
{
    public static function forCreate(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'contact_number' => ['required', 'string', 'max:50'],
        ];
    }

    public static function forUpdate(string $customerId): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', "unique:customers,email,{$customerId},id"],
            'contact_number' => ['sometimes', 'required', 'string', 'max:50'],
        ];
    }
}
