Fast Forward GitHub Actions
===========================

``fast-forward/github-actions`` provides a Symfony Console runtime for Fast
Forward reusable GitHub Actions workflows.

The package is intentionally small: workflows install it globally, then call
commands that replace Fast Forward-owned composite action scripts over time.

Installation
------------

.. code-block:: bash

   composer global require fast-forward/github-actions --no-plugins --no-scripts

For local development, install dependencies without running scripts while
workflow synchronization is still being separated:

.. code-block:: bash

   composer install --no-scripts

Initial Commands
----------------

.. code-block:: bash

   fast-forward-actions php:detect-project --github-output
   fast-forward-actions changelog:resolve-merged-version release/v0.1.0 --github-output
   fast-forward-actions summary:write "## Workflow Summary"

The ``.github`` organization repository remains the owner of reusable workflow
YAML. This package owns the command behavior those workflows call.
