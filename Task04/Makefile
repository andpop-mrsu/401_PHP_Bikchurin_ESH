.PHONY: install serve

install:
	composer install
	cp -n .env.example .env || true
	php artisan key:generate
	@if [ ! -f database/database.sqlite ]; then touch database/database.sqlite; fi
	php artisan migrate

serve:
	php artisan serve
