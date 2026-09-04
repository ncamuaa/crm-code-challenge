<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Tests\TestCase;

class CustomerRepositoryTest extends TestCase
{
    private CustomerRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CustomerRepository();
    }

    public function test_it_detects_existing_email(): void
    {
        Customer::factory()->create(['email' => 'taken@example.com']);

        $this->assertTrue($this->repository->emailExists('taken@example.com'));
        $this->assertFalse($this->repository->emailExists('free@example.com'));
    }

    public function test_email_exists_check_can_exclude_a_record(): void
    {
        $customer = Customer::factory()->create(['email' => 'self@example.com']);

        $this->assertFalse($this->repository->emailExists('self@example.com', $customer->id));
    }

    public function test_it_creates_and_finds_a_customer(): void
    {
        $customer = $this->repository->create([
            'first_name' => 'Rear',
            'last_name' => 'Admiral',
            'email' => 'radm@example.com',
            'contact_number' => '+61400000099',
        ]);

        $found = $this->repository->find($customer->id);

        $this->assertNotNull($found);
        $this->assertSame('radm@example.com', $found->email);
    }
}
