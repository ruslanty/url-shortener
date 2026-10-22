# URL shortener with stats

Small PHP backend that creates short links, redirects visitors, and counts clicks in the background

## Tech Stack

- PHP 8.5.12
- FrankenPHP
- Mezzio / FastRoute
- Laminas components
- Cycle ORM / Cycle Database
- PostgreSQL 18.6
- RabbitMQ 4.4
- PHP-CS-Fixer
- PHPStan
- PHPUnit
- Rector
- Deptrac

## Local development

Start project:

```bash
docker compose up --build -d
```

Run all quality checks:

```bash
composer quality
```

Remove project:

```bash
docker compose down --volumes --rmi all --remove-orphans
```

## API

Run API requests in PhpStorm using [`http/requests.http`](http/requests.http).

| Method | Path | Result |
| --- | --- | --- |
| `GET` | `/` | API healthcheck |
| `GET` | `/api/links` | List all links |
| `POST` | `/api/links` | Create a short link from `{"url":"https://..."}` |
| `GET` | `/{code}` | Count click and redirect to the destination |
| `GET` | `/{code}/stats` | Show destination and click count |

## How it works

1. API stores links in PostgreSQL;
2. When someone opens URL, it saves click event and redirects;
3. Background worker sends event through RabbitMQ and saves the click (duplicates are ignored);
4. The stats endpoint shows the recorded count;

## Technology choices

| Product | What it does | Why chosen |
| --- | --- | --- |
| Hexagonal Architecture | Connects application to PostgreSQL and RabbitMQ through adapters | Keeps business rules separate from HTTP and storage |
| FrankenPHP | Hosts the API and runs the PHP worker | One image runs both the API and worker |
| Mezzio / FastRoute | Matches routes and passes requests through middleware to handlers | Provides the routing and middleware used by the API |
| Laminas components | Dependency injection, configuration, and PSR-7 HTTP messages | Provides the container and HTTP building blocks the app uses |
| Cycle ORM / Cycle Database | Maps link records and writes transactional outbox data | Demonstrates ORM mapping alongside direct SQL and transactions |
| PostgreSQL | Stores URLs, click events, and statistics | Transactions and constraints support the outbox flow |
| RabbitMQ | Delivers click events to the background worker | Lets redirects return before click processing finishes |
| PHP-CS-Fixer | Checks the code against PER-CS | Keeps code style consistent across the project |
| PHPStan | Analyses code at the maximum level, with strict and deprecation rules | Finds type and API mistakes early |
| PHPUnit | Runs unit and integration tests | Covers URL validation and duplicate click delivery |
| Rector | Runs the project's refactoring rules in dry-run mode | Lets CI report possible refactorings without changing code |
| Deptrac | Checks the boundaries between application layers | Reports dependencies that cross the defined layer boundaries |
