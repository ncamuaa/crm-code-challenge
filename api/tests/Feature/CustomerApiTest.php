<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\SearchServiceInterface;
use Tests\Doubles\FakeSearchService;
use Tests\TestCase;

class CustomerApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Swap the real Elasticsearch client for an in-memory fake.
        $this->app->instance(SearchServiceInterface::class, new FakeSearchService());
    }

    public function test_it_creates_a_customer(): void
    {
        $payload = [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'contact_number' => '+61400000000',
        ];

        $this->json('POST', '/api/customers', $payload)
            ->seeStatusCode(201)
            ->seeJsonContains(['email' => 'ada@example.com']);

        $this->seeInDatabase('customers', ['email' => 'ada@example.com']);
    }

    public function test_it_rejects_duplicate_emails(): void
    {
        Customer::factory()->create(['email' => 'dupe@example.com']);

        $this->json('POST', '/api/customers', [
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'dupe@example.com',
            'contact_number' => '+61400000001',
        ])->seeStatusCode(422);
    }

    public function test_it_requires_first_and_last_name(): void
    {
        $this->json('POST', '/api/customers', [
            'email' => 'noname@example.com',
            'contact_number' => '+61400000002',
        ])->seeStatusCode(422)
          ->seeJsonStructure(['first_name', 'last_name']);
    }

    public function test_it_lists_customers(): void
    {
        Customer::factory()->count(3)->create();

        $this->json('GET', '/api/customers')
            ->seeStatusCode(200)
            ->seeJsonStructure(['data', 'meta' => ['total', 'page', 'per_page', 'source']]);
    }

    public function test_it_shows_a_single_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->json('GET', "/api/customers/{$customer->id}")
            ->seeStatusCode(200)
            ->seeJsonContains(['email' => $customer->email]);
    }

    public function test_it_returns_404_for_unknown_customer(): void
    {
        $this->json('GET', '/api/customers/does-not-exist')
            ->seeStatusCode(404);
    }

    public function test_it_updates_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->json('PUT', "/api/customers/{$customer->id}", ['first_name' => 'Updated'])
            ->seeStatusCode(200)
            ->seeJsonContains(['first_name' => 'Updated']);

        $this->seeInDatabase('customers', ['id' => $customer->id, 'first_name' => 'Updated']);
    }

    public function test_it_deletes_a_customer(): void
    {
        $customer = Customer::factory()->create();

        $this->json('DELETE', "/api/customers/{$customer->id}")
            ->seeStatusCode(204);

        $this->notSeeInDatabase('customers', ['id' => $customer->id]);
    }

    public function test_search_matches_by_name_or_email(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Katherine',
            'last_name' => 'Johnson',
            'email' => 'katherine.johnson@example.com',
        ]);

        // The observer synced this customer into the fake search index on create.
        $this->json('GET', '/api/customers?q=Johnson')
            ->seeStatusCode(200)
            ->seeJsonContains(['email' => $customer->email])
            ->seeJsonContains(['source' => 'search']);
    }
}
