# Testing & Tooling

Strong guarantees demand strong tests. The Temporal package includes fixtures, helpers, and recommended workflows to keep your time-travel data honest.

## Package Test Suite

- Uses SQLite in-memory via Orchestra Testbench.
- Bootstraps the `TemporalServiceProvider`, binds fixture models, and registers schema macros.
- Lives under `packages/Temporal/tests`.

Run the suite locally:

```bash
make test TEST_ARGS="--testsuite=Temporal"
```

This command shells into the Docker PHP container, reuses the vendor-bin PHPUnit install, and points to the package-specific configuration.

## Fixture Highlights

| File | Purpose |
| ---- | ------- |
| `tests/Support/Account.php` | Aggregate model using `HasTemporalAttributes`. |
| `tests/Support/AccountBalance.php` | Concrete `TemporalModel` complete with casts, FK, and relationship. |
| `tests/TestCase.php` | Sets up the database schema and service provider, and fakes the `temporal.user_resolver`. |

## Test Coverage Themes

- **Temporal trait** – verifies `current()` / `asOf()` scopes and the `upsertTemporal` atomic lifecycle.
- **Attribute trait** – ensures pending writes, legacy keys, and transformation callbacks behave.
- **Console command** – exercises the `make:temporal` generator end-to-end.
- **Blueprint macros** – asserts column/index registration within migrations.

## Writing Your Own Tests

1. Extend your Testbench `TestCase` from `ImBrett\LaravelTemporal\Tests\TestCase` or copy the setup logic.
2. Fake `config('temporal.user_resolver')` to control attribution assertions.
3. Use the `temporalAttribute()` accessor in tandem with model factories for round-trip tests (set, persist, rehydrate, read).

## Tooling Checklist

- `make stan` – ensures PHPStan passes.
- `make ecs` / `make ecs-fix` – coding standards (Easy Coding Standard).
- `make insights` – run PHP Insights for code style & architecture checks.
- `make phpmd` – catch potential smells with PHPMD.

Integrate these commands into your CI pipelines (see `.github/workflows/test.yml`) for continuous assurance.

When edge cases crop up, head over to the [Playbook & Troubleshooting](playbook.md).
