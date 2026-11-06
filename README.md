Laravel Toolkit Monorepo
========================

Developer-experience helpers for Laravel projects, packaged as a maintained monorepo. Each package ships with its own docs, tests, and release notes while sharing infrastructure, tooling, and CI.

Highlights
----------

- Dedicated packages for temporal data, cached pagination, API query scopes, and attribute-driven listeners.
- Reproducible Docker workflow with `make` targets for install, tests, linting, and QA.
- Extensive documentation per package plus shared contributor guides.
- GitHub Actions pipeline that mirrors the local toolchain (lint on every push, quality gates before merges, package-specific matrices).
- Extension registries (`TemporalComparatorRegistry`, `FilterOperatorManager`, `RelationSortHandlerManager`, `CacheKeyResolver`, `AttributeRegistry`) so consumers can plug in project-specific edge cases without forking.

Packages
--------

| Package | Summary | Docs |
| ------- | ------- | ---- |
| Temporal | Temporal attributes, versioned models, schema macros, and scaffolding. | [`packages/Temporal/docs/index.md`](packages/Temporal/docs/index.md) |
| CachedPaginator | Drop-in cached totals for pagination with per-model tuning. | [`packages/CachedPaginator/docs/index.md`](packages/CachedPaginator/docs/index.md) |
| ApiQuery | Request-driven filtering, sorting, and searching helpers. | [`packages/ApiQuery/docs/index.md`](packages/ApiQuery/docs/index.md) |
| ListenerAttributes | Register Laravel event listeners via PHP attributes. | [`packages/ListenerAttributes/docs/index.md`](packages/ListenerAttributes/docs/index.md) |
| Sluggable | Automatic slug generation and synchronisation for Eloquent models. | [`packages/Sluggable/docs/index.md`](packages/Sluggable/docs/index.md) |

Quick start
-----------

1. **Install dependencies (runs inside Docker)**

   ```bash
   make install
   ```

2. **Run the full test suite or focus on a package**

   ```bash
   make test
   make test TEST_ARGS="--testsuite=Temporal"
   make test-coverage   # requires Xdebug, writes build/logs/clover.xml
   ```

3. **Apply static analysis and coding standards**

   ```bash
   make stan
   make ecs
   make ecs-fix
   make insights
   make phpmd
   ```

Tooling & workflow
------------------

- `_impl/` holds vendor-bin islands (PHPUnit, PHPStan, ECS, Rector, PHP Insights, PHPMD, etc.) managed by `bamarni/composer-bin-plugin`.
- `docker/` defines the PHP container used by the Make targets and CI jobs.
- `.github/workflows/test.yml` mirrors local commands: commits trigger Rector/ECS linting; pushes add phpstan, insights, phpmd, and phpunit; changed packages run through PHP/Laravel compatibility matrices.

Documentation
-------------

- Each package documents installation, usage, internals, recipes, and testing strategy under `packages/<Name>/docs/`.
- Monorepo-wide QA guidance lives in [`docs/testing.md`](docs/testing.md).
- When contributing features or fixes, update the relevant package docs as part of the same change.

Development checklist
---------------------

- Scope your changes to a single package when possible.
- Run `make test TEST_ARGS="--testsuite=<Name>"` plus the lint targets before raising a pull request.
- Add or adjust documentation in `packages/<Name>/docs/` so consumers have up-to-date guidance.
- Tag the monorepo (`vX.Y.Z`) after CI passes and documentation and changelog entries are in place. Pushing that tag splits `imbrett/laravel-api-query` and `imbrett/laravel-listener-attributes` into read-only mirrors for Packagist. Pushes to `main` update `dev-main` on those mirrors.

License
-------

MIT © Brett
