Repository Tooling & QA
=======================

Every package in this monorepo inherits the same tooling workflow. Use this guide as your checklist when contributing or cutting a release.

## Install toolchains

The project ships with [`bamarni/composer-bin-plugin`](https://github.com/bamarni/composer-bin-plugin) namespaces for PHPUnit, PHPStan, ECS, Rector, PHP Insights, PHPMD, and more.

```bash
make install
```

Running the command inside the Docker container installs root dependencies plus every vendor-bin toolchain under `_impl/`.

## Running tests

| Command | Purpose |
| ------- | ------- |
| `make test` | Execute the full PHPUnit suite using the shared vendor-bin installation. |
| `make test TEST_ARGS="--testsuite=Temporal"` | Pass additional flags or limit to a package testsuite. |
| `make test-coverage` | Enable Xdebug, print coverage to the console, and generate `build/logs/clover.xml`. |

Each package maintains its own PHPUnit configuration under `packages/<Name>/phpunit.xml.dist`. The root `phpunit.xml.dist` wires them together so `make test` remains the single entry point.

## Static analysis & linting

```bash
make stan        # PHPStan
make ecs         # Easy Coding Standard (dry run)
make ecs-fix     # Auto-fix coding standards
make insights    # PHP Insights quality gates
make phpmd       # PHPMD ruleset
```

These commands run inside Docker for deterministic versions. CI reuses the same scripts (see `.github/workflows/test.yml`).

## Watching tests locally

Point watchers such as `spatie/phpunit-watcher` to the vendor-bin executable:

```bash
_impl/phpunit/vendor/bin/phpunit-watcher --phpunit "php _impl/phpunit/vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist"
```

Use `TEST_ARGS` to focus on a specific package or test case when iterating quickly.

## Folder conventions

- `packages/<Name>/docs/` – package-specific documentation.
- `packages/<Name>/tests/` – package unit and feature tests; extend the shared `tests/TestCase.php` bootstrap when practical.
- `_impl/<tool>/` – isolated composer installations for QA tooling.

Stick to these conventions and the automation (IDE helpers, workflows, release scripts) continues to work without additional configuration.
