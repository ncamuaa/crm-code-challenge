# CRM Customer Management — Code Challenge Submission

A CRUD application for managing customer records, built to the FieldMagic code
challenge spec.

## Stack

| Concern           | Choice                                                        |
|--------------------|----------------------------------------------------------------|
| Backend API        | Lumen 10 (PHP 8.2), hand-rolled Docker (no `laravel/sail`)     |
| Frontend           | Angular 16 + Bootstrap 5                                       |
| Database           | MySQL 8                                                        |
| Search             | Elasticsearch 8 (REST API via Guzzle only — no Laravel Scout)  |
| Reverse proxy / LB | Nginx, forwards to the `api` service                           |

## Architecture

```
                 ┌────────────┐
   browser ───▶  │  frontend  │  (Angular SPA, served by its own nginx)
                 └─────┬──────┘
                       │ HTTP (JSON)
                       ▼
                 ┌────────────┐
                 │ controller │  nginx reverse proxy / load balancer
                 └─────┬──────┘
                       │ fastcgi
                       ▼
                 ┌────────────┐        ┌────────────┐
                 │    api     │◀──────▶│  database  │  MySQL (source of truth)
                 │  (Lumen)   │        └────────────┘
                 └─────┬──────┘
                       │ HTTP (Guzzle)
                       ▼
                 ┌────────────┐
                 │  searcher  │  Elasticsearch (search index only)
                 └────────────┘
```

**Backend design notes** (this is what the "Developer Notes" section of the
brief is really grading, so it's worth spelling out):

- **Repository pattern** — `CustomerRepositoryInterface` / `CustomerRepository`.
  The controller depends on the interface, not Eloquent, which is what
  actually makes the search-sync requirement testable and mockable.
- **Search is its own boundary** — `SearchServiceInterface` /
  `ElasticsearchService`. `ElasticsearchService` talks to Elasticsearch's
  HTTP API using nothing but Guzzle, per the spec's "avoid Scout" rule. If ES
  is briefly unavailable, indexing failures are logged, not thrown — the
  database stays the source of truth and a write to `/customers` never fails
  because the search cluster hiccuped.
- **Sync happens via an Eloquent observer** (`CustomerObserver`), not inline
  in the controller. `created`/`updated`/`deleted` model events push to the
  index automatically, so there's exactly one place that can forget to sync,
  and it isn't the controller.
- **`GET /api/customers?q=...`** is served from Elasticsearch (multi-match
  across first name, last name, full name, and email, with fuzziness for
  typo tolerance); without `q` it's a plain paginated DB listing. This keeps
  "browse everything" cheap and "search" fuzzy, instead of forcing one query
  shape to do both jobs.
- Validation rules live in `CustomerRules` as a plain reusable class (Lumen
  doesn't resolve `FormRequest`s the way full Laravel does), not duplicated
  inline in the controller.
- Tests use an in-memory SQLite DB and a `FakeSearchService` test double, so
  `composer test` runs in-process with no Docker/ES dependency.

## Prerequisites

- Docker & Docker Compose
- Ports `8000` (API), `4200` (frontend), `3306` (MySQL), `9200` (Elasticsearch)
  free on your host

## Running it

```bash
git clone <this-repo-url> crm-challenge
cd crm-challenge

# copy env (already provided with sane defaults for docker-compose)
cp api/.env.example api/.env   # already present in this submission

docker-compose up --build
```

First boot will take a minute or two: MySQL initialises, Elasticsearch
starts, then the `api` container waits for both, runs migrations, and
creates the search index before starting `php-fpm`.

Once everything is healthy:

- Frontend: **http://localhost:4200**
- API (through the load balancer): **http://localhost:8000/api/customers**
- Elasticsearch: **http://localhost:9200**

## API reference

| Method | Endpoint                          | Description                                  |
|--------|------------------------------------|-----------------------------------------------|
| GET    | `/api/customers`                   | List customers, paginated (`page`, `per_page`) |
| GET    | `/api/customers?q=term`            | Search by name/email via Elasticsearch        |
| GET    | `/api/customers/{id}`              | View one customer                             |
| POST   | `/api/customers`                   | Create a customer                             |
| PUT    | `/api/customers/{id}`              | Update a customer                             |
| DELETE | `/api/customers/{id}`              | Delete a customer                             |

Request/response body:

```json
{
  "first_name": "Ada",
  "last_name": "Lovelace",
  "email": "ada@example.com",
  "contact_number": "+61400000000"
}
```

## Running tests

```bash
cd api
composer install
composer test          # phpunit, in-memory sqlite, no Docker needed
```

## Running the frontend outside Docker (optional, for faster iteration)

```bash
cd frontend
npm install
npm start               # serves on http://localhost:4200, proxies to :8000
```

## Project layout

```
crm-challenge/
├── api/                  # Lumen backend
│   ├── app/
│   │   ├── Console/Commands/   # search:setup artisan command
│   │   ├── Exceptions/         # consistent JSON error responses
│   │   ├── Http/Controllers/
│   │   ├── Http/Requests/      # validation rule sets
│   │   ├── Models/
│   │   ├── Observers/          # syncs Customer -> Elasticsearch
│   │   ├── Providers/
│   │   ├── Repositories/       # data access boundary
│   │   └── Services/           # search boundary (Guzzle -> ES)
│   ├── database/{migrations,factories}/
│   ├── tests/{Feature,Unit,Doubles}/
│   └── Dockerfile
├── frontend/              # Angular 16 SPA
│   └── src/app/
│       ├── core/{models,services}/
│       └── customers/{customer-list,customer-form,customer-view}/
├── nginx/                  # controller/load-balancer service
├── docker-compose.yml
└── README.md
```

## Notes / trade-offs

- MySQL and Elasticsearch data are persisted in named Docker volumes
  (`db_data`, `es_data`) so state survives `docker-compose down` (but not
  `docker-compose down -v`).
- Elasticsearch security is disabled (`xpack.security.enabled=false`) for
  local/dev simplicity, matching the scope of a take-home exam. In a real
  deployment it would sit behind the same network boundary as `database`
  and never be exposed on a public port.
- `api` runs a single php-fpm process behind the `controller` nginx service;
  `docker-compose up --scale api=3` will load-balance across replicas since
  `controller.conf`'s upstream resolves the `api` service name.
