current_user  := $(shell id -u)
current_group := $(shell id -g)
BUILD_DIR     := $(PWD)
DOCKER_FLAGS  := --interactive --tty
DOCKER_IMAGE  := php:8.1-alpine
COVERAGE_FLAGS := --coverage-html coverage
WAIT_FOR_IT := build/wait-for-it.sh database:3306 -t 10 --

install-php:
	docker run --rm $(DOCKER_FLAGS) --volume $(BUILD_DIR):/app -w /app --volume ~/.composer:/composer --user $(current_user):$(current_group) composer install $(COMPOSER_FLAGS)

update-php:
	docker run --rm $(DOCKER_FLAGS) --volume $(BUILD_DIR):/app -w /app --volume ~/.composer:/composer --user $(current_user):$(current_group) composer update $(COMPOSER_FLAGS)

ci: phpunit cs stan

ci-with-coverage: phpunit-with-coverage cs stan

test: phpunit

phpunit:
	docker-compose run --rm app $(WAIT_FOR_IT) ./vendor/bin/phpunit

phpunit-with-coverage:
	docker-compose -f docker-compose.yml -f docker-compose.debug.yml run --rm -e XDEBUG_MODE=coverage app_debug $(WAIT_FOR_IT) ./vendor/bin/phpunit $(COVERAGE_FLAGS)

cs:
	docker-compose run --rm --no-deps app ./vendor/bin/phpcs

fix-cs:
	docker-compose run --rm --no-deps app ./vendor/bin/phpcbf

stan:
	docker-compose run --rm --no-deps app php -d memory_limit=1G ./vendor/bin/phpstan analyse --level=9 --no-progress src/ tests/

setup: install-php

.PHONY: install-php update-php ci ci-with-coverage test phpunit phpunit-with-coverage cs fix-cs stan setup
