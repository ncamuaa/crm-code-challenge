<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRules;
use App\Repositories\CustomerRepositoryInterface;
use App\Services\SearchServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class CustomerController extends BaseController
{
    public function __construct(
        private CustomerRepositoryInterface $customers,
        private SearchServiceInterface $searchService
    ) {
    }

    /**
     * GET /api/customers
     * GET /api/customers?q=jane&per_page=10&page=2
     *
     * When "q" is present, the listing is served from Elasticsearch and
     * matches against name and email, per the spec. Without "q" it falls
     * back to a plain paginated DB listing.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $page = (int) $request->input('page', 1);
        $query = trim((string) $request->input('q', ''));

        if ($query !== '') {
            $results = $this->searchService->search($query, $page, $perPage);

            return response()->json([
                'data' => $results['hits'],
                'meta' => [
                    'total' => $results['total'],
                    'page' => $page,
                    'per_page' => $perPage,
                    'source' => 'search',
                ],
            ]);
        }

        $paginated = $this->customers->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'total' => $paginated->total(),
                'page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'source' => 'database',
            ],
        ]);
    }

    /**
     * GET /api/customers/{id}
     */
    public function show(string $id): JsonResponse
    {
        $customer = $this->customers->find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        return response()->json(['data' => $customer]);
    }

    /**
     * POST /api/customers
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, CustomerRules::forCreate());

        $customer = $this->customers->create($request->only([
            'first_name', 'last_name', 'email', 'contact_number',
        ]));

        return response()->json(['data' => $customer], 201);
    }

    /**
     * PUT/PATCH /api/customers/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $customer = $this->customers->find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        $this->validate($request, CustomerRules::forUpdate($id));

        $customer = $this->customers->update(
            $customer,
            $request->only(['first_name', 'last_name', 'email', 'contact_number'])
        );

        return response()->json(['data' => $customer]);
    }

    /**
     * DELETE /api/customers/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $customer = $this->customers->find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found.'], 404);
        }

        $this->customers->delete($customer);

        return response()->json(null, 204);
    }
}
