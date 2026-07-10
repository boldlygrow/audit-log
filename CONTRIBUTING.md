# Contributing Guide

This project does not have formalized or rigid contribution processes. We keep it simple and subscribe to a "see something, say something" philosophy with a "if it's broken, figure out where and fix it". Due to the simple architecture, it's likely that any problems encountered can be fixed within a single method or with a find and replace of a repeated line of code.

Please consider these to be guidelines. If in doubt, please create an issue and tag the [maintainers](README.md#maintainers) to discuss.

## Feature Requests and Ideas

> **Disclaimer:** This is not an official package maintained by any company. Please use at your own risk and create merge requests for any bugs that you encounter.

We do not maintain a roadmap of feature requests, however we invite you to contribute and we will gladly review your merge requests.

## Code Contributions

We have transitioned from issue-first to PR-first development. We will create an issue for any deferred work, however you can start contributing by creating a new `feature/*` or `hotfix/*` branch and create a merge request.

Before assigning your PR to a maintainer, please review the pipeline CI job outputs for any errors and fix anything that appears.

All merge requests can be assigned to one or all of the maintainers at your discretion. It is helpful to add a comment with any context that the maintainer/reviewer should know or be on the look out for.

### Testing

The package uses [Pest](https://pestphp.com/) (on top of PHPUnit) with [Orchestra Testbench](https://github.com/orchestral/testbench) to boot a lightweight Laravel container for feature tests. The suite lives in the `tests/` directory and covers the logger, actor resolution, response formatting, database persistence, and the Eloquent model.

Install the dependencies and run the suite:

```bash
composer install
composer test
# or: vendor/bin/pest
```

Tests run against an in-memory SQLite database, so no external services are required. Please add or update tests for any behavior you change, and make sure the suite is green before assigning your merge request.

### Static Analysis

The package is analyzed with [Larastan](https://github.com/larastan/larastan) (PHPStan) at the level configured in `phpstan.neon`.

```bash
vendor/bin/phpstan analyse
```

### Dependencies

This package intentionally depends only on the individual `illuminate/*` components it uses (`config`, `database`, `log`, `support`) rather than the full `laravel/framework`. When adding functionality, prefer a specific `illuminate/*` module over pulling in the framework, and add new `require` entries with the same multi-version constraint span as the existing ones.

### Laravel Test Application

You can create a new Laravel application for a specific version to perform local testing with. This allows you to easily use Tinkerwell for each
respective Laravel version.

```bash
# Set temporary environment variable
export SDK_LARAVEL_VERSION=10
cd ~/Code
# Create new Laravel projects
composer create-project laravel/laravel:^${SDK_LARAVEL_VERSION}.0 laravel${SDK_LARAVEL_VERSION}-pkg-test
# Create sylinks in directory
mkdir -p laravel${SDK_LARAVEL_VERSION}-pkg-test/packages/boldlygrow
ln -s ~/Code/audit-log ~/Code/laravel${SDK_LARAVEL_VERSION}-pkg-test/packages/boldlygrow/audit-log
# Custom repository location configuration
cd ~/Code/laravel${SDK_LARAVEL_VERSION}-pkg-test
sed -i '.bak' -e 's/seeders\/"/&,\n            "BoldlyGrow\\\\AuditLog\\\\": "packages\/boldlygrow\/audit-log\/src"/g' composer.json
composer config repositories.audit-log '{"type": "path", "url": "packages/boldlygrow/audit-log"}' --file composer.json
composer require boldlygrow/audit-log:dev-main
php artisan vendor:publish --tag=audit-log
# Unset temporary environment variable
unset SDK_LARAVEL_VERSION
```

You can link this package into an existing Laravel application using the following commands.

```bash
cd ~/Code/my-project-name

mkdir -p packages/boldlygrow
ln -s ~/Code/audit-log packages/boldlygrow/audit-log
sed -i '.bak' -e 's/seeders\/"/&,\n            "BoldlyGrow\\\\AuditLog\\\\": "packages\/boldlygrow\/audit-log\/src"/g' composer.json
composer config repositories.audit-log '{"type": "path", "url": "packages/boldlygrow/audit-log"}' --file composer.json
composer require boldlygrow/audit-log:dev-main
php artisan vendor:publish --tag=audit-log
```

## Custom Application Configuration

### Configuring Your Application with Working Copies of Packages

When you run `composer install`, you will get the latest copy of the packages from the GitHub and GitLab repositories. However, you won't be able to see real-time changes if you change any code in the packages.

You can mitigate this problem by creating a local symlink (with resolved namespaces) for the package inside of your application that you're using for development and testing. By symlinking the packages into the newly created `packages` directory, you'll be able to preview and test your work without doing any Git commits (bad practice).

```bash
# Pre-Requisite (you should already have this)
# You can use any directory you want (if not using ~/Code)
cd ~/Code
git clone https://github.com/boldlygrow/audit-log.git
```

```bash
cd ~/Code/{my-laravel-app}
mkdir -p packages/boldlygrow
cd packages/boldlygrow
ln -s ~/Code/audit-log audit-log
```

### Application Composer

Update the `composer.json` file in your testing application (not the package) to add the package to the `autoload.psr-4` array (append the array, don't replace anything).

```json
# ~/Code/{my-laravel-app}/composer.json

"autoload": {
    "psr-4": {
        "App\\": "app/",
        "BoldlyGrow\\AuditLog\\": "packages/boldlygrow/audit-log/src",
    }
},
```

### Configure Local Composer Repository

Credit: https://laravel-news.com/developing-laravel-packages-with-local-composer-dependencies

```bash
cd ~/Code/{my-laravel-app}

composer config repositories.audit-log '{"type": "path", "url": "packages/boldlygrow/audit-log"}' --file composer.json

composer require boldlygrow/audit-log:dev-main

# Package operations: 1 install, 0 updates, 0 removals
#  - Installing boldlygrow/audit-log (dev-main): Symlinking from packages/boldlygrow/audit-log
```

### Validation and Config Copy

```bash
php artisan vendor:publish --tag=audit-log

# Copied File [/Users/jmartin/Code/audit-log/src/Config/audit-log.php] To [/config/audit-log.php]
# Publishing complete.
```

### Caching Problems

If you run into any classes or files that are renamed and are throwing `Not Found` errors, you may need to use the `composer dump-autoload` command.
