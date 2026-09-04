<?php

namespace App\Services;

use App\Models\Customer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Talks to Elasticsearch over its HTTP REST API using nothing but Guzzle,
 * as required by the spec (no laravel/scout). Kept behind
 * SearchServiceInterface so the rest of the app never depends on this
 * concrete transport.
 */
class ElasticsearchService implements SearchServiceInterface
{
    private Client $client;
    private string $index;

    public function __construct(?Client $client = null)
    {
        $scheme = env('ELASTICSEARCH_SCHEME', 'http');
        $host = env('ELASTICSEARCH_HOST', 'searcher');
        $port = env('ELASTICSEARCH_PORT', 9200);

        $this->index = env('ELASTICSEARCH_INDEX', 'customers');

        $this->client = $client ?? new Client([
            'base_uri' => "{$scheme}://{$host}:{$port}",
            'timeout' => 5,
        ]);
    }

    public function ensureIndex(): void
    {
        try {
            $exists = $this->client->head("/{$this->index}");
            if ($exists->getStatusCode() === 200) {
                return;
            }
        } catch (GuzzleException $e) {
            // A 404 throws in Guzzle by default; fall through and create it.
        }

        try {
            $this->client->put("/{$this->index}", [
                'json' => [
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'keyword'],
                            'first_name' => ['type' => 'text'],
                            'last_name' => ['type' => 'text'],
                            'full_name' => ['type' => 'text'],
                            'email' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
                            'contact_number' => ['type' => 'keyword'],
                            'created_at' => ['type' => 'date'],
                            'updated_at' => ['type' => 'date'],
                        ],
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            // Index may already exist (race) - safe to ignore 400 "resource_already_exists_exception".
            Log::warning('Elasticsearch ensureIndex failed', ['message' => $e->getMessage()]);
        }
    }

    public function index(Customer $customer): void
    {
        try {
            $this->client->put("/{$this->index}/_doc/{$customer->id}", [
                'json' => $customer->toSearchDocument(),
            ]);
        } catch (GuzzleException $e) {
            // Sync failures must not break the CRUD request/response cycle.
            // Log and move on - the DB remains the source of truth.
            Log::error('Failed to index customer in Elasticsearch', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete(string $customerId): void
    {
        try {
            $this->client->delete("/{$this->index}/_doc/{$customerId}");
        } catch (GuzzleException $e) {
            Log::error('Failed to delete customer from Elasticsearch', [
                'customer_id' => $customerId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function search(string $query, int $page = 1, int $perPage = 15): array
    {
        $from = max(0, ($page - 1) * $perPage);

        try {
            $response = $this->client->post("/{$this->index}/_search", [
                'json' => [
                    'from' => $from,
                    'size' => $perPage,
                    'query' => [
                        'multi_match' => [
                            'query' => $query,
                            'fields' => ['first_name', 'last_name', 'full_name', 'email'],
                            'fuzziness' => 'AUTO',
                            'type' => 'best_fields',
                        ],
                    ],
                ],
            ]);

            $body = json_decode((string) $response->getBody(), true);

            return [
                'total' => (int) ($body['hits']['total']['value'] ?? 0),
                'hits' => array_map(
                    fn ($hit) => $hit['_source'],
                    $body['hits']['hits'] ?? []
                ),
            ];
        } catch (GuzzleException $e) {
            Log::error('Elasticsearch search failed', ['error' => $e->getMessage()]);

            return ['total' => 0, 'hits' => []];
        }
    }
}
