# Simple Document Portal

A WordPress plugin to provide access to documents to logged-in users, with a simple interface for uploading and managing them powered by Advanced Custom Fields (ACF) Pro.

## Features

// TODO

## Prerequisites

### Documents directory

By default, WordPress stores uploaded files in the `wp-content/uploads` directory, and files are accessible to anyone with the direct URL to the file no matter how much protection you put in place on the front-end of your site.

If you want your documents to be accessible only to logged-in users, which this plugin assumes you want to do, there are two ways to do this:

1. Put the documents in a directory outside the webroot, and use PHP code to serve the files to logged-in users. This what the plugin attempts to do by default.
2. Put the documents in a special directory within `wp-content/uploads`, and use server configuration to restrict access to that directory. Using such a folder is what the plugin will do if it cannot create or access a directory outside the webroot, or you configure it to do so in the settings - but **you must do the server configuration yourself**.
	- For Apache servers you can use a `.htaccess` file.
	- For Nginx servers you need to add to the server configuration file, and may need to contact your hosting provider to do this for you (e.g., if you are on managed hosting).

> [!CAUTION]
> The developer of this plugin does not provide support for server configuration and cannot guarantee the security of your documents under any circumstances. If you need to ensure protection of sensitive documents, please consult with your hosting provider and/or a security professional.

## Installation

// TODO

## Development

### Prerequisites
- PHP and Composer available on the command line
- Local dev site set up with the plugin installed and active.

Install dependencies via Composer:

```powershell
composer install
```

At the time of writing, there are no Composer dependencies required for production so the majority of the `vendor` directory does not need to be uploaded to your live site - only `vendor/composer` and `vendor/autoload.php` do.

One way you can make sure you have only what you need is by deleting the `vendor` directory and running:

```powershell
composer dump-autoload -o
```

### Automated tests

As the plugin is almost entirely PHP, so are the tests. Unit, integration, and end-to-end tests are written using [Pest](https://pestphp.com/), using a [WebDriver](https://www.selenium.dev/documentation/webdriver/) integration for in-browser tests.

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
