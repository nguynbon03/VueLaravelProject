.PHONY: up down migrate test test-backend build-frontend

up:
	cp -n .env.example .env || true
	docker compose up -d --build
	@echo "Schema migrated by the one-shot migrate service (seeds on first boot)."

down:
	docker compose down

migrate:
	@echo "Schema migrated by the one-shot migrate service (seeds on first boot)."

test:
	docker compose config --quiet
	cd backend && php artisan test
	cd frontend && npm run build

test-backend:
	cd backend && php artisan test

build-frontend:
	cd frontend && npm run build
