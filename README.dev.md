# Developing the Simple Document Portal plugin

### Prerequisites
- PHP and Composer available on the command line
- Local dev site set up with the plugin installed and active.

Install dependencies via Composer:

```powershell
composer install
```

This and `comoser update` will install the dependencies listed in `composer.json` and then go into the test directories and do the same.

At the time of writing, there are no Composer dependencies required for production so the majority of the `vendor` directory does not need to be uploaded to your live site - only `vendor/composer` and `vendor/autoload.php` do.

One way you can make sure you have only what you need is by deleting the `vendor` directory and running:

```powershell
composer dump-autoload -o
```

### Automated tests

As the plugin is almost entirely PHP, so are the tests. Unit, integration, and end-to-end tests are written using [Pest](https://pestphp.com/), with [ChromeDriver](https://github.com/php-webdriver/php-webdriver/wiki/Chrome) and [Laravel Dusk](https://laravel.com/docs/12.x/dusk) for in-browser tests.

See [tests/README.md](tests/README.md) for more information.

### Linting and formatting

[Laravel Pint](https://laravel.com/docs/pint) is set up for linting and formatting the code.

In PhpStorm, you can have it enabled as an inspection and/or run it automatically on save:
1. Enable and configure it in `Settings -> PHP -> Quality Tools -> Laravel Pint`
2. Select it as the external formatter in `Settings -> PHP -> Quality Tools` (the top level page of that section)
3. Enable formatting on save in `Settings -> Tools -> Actions on Save`.

You can also run it from the command line for the whole project:

```powershell
./vendor/bin/pint
```

Or a specific directory:

```powershell
./vendor/bin/pint src
```

### Miscellany

### Avoid committing changes to .env

```powershell
git update-index --skip-worktree .env
```

If you need to temporarily track and commit changes to `.env` such as adding new keys, you can run:

```powershell
git update-index --no-skip-worktree .env
```
