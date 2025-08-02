# Automated tests for Simple Document Portal

## Prerequisites

- PHP and Composer installed and available on the command line
- Dependencies installed in the project via Composer (`composer install` in the plugin root directory).

- <details>
  <summary>Additional prerequisites for integration tests</summary>

  	- WP-CLI installed and available in your PATH
	- Local WordPress installation with:
		- the Simple Document Portal plugin installed and active
		- site URL matching the one in `phpunit.xml` (or update that file)
		- test user accounts as per the `./herdpress.json` file in the project root
		- `.env` file in the project root containing an application password for the `rest-api-test-account` user, using key `REST_API_TEST_APPLICATION_PASSWORD=`
		- ability for the REST API test user to access all the required endpoints in a local environment
  </details>

- <details>
  <summary>Additional prerequisites for E2E tests</summary>

	- Chrome and ChromeDriver installed and available in your PATH
	- Local WordPress installation with:
		- the Simple Document Portal plugin installed and active
		- site URL matching the one in `phpunit.xml` (or update that file)
		- test user accounts as per the `./herdpress.json` file in the project root
  </details>

See the [Appendices](#appendices) section below for more information on setting up your environment and dev site.

## About the setup

Each test type has its own directory under `./tests/` with its own Pest instance and configuration, so as far as Pest is concerned each test type is a separate project. This is to ensure that dependencies from each are not loaded for the others (e.g., Laravel utilities used for browser testing cause conflicts with the integration tests that load WordPress core functions with the same names as Laravel helpers). A side effect of this is a doubled-up directory structure, such as
`./tests/Integration/tests/Integration`, just because of where Pest expects to find things.

> **Tip**
> If using PhpStorm, you'll want to ensure it picks up the test directories as separate Composer projects so it can run tests correctly from the context menus and gutters without prior configuration for specific files and tests. You can do this in `Settings -> PHP`, clicking on the `Composer files` tab and ensuring all `composer.json` files are listed there. Then under
`Settings -> PHP -> Test Frameworks`, it should automatically detect the three different Pest instances and allow you to set the default configuration file (or add them if not automatically detected).

The root Composer configuration is set up to run the install/update commands in each of these directories, so you do not need to run them separately.

Non-sensitive environment variables specific to a test type are set in their respective `phpunit.xml` files. Sensitive credentials should be stored in the project root `.env` file.

## Test types

### Unit tests

// TODO: More info here

A number of WordPress global functions are mocked using BrainMonkey, with some custom patching on top (using Patchwork directly) for additional functionality. See `tests/Unit/tests/Pest.php` for details.

Central mock classes have been created for the `WP_Query` class and `$wpdb` global using Mockery and Spies, to ensure consistency as well as simplify repeated usage.

#### Things to note

<details>
<summary>Asserting on spies</summary>

At the time of writing, [Spies expectation syntax](https://github.com/sirbrillig/spies?tab=readme-ov-file#objects) for object method spies doesn't work with Pest as a standalone assertion. It can be used to give debugging information if a test is failing, but you ultimately also need either a standard Pest assertion such as:
```php
expect($spy->was_called_with('post__not_in', [17, 30, 27]))->toBeTrue();
```
or a PHPUnit assertion such as:
```php
$this->assertTrue($spy->was_called_with('post__not_in', [17, 30, 27]));
```
</details>

### Integration tests

These use a range of methods to check results of the plugin's operations in a more "raw" way that is not impacted by template output or other front-end code, such as querying the WordPress REST API, querying the database, running WP-CLI commands, and running the actual functions (like a unit test structure but the real thing). These are generally faster than browser tests.

When writing tests, it can be helpful to tell PhpStorm where your dev site's WordPress installation is so it can find the WordPress core files and functions for autocompletion, type hinting, "go to definition", etc. You can do this in:
- `Settings -> PHP -> Frameworks`, then the `WordPress` section
- `Settings -> PHP ->`, then the `Include path` tab.

### End-to-end (browser) tests

These use a real browser to test the plugin's functionality in a minimal WordPress installation as a typical user would. They utilise [ChromeDriver](https://github.com/php-webdriver/php-webdriver/wiki/Chrome) and [Laravel Dusk](https://laravel.com/docs/12.x/dusk) to do this. These tests are generally slower than integration tests, as they require a browser to be launched and actual webpages loaded (even if running in "headless" mode where you don't actually see this happening).

> **Note**
> The custom test case class, `DuskTestCase`, is in `src` so we can use PSR-4 autoloading for it. Pest tests themselves should not be namespaced, so that's...that's what's going on there.

## Running the tests

The integration and end-to-end tests should be run in a fresh, minimal WordPress installation with a minimal theme and no other plugins active than what is required by the plugin. This ensures no interference from other code. (That said, if they are all passing in that setup, you _could_ also run them in a full client site to help narrow down the cause of a problem or unexpected behaviour).

PhpStorm Run configurations for all test type plus settings for the default Pest template are included for convenience. These configure:
- finding environment variables in the project root `.env` file, which is where you should store sensitive credentials (such as the REST API test user's application password)
- running `StartTest.php` first when running all integration or E2E tests.

Alternatively, you can run tests from the command line directly - you will just need to specify the required root environment variables another way.

For example, the below command will navigate to the Unit tests directory, run the tests, and navigate back to the project root:

```powershell
cd tests\Unit && vendor\bin\pest && cd ..\..
```

Before running the E2E tests, start ChromeDriver in a separate terminal window:

```powershell
chromedriver --port=9515
```

## Coverage reporting

> **Note**
> Ensure you have Xdebug installed and enabled to generate code coverage reports.

The `Unit` and `Integration` test directory "projects" are configured to generate HTML coverage reports by default during normal test runs (the configuration is set up in their respective `phpunit.xml` files). The reports will be generated in the `coverage-html` directory in each folder.

However, the PhpStorm "run with coverage" feature (which is theoretically the same thing but also loads the coverage data directly into the IDE) doesn't work with the subdirectory structure. For this reason, Pest is also installed at the root of the project and there is a root `phpunit.xml` and `Pest.php` which are only used in this scenario. This means there is some duplication of configuration to ensure the required environment variables are available when running tests this way.

Reports generated when using "run with coverage" will be located in the `tests/phpstorm-coverage-html` directory, but are basically the same as the ones generated automatically thanks to the `phpunit.xml` config running coverage every time. "Run with coverage" is useful for loading data into the IDE.

> **Warning**
> "Run with coverage" data will only show the coverage for the test suite just run, i.e., Unit _or_ Integration. A workaround for this is to generate the combined coverage report as per the below, and then manually load the generated `clover.xml` file into PhpStorm to see the combined coverage data in the IDE.

### Combined coverage for unit and integration tests

For this plugin, some things make more sense as integration tests than unit tests, so a coverage report of one or the other does not give the complete picture. Fortunately what we can do is generate a combined coverage report.

In each `phpunit.xml`, the unit and integration test suites are configured to save coverage data in `.cov` format as well as HTML, and place these files in the `phpcov` folder. From there, we can use the `phpcov` command to generate a combined report. A PhpStorm Run configuration to run both test suites (`Unit + Integration coverage`) and then generate the report is included in the project; and the merge script can be run standalone as well in multiple ways:

1. Run the `Merge coverage reports` PhpStorm Run configuration (this just runs the `merge-coverage.ps1` script in the root of the project and mainly exists for the combined Run configuration)
2. Run the PowerShell script `merge-coverage.ps1` in the root of the project directly
```powershell
./merge-coverage.ps1
```
3. Use the Composer script that the PowerShell script calls:
```powershell
composer merge-coverage
```
4. Use the terminal command directly
```powershell
phpcov merge --clover directory tests/phpcov/source/ --html tests/phpcov/coverage-html
```

If you use option 1 or 2, the coverage report will automatically open in your browser. Otherwise, you can find it in the `tests/phpcov/coverage-html` directory.

To load the combined coverage data into PhpStorm:
1. Go to the Coverage tool window
2. Close any open coverage reports
3. Click "Import a report collected in CI from disk"
4. Select the `clover.xml` file in the `tests/phpcov` directory.

## Appendices

### Local dev environment

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
<summary>Environment variables</summary>

For portability, non-sensitive and persistent environment variables for the tests, such as the site URL, are stored in the `phpunit.xml` files for each test type. These values are committed to Git.

Sensitive credentials should be stored in the `.env` file in the project root, with only keys committed to Git - not sensitive values.
</details>

### Troubleshooting

<details>
<summary>PhpStorm "cannot find interpreter" error when running with coverage</summary>
PhpStorm's Pest integration doesn't understand the subdirectory structure in this context (no idea why it does for regular test runs, but I digress) so it doesn't load the configuration we'd normally expect it to. For this reason, Pest is a dev dependency in the root `composer.json` file as well, and there are minimal `phpunit.xml` and `Pest.php` files in `./tests/` to be used in this context.
</details>

<details>
<summary>Unable to create test case for test file at [file path], namespace P\; ...</summary>
This error occurs because your test is not in a location that Pest expects to find tests, so it's misinterpreting what your file is (note: Pest test files themselves should not be namespaced). They should be in `tests/TestType` directories, where in our case `TestType` is one of `Unit`, `Integration`, or `E2E`. With the subfolder structure used for this project, that means a double-up e.g., `tests/Unit/tests/Unit/TestFile.php`.
</details>

<details>
	<summary>Other Pest or PHPUnit errors</summary>

Because this project has multiple Composer projects within it that each contain a Pest installation - including one in the root - keep an eye on version mismatches when updating dependencies. For example if you end up with a different version of Pest or PHPUnit in the root than in one of the test directories, you may get inconsistent behaviour between normal test runs and "run with coverage" in PhpStorm.

And in general, Pest requires specific PHPUnit version ranges, which can mean that the latest version of Pest is not compatible with the latest version of PHPUnit.

</details>

<details>
<summary>"Cannot load Xdebug - it was already loaded" when running tests with PhpStorm</summary>
Go to `Settings -> PHP` and next to the interpreter you are using, click the `...` button to see its details. If an Xdebug version is listed in the top section (i.e., is auto-detected), ensure the "Debugger extension" field is empty. 
</details>

<details>
<summary>Other PhpStorm issues</summary>

Some general places to look and things to do if you have issues with PhpStorm:
- Ensure the test directories are set up as separate Composer projects in `Settings -> PHP -> Composer files`, but the root `composer.json` is listed as the "main" one (so you should have four in total)
- Ensure PhpStorm is using the same PHP interpreter as your terminal
- In `Settings -> PHP`, in the `PHP Runtime` tab, scroll down and click "sync extensions with interpreter" to ensure that your CLI and PhpStorm are working with the same settings
- In `Settings -> PHP -> Test Frameworks`, ensure the paths to the Pest executables and default configuration files are correct for each test type (there should be a configuration for each test type and one for the root; the latter is only used for "run with coverage").
</details>
