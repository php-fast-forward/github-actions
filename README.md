# Fast Forward GitHub Actions

Symfony Console runtime for Fast Forward reusable GitHub Actions workflows.

This package is intended to move Fast Forward-owned workflow behavior out of
composite-action scripts and into a Composer-installable, testable PHP
application.

## Installation

```bash
composer global require fast-forward/github-actions --no-plugins --no-scripts
```

During early repository work, install local dependencies without running scripts:

```bash
composer install --no-scripts
```

The package depends on `fast-forward/dev-tools`, so reusable workflows can install
this runtime globally even when a consumer repository does not require DevTools
directly. The global install command keeps Composer plugins and scripts disabled
so installing the runtime does not trigger DevTools synchronization.
The DevTools Composer plugin is disabled for this repository, keeping workflow
and agent synchronization out of the first package bootstrap.

## Usage

```bash
fast-forward-actions list
fast-forward-actions php:resolve-version --github-output
fast-forward-actions php:detect-project --github-output
fast-forward-actions changelog:resolve-merged-version release/v0.1.0 --github-output
fast-forward-actions summary:write "## Workflow Summary"
```

This first version intentionally does not add synchronized workflow wrappers.
The organization `.github` repository remains responsible for reusable workflow
YAML while this package grows the command runtime used by those workflows.
