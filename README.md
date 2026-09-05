# CRM Customer Management

CRUD app for managing customer records, built for the FieldMagic code challenge.

## Stack

- Backend: Lumen 10 (PHP 8.2), custom Docker setup (no laravel/sail)
- Frontend: Angular 16 + Bootstrap 5
- Database: MySQL 8
- Search: Elasticsearch 8, using Guzzle directly (no Laravel Scout)
- Reverse proxy: Nginx, forwards requests to the api service

## Architecture

```
browser -> frontend (Angular, served by nginx)
        -> controller (nginx reverse proxy)
        -> api (Lumen) -> database (MySQL)
                        -> searcher (Elasticsearch, via Guzzle)
```

## Backend notes

- Used a repository pattern (`CustomerRepositoryInterface` / `CustomerRepository`) so the controller doesn't depend directly on Eloquent. Makes it easier to test.
- Search logic is separated into `SearchServiceInterface` / `ElasticsearchService`. Talks to Elasticsearch's HTTP API with Guzzle only, per the spec. If Elasticsearch is down, it logs the error instead of failing the request — the database stays the source of truth.
- Syncing to Elasticsearch happens through a model observer (`CustomerObserver`) on create/update/delete, instead of inside the controller.
- `GET /api/customers?q=...` searches Elasticsearch across name and email. Without `q` it just lists from the database.
- Validation rules are in `CustomerRules` since Lumen doesn't support FormRequest classes the same way Laravel does.
- Tests run against an in-memory SQLite DB with a fake search service, so they don't need Docker running.

## Prerequisites

- Docker & Docker Compose
- Ports 8000, 4200, 3306, 9200 free

## Running it

```bash
git clone <repo-url> crm-challenge
cd crm-challenge
cp api/.env.example api/.env
docker-compose up --build
```

- Frontend: http://localhost:4200
- API: http://localhost:8000/api/customers
- Elasticsearch: http://localhost:9200

## API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/customers | List (paginated) |
| GET | /api/customers?q=term | Search by name/email |
| GET | /api/customers/{id} | View one |
| POST | /api/customers | Create |
| PUT | /api/customers/{id} | Update |
| DELETE | /api/customers/{id} | Delete |

Body:
```json
{
  "first_name": "Ada",
  "last_name": "Lovelace",
  "email": "ada@example.com",
  "contact_number": "+61400000000"
}
```

## Tests

```bash
cd api
composer install
composer test
```

## Frontend without Docker

```bash
cd frontend
npm install
npm start
```

## Project layout

```
crm-challenge/
├── api/          # Lumen backend
├── frontend/     # Angular SPA
├── nginx/        # reverse proxy config
├── docker-compose.yml
└── README.md
```

## Notes

- MySQL and Elasticsearch data persist in Docker volumes across `docker-compose down`, but not with `-v`.
- Elasticsearch security is off for local dev simplicity — would be locked down in production.
