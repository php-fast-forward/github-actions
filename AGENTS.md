# AGENTS - Fast Forward GitHub Actions

This repository contains the Composer-installable command runtime used by Fast
Forward reusable GitHub Actions workflows.

## Repository Surfaces

- CLI entrypoint: [`bin/fast-forward-actions`](bin/fast-forward-actions)
- Console wiring: [`src/Console/`](src/Console/)
- Commands: [`src/Command/`](src/Command/)
- GitHub Actions IO helpers: [`src/GitHub/`](src/GitHub/)
- Project detection logic: [`src/Project/`](src/Project/)
- PHP version resolution logic: [`src/Project/`](src/Project/)
- Tests: [`tests/`](tests/)
- Docs: [`docs/`](docs/)
- Release history: [`CHANGELOG.md`](CHANGELOG.md)

## Setup And Local Workflow

- Install dependencies with `composer install --no-scripts` while workflow
  synchronization is still being externalized.
- Use `composer global require fast-forward/github-actions --no-plugins --no-scripts`
  when smoke-testing the runtime as a workflow dependency.
- Run tests with `vendor/bin/phpunit`.
- Validate package metadata with `composer validate --strict`.
- Do not add `.github/workflows` in this initial package unless a task explicitly
  asks for workflow publication.

## Design Notes

- Keep reusable workflow YAML in `php-fast-forward/.github`.
- Keep this package focused on deterministic commands callable from those
  workflows.
- Prefer small services with unit tests over shell fragments embedded in workflow
  YAML.
