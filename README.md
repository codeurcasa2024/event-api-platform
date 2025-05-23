# event-api

API Platform / Symfony – gestion d’événements B2B

## Installation

```bash
docker compose up -d
docker compose exec php composer install
docker compose exec php bin/console doctrine:migrations:migrate

Usage
Docs OpenAPI : http://localhost:8000/api/docs

Endpoints protégés par JWT (/login, /api/events, /api/invitations, …)

Tests

APP_ENV=test docker compose exec php vendor/bin/phpunit
