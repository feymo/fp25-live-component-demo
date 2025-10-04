-include .env .env.local
export

.DEFAULT_GOAL := help

# Variables
DOCKER 	        = docker
DOCKER_COMPOSE 	= docker compose
EXEC 			= $(DOCKER) exec
APP				= $(EXEC) -it ${COMPOSE_PROJECT_NAME}_frankenphp
APP_CI		    = $(EXEC) -i ${COMPOSE_PROJECT_NAME}_frankenphp
COMPOSER		= $(EXEC) -it -e COMPOSER_MEMORY_LIMIT=-1 ${COMPOSE_PROJECT_NAME}_frankenphp
COMPOSER_CI		= $(EXEC) -i -e COMPOSER_MEMORY_LIMIT=-1 ${COMPOSE_PROJECT_NAME}_frankenphp
CONSOLE			= $(APP) bin/console
CONSOLE_CI		= $(APP_CI) bin/console
PNPM		    = $(DOCKER_COMPOSE) run --rm node pnpm
QA              = $(DOCKER_COMPOSE) run --rm qa

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
RED    := $(shell tput -Txterm setaf 1)
YELLOW := $(shell tput -Txterm setaf 3)
BLUE   := $(shell tput -Txterm setaf 4)
RESET  := $(shell tput -Txterm sgr0)

## —— 🔥 Project ——
.env.local: .env
	@if [ -f .env.local ]; then \
		echo '${YELLOW}The ".env" has changed. You may want to update your .env.local accordingly (this message will only appear once).${RESET}'; \
		touch .env.local; \
		exit 1; \
	else \
		cp .env .env.local; \
		echo "${YELLOW}.env.local file copied from .env - Modify it according to your needs and rerun the command.${RESET}"; \
		exit 1; \
	fi

.PHONY: install
install: ## Project Installation
install: .env.local build start vendor reset-db
	@echo "${GREEN}The application is available at: https://$(SERVER_NAME)${RESET}"
	@make open

.PHONY: cache-clear
cache-clear: ## Clear cache
	$(CONSOLE) cache:clear

## —— 🐳 Docker ——
.PHONY: build
build: ## Build the container
build: init
	$(DOCKER_COMPOSE) build --build-arg APP_ENV=$(APP_ENV)

.PHONY: start
start: ## Start the containers
start: init
	$(DOCKER_COMPOSE) up -d --remove-orphans

.PHONY: init
init: ## Init the app
init: compose.override.yaml
	@echo "${YELLOW}Adding SERVER_NAME value in your \"/etc/hosts\" file....${RESET}"
	cat /etc/hosts |grep -q $(SERVER_NAME) || sudo sh -c "echo 127.0.0.1 $(SERVER_NAME) >> /etc/hosts"

.PHONY: stop
stop: ## Stop the containers
	$(DOCKER_COMPOSE) stop

.PHONY: restart
restart: ## restart the containers
restart: stop start

.PHONY: kill
kill: ## Forces running containers to stop by sending a SIGKILL signal
	$(DOCKER_COMPOSE) kill

.PHONY: down
down: ## Stops containers
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

.PHONY: reset
reset: ## Stop and start a fresh install of the project
reset: kill down install

.PHONY: open
open: ## Open the project in the browser
open:
	@echo "${GREEN}Opening https://$(SERVER_NAME)${RESET}"
	@open https://$(SERVER_NAME)

.PHONY: bash
bash: ## Open a new bash shell in php container
	$(APP) /bin/bash

## —— 🎻 Composer ——
vendor: ## Install dependencies
vendor: composer.lock
	$(COMPOSER) composer install

vendor-ci: ## Install dependencies
vendor-ci: composer.lock
	$(COMPOSER_CI) composer install

.PHONY: composer-update
composer-update: ## Update dependencies
	$(COMPOSER) composer update

.PHONY: composer-validate
composer-validate: ## Validate composer.json file
	$(APP) composer validate --strict $(ANSI_COLOR)
	$(APP) composer check-platform-reqs --lock $(ANSI_COLOR)

.PHONY: composer-validate-ci
composer-validate-ci: ## Validate composer.json file
	$(APP_CI) composer validate --strict $(ANSI_COLOR)
	$(APP_CI) composer check-platform-reqs --lock $(ANSI_COLOR)

## —— 📊 Database ——
.PHONY: reset-test-db
reset-test-db: ## Reset Database before test
reset-test-db:
    # Needs database container to be running (run "make start" if needed)
	$(CONSOLE) doctrine:database:drop --force --env=test
	$(CONSOLE) doctrine:schema:create --env=test
	#$(CONSOLE) doctrine:fixtures:load --no-interaction --env=test

.PHONY: reset-db
reset-db: ## Reset Database
reset-db: vendor
    # Needs database container to be running (run "make start" if needed)
	$(CONSOLE) doctrine:database:drop --force
	$(CONSOLE) doctrine:database:create --if-not-exists --no-interaction
	$(CONSOLE) doctrine:migrations:migrate --allow-no-migration --no-interaction

##
## —— ✅ Test ——
.PHONY: tests
tests: ## Run all tests
tests: reset-test-db unit-tests

.PHONY: unit-tests
unit-tests: ## Run unit tests
unit-tests:
	$(APP) vendor/bin/phpunit tests --testdox

.PHONY: ci-tests
ci-tests:
	$(CONSOLE_CI) doctrine:database:create --env=test $(ANSI_COLOR)
	$(CONSOLE_CI) doctrine:schema:create --env=test $(ANSI_COLOR)
	#$(CONSOLE_CI) doctrine:fixtures:load --no-interaction --env=test $(ANSI_COLOR)
	$(APP_CI) vendor/bin/phpunit tests

## —— ✨ Code Quality ——
.PHONY: lint-yaml
lint-yaml: ## Lints YAML files
	# Need PHP dependencies (run "make composer-install" if needed)
	$(CONSOLE) lint:yaml config

.PHONY: lint-twig
lint-twig:## Lints Twig files
	# Need PHP dependencies (run "make composer-install" if needed)
	$(CONSOLE) lint:twig templates

.PHONY: lint-container
lint-container: ## Lints containers
	# Need PHP dependencies (run "make composer-install" if needed)
	$(CONSOLE) lint:container

.PHONY: phpcs
phpcs: ## PHP_CodeSniffer (https://github.com/squizlabs/PHP_CodeSniffer)
	$(QA) phpcs -p -n --colors --standard=.phpcs.xml src tests --colors

.PHONY: phpmnd
phpmnd: ## Detect magic numbers in your PHP code
	$(QA) phpmnd src tests $(ANSI_COLOR)

.PHONY: phpdd
phpdd: ## Detect deprecations
	$(QA) phpdd src tests $(ANSI_COLOR)

.PHONY: phpstan
phpstan: ## PHP Static Analysis Tool (https://github.com/phpstan/phpstan)
phpstan:
	$(QA) phpstan --memory-limit=-1 analyse $(ANSI_COLOR)

.PHONY: phpinsights
phpinsights: ## PHP Insights (https://phpinsights.com)
	$(QA) phpinsights analyse --no-interaction $(ANSI_COLOR)

.PHONY: phpinsights-fix
phpinsights-fix: ## PHP Insights (https://phpinsights.com)
	$(QA) phpinsights analyse --no-interaction --fix

.PHONY: php-cs-fixer
php-cs-fixer: ## PhpCsFixer (https://cs.symfony.com/)
	$(QA) php-cs-fixer fix --using-cache=no --verbose --diff --dry-run $(ANSI_COLOR)

.PHONY: php-cs-fixer-apply
php-cs-fixer-apply: ## Applies PhpCsFixer fixes
	$(QA) php-cs-fixer fix --using-cache=no --verbose --diff

.PHONY: twigcs
twigcs: ## Twigcs (https://github.com/friendsoftwig/twigcs)
	$(QA) twigcs templates --severity error --display blocking $(ANSI_COLOR)

.PHONY: db-validate-schema
db-validate-schema: ## Validate database schema and ORM mapping
	$(CONSOLE) doctrine:schema:validate $(ANSI_COLOR)

.PHONY: lint-dockerfile
lint-dockerfiles: ## Lints Dockerfile files
	$(DOCKER) run --rm -i -v ./hadolint.yaml:/.config/hadolint.yaml hadolint/hadolint < devops/php/Dockerfile
	$(DOCKER) run --rm -i -v ./hadolint.yaml:/.config/hadolint.yaml hadolint/hadolint < devops/qa/Dockerfile

.PHONY: security-check
security-check:
	-$(APP) composer audit $(ANSI_COLOR)

## —— 🤖 CI/CD ——
.PHONY: ci-build
ci-build: composer-validate-ci vendor-ci vendor/bin/phpunit

.PHONY: ci-security-check
ci-security-check:
	$(APP) composer audit --no-dev --format summary --locked $(ANSI_COLOR)

.PHONY: ci-validate-database
ci-validate-database:
	$(APP) bin/console doctrine:migrations:migrate --env=prod $(ANSI_COLOR)
	$(APP) bin/console doctrine:schema:validate --env=prod $(ANSI_COLOR)

.PHONY: ci-quality-lint
ci-quality-lint:
	$(CONSOLE_CI) lint:yaml config
	$(CONSOLE_CI) lint:container
	$(CONSOLE_CI) lint:twig templates
	make twigcs

.PHONY: ci-quality-php
ci-quality-php: phpdd phpcs phpmnd php-cs-fixer phpstan phpinsights

.PHONY: ci-phpstan
ci-phpstan:
	$(QA) phpstan --memory-limit=-1 analyse $(ANSI_COLOR)

#.PHONY: ci-tests
#ci-tests:
#	$(APP) bin/console doctrine:database:create --env=test $(ANSI_COLOR)
#	$(APP) bin/console doctrine:schema:create --env=test $(ANSI_COLOR)
#	$(APP) bin/console hautelook:fixtures:load --env=test --no-interaction $(ANSI_COLOR)
#	$(APP) vendor/bin/phpunit tests --exclude-group panther

## —— 🛠️ Others ——
.PHONY: help
help: ## List of commands
	@grep -E '(^[a-z0-9A-Z_-]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
