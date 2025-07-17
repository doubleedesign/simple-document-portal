# Automated tests for Simple Document Portal

## Prerequisites

For all testing types, you will first need to install project dev dependencies using Composer:

```powershell
composer install
```

Setup requirements for specific test types are described below.

## Setup

### Custom Pest runner file
A custom Pest runner file is included in `.tests/pestrunner` because the E2E tests have their own Pest instance and configuration (to prevent its dependencies being loaded for other tests, because that causes conflicts such as Laravel helpers having the same names as WordPress functions and being loaded first). All it does is determine whether this is an E2E test (defined by using the `phpunit.xml` config file from the
`./tests/e2e` directory) and pipes the command to the correct Pest binary. For all other tests, it uses the default Pest binary from the project root. PhpStorm users should specify this file as the `Path to Pest executable` in `Settings -> PHP -> Test Frameworks -> Pest`.

PhpStorm Run configurations are included for convenience, including a Pest template that tells it to:
- find environment variables in `./tests/env.dusk.local` and `/.env`
- run `StartTest.php` first when running all integration or E2E tests.

Alternatively, you can run tests from the command line directly - you will just need to specify the environment variables another way.

## Unit tests

TBC

## Integration tests

The integration tests should be run in a fresh, minimal WordPress installation with a minimal theme and no other plugins active than what is required by the plugin. This ensures we are testing the _integration_, as opposed to an end-to-end test that would account for a client's entire setup.[^1]

These use a range of methods to check results of the plugin's operations in a more "raw" way that is not impacted by template output or other front-end code, such as querying the WordPress REST API, querying the database, running WP-CLI commands, and running the actual functions (like a unit test structure but the real thing). These are generally faster than browser tests.

[^1]: That said, you _can_ also run these as part of your end-to-end testing suite on a full site if you want to ensure other plugin and theme code do not interfere with the plugin's functionality in unintended ways - just keep in mind that a test failure in this case may not indicate a problem with the Simple Document Portal plugin and the developer is not able to provide support for such cases.

### Prerequisites

- Local WordPress installation with:
	- the Simple Document Portal plugin installed and active
	- site URL matching the one in `./tests/env.dusk.local` (or update that file)
	- test user accounts as per the `./herdpress.json` file in the project root
	- `.env` file in the project root containing an application password for the `rest-api-test-account` user, using key `REST_API_TEST_APPLICATION_PASSWORD=`
	- ability for the REST API test user to access all the required endpoints in a local environment
- WP-CLI installed and available in your PATH.

## End-to-end (browser) tests

These use a real browser to test the plugin's functionality in a minimal WordPress installation as a typical user would.

### Structure

The seemingly redundant `./tests/E2E/tests/E2E` structure is due to Pest expecting test files to be in a `tests/` directory, with test types within that. The `./tests/E2E` directory has its own Pest instance and configuration designed to ensure that the Laravel libraries used for browser testing are not loaded for other types of tests, so we have to treat `./tests/E2E` as the Pest project root for those tests (as opposed to the overall project root used for the other tests).

The custom test case class, `DuskTestCase`, is in `src` so we can use PSR-4 autoloading for it. Pest tests themselves should not be namespaced, so that's...that's kinda what's going on with the structure there.

### Prerequisites

- Local WordPress installation with:
	- the Simple Document Portal plugin installed and active
	- site URL matching the one in `./tests/env.dusk.local` (or update that file)
	- test user accounts as per the `./herdpress.json` file in the project root
- Chrome and ChromeDriver installed and available in your PATH (for browser tests).

See the [Appendices](#appendices) section below for more information on setting up your environment and dev site.

## Running the tests

As mentioned above, there are PhpStorm Run configurations included for convenience, but you can also run them from the command line directly. Ensure for E2E tests you are using the `./tests/E2E/phpunit.xml` config file and the custom Pest runner file `.tests/pestrunner` (instead of `./vendor/bin/pest`).

Before running the E2E tests, start ChromeDriver in a separate terminal window:

```powershell
chromedriver --port=9515
```

## Appendices
<details>
<summary>Local dev server</summary>

You can use any local dev server setup you like. Optionally if you are on Windows, you can use my "HerdPress" script to quickly set up and clean up dev sites on your local machine using Laravel Herd. This will also take care of setting up the REST API test user's application password as described above.

Set it up as per the [HerdPress Gist](https://gist.github.com/doubleedesign/f61f25ef8096c30eb8ae4117d76cb185) and then run:

```powershell
herdpress docportal
```
</details>

<details>
<summary>ChromeDriver</summary>

In Windows, you can install ChromeDriver using [Chocolatey](https://community.chocolatey.org/):

```powershell
choco install chromedriver
```
</details>

<details>
	<summary>Installing WP-CLI on Windows</summary>

Download it:
```powershell
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
```

Check it:
```powershell
php wp-cli.phar --info
```

Create a batch script called `wp.bat` to alias the `wp` command, and put it in your Herd `bin` folder or another location your PATH is aware of:
```
@echo off
php "C:\Users\{YOUR_USERNAME}\wp-cli.phar" %*
```

Check that it works:
```powershell
wp --info
```
</details>

<details>
<summary>.env files</summary>

The `./tests/env.dusk.local` file is intended to contain persistent, non-sensitive environment variables for the tests, such as the site URL.

Sensitive credentials should be stored in the `.env` file in the project root, with only keys committed to Git - not sensitive values.
</details>
