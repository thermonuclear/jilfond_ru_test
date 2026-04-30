.PHONY: up down restart logs logs-php logs-nginx logs-mysql logs-queue shell shell-mysql shell-queue composer artisan migrate migrate-fresh seed test test-coverage pint pint-test pint-dirty phpstan clean install queue queue-failed queue-retry queue-flush

up:
	docker compose up -d --build
	docker compose exec php composer install --no-interaction
	docker compose exec php php artisan key:generate --no-interaction
	docker compose exec php php artisan migrate --force
	docker compose exec php php artisan db:seed
	@echo "================================"
	@echo " Project is ready!"
	@echo " App:        http://localhost:8080"
	@echo " phpMyAdmin: http://localhost:8081"
	@echo "================================"

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

logs-php:
	docker compose logs -f php

logs-nginx:
	docker compose logs -f nginx

logs-mysql:
	docker compose logs -f mysql

logs-queue:
	docker compose logs -f queue

shell:
	docker compose exec php bash

shell-mysql:
	docker compose exec mysql bash

shell-queue:
	docker compose exec queue bash

composer:
	docker compose exec php composer $(ARGS)

artisan:
	docker compose exec php php artisan $(ARGS)

migrate:
	docker compose exec php php artisan migrate --force

migrate-fresh:
	docker compose exec php php artisan migrate:fresh --force

seed:
	docker compose exec php php artisan db:seed

test:
	docker compose exec php php artisan test

test-coverage:
	docker compose exec php php artisan test --coverage

pint:
	docker compose exec php ./vendor/bin/pint --verbose

pint-test:
	docker compose exec php ./vendor/bin/pint --test --verbose

pint-dirty:
	docker compose exec php ./vendor/bin/pint --dirty --verbose

phpstan:
	docker compose exec php ./vendor/bin/phpstan analyse --no-progress

clean:
	docker compose down -v
	docker compose rm -f

install: up

queue:
	docker compose exec php php artisan queue:work redis --queue=default --tries=3 --sleep=3

queue-failed:
	docker compose exec php php artisan queue:failed

queue-retry:
	docker compose exec php php artisan queue:retry all

queue-flush:
	docker compose exec php php artisan queue:flush
