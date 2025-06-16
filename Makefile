up:
	docker-compose up -d --build

down:
	docker-compose down

restart:
	docker-compose down && docker-compose up -d --build

logs:
	docker-compose logs -f --tail=100

bash:
	docker-compose exec app bash

migrate:
	docker-compose exec app php artisan migrate

seed:
	docker-compose exec app php artisan db:seed

key:
	docker-compose exec app php artisan key:generate

test:
	docker-compose exec app ./vendor/bin/pest

init:
	chmod +x docker/php/entrypoint.sh

