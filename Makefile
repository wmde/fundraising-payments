current_user  := $(shell id -u)
current_group := $(shell id -g)
BUILD_DIR     := $(PWD)
DOCKER_FLAGS  := --interactive --tty
DOCKER_IMAGE  := registry.gitlab.com/fun-tech/fundraising-frontend-docker
COVERAGE_FLAGS := --coverage-html coverage
WAIT_FOR_IT := build/wait-for-it.sh database:3306 -t 10 --

install-php:
	docker run --rm $(DOCKER_FLAGS) \
		--volume ~/.composer:/composer \
		--volume $(BUILD_DIR):/app \
		-w /app \
		--user $(current_user):$(current_group) \
		$(DOCKER_IMAGE) composer install $(COMPOSER_FLAGS)

update-php:
	docker run --rm $(DOCKER_FLAGS) \
		--volume ~/.composer:/composer \
		--volume $(BUILD_DIR):/app \
		-w /app \
		--user $(current_user):$(current_group) \
		$(DOCKER_IMAGE) composer update $(COMPOSER_FLAGS)

ci: phpunit cs stan check-dependencies

ci-with-coverage: phpunit-with-coverage cs stan check-dependencies

test: phpunit

phpunit:
	docker-compose run --rm app $(WAIT_FOR_IT) ./vendor/bin/phpunit

phpunit-with-coverage:
	docker-compose run --rm -e XDEBUG_MODE=coverage app $(WAIT_FOR_IT) \
		./vendor/bin/phpunit $(COVERAGE_FLAGS)

cs:
	docker-compose run --rm --no-deps app ./vendor/bin/phpcs

fix-cs:
	docker-compose run --rm --no-deps app ./vendor/bin/phpcbf

stan:
	docker-compose run --rm --no-deps app \
		php -d memory_limit=1G \
		./vendor/bin/phpstan analyse --level=9 --no-progress src/ tests/

check-dependencies:
	docker-compose run --rm --no-deps app \
		php \
		./vendor/bin/deptrac --no-progress --fail-on-uncovered --report-uncovered

generate-test-inspectors:
	docker-compose run --rm --no-deps app php ./tests/generate_inspectors

setup: install-php generate-test-inspectors

.PHONY: install-php update-php ci ci-with-coverage test phpunit phpunit-with-coverage cs fix-cs stan generate-test-inspectors setup
