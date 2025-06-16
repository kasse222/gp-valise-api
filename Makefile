up: copy-env chmod-entrypoint
	docker-compose up -d --build

down:
	docker-compose down

restart: down up

logs:
	docker-compose logs -f --tail=100

logs-app:
	docker-compose logs -f app

bash:
	docker-compose exec app bash

seed:
	docker-compose exec app php artisan db:seed

key:
	docker-compose exec app php artisan key:generate

test:
	docker-compose exec app ./vendor/bin/pest

chmod-entrypoint:
	chmod +x docker/php/entrypoint.sh

copy-env:
	cp .env.docker .env
