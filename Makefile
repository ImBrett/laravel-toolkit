SHELL := /bin/bash
PHP     ?= php
DC      ?= docker compose
SERVICE ?= php
.PHONY: help shell install update dump-autoload test test-coverage stan ecs ecs-fix rector insights phpmd metrics ci clean

help:
	@echo "Common targets:"
	@echo "  make shell            - bash into the php container"
	@echo "  make install          - composer install (root & tool bins)"
	@echo "  make update           - composer update"
	@echo "  make test             - run tests"
	@echo "  make test-coverage    - run tests with coverage"
	@echo "  make stan             - run phpstan"
	@echo "  make ecs              - run ECS check"
	@echo "  make ecs-fix          - run ECS fix"
	@echo "  make rector           - run Rector (dry-run by default)"
	@echo "  make insights         - run PHP Insights"
	@echo "  make phpmd            - run PHPMD"
	@echo "  make metrics          - run PhpMetrics (HTML to build/metrics)"
	@echo "  make ci               - stan + ecs + test"

shell:
	$(DC) run --rm $(SERVICE) bash

install:
	$(DC) run --rm $(SERVICE) composer install

update:
	$(DC) run --rm $(SERVICE) composer update

dump-autoload:
	$(DC) run --rm $(SERVICE) composer dump-autoload

test:
	$(DC) run --rm $(SERVICE) bash -lc "vendor/bin/phpunit -c phpunit.xml.dist $(TEST_ARGS)"

test-coverage:
	$(DC) run -e XDEBUG_MODE=coverage --rm $(SERVICE) bash -lc 'vendor/bin/phpunit -c phpunit.xml.dist --coverage-text --coverage-clover build/logs/clover.xml'

stan:
	$(DC) run --rm $(SERVICE) composer stan

ecs:
	$(DC) run --rm $(SERVICE) composer ecs

ecs-fix:
	$(DC) run --rm $(SERVICE) composer ecs-fix

rector:
	$(DC) run --rm $(SERVICE) composer rector

insights:
	$(DC) run --rm $(SERVICE) composer insights

phpmd:
	$(DC) run --rm $(SERVICE) composer phpmd

metrics:
	$(DC) run --rm $(SERVICE) composer metrics

ci:
	$(DC) run --rm $(SERVICE) composer ci

clean:
	rm -rf build/metrics build/logs || true
