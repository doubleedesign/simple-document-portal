# Automated tests for Simple Document Portal

## Prerequisites

Install project dev dependencies installed via Composer:

```powershell
composer update
```

To run the integration tests, you will need a local WordPress installation with the Simple Document Portal plugin installed and active; ensure the URL matches the one in `./tests/env.dusk.local` (or update that file).

You can use any local dev server setup you like. Optionally you can use my "HerdPress" script to quickly set up and clean up dev sites on your local machine using Laravel Herd.

Set it up as per the [HerdPress Gist](https://gist.github.com/doubleedesign/f61f25ef8096c30eb8ae4117d76cb185) and then run:

```powershell
herdpress docportal
```

To run the browser tests, you will also need Chrome and ChromeDriver installed and available in your PATH. In Windows, you can install ChromeDriver using [Chocolatey](https://community.chocolatey.org/):

```powershell
choco install chromedriver
```

## Unit tests

TBC

## Integration tests

The integration tests should be run in a fresh, minimal WordPress installation with a minimal theme and no other plugins active than what is required by the plugin. This ensures we are testing the _integration_, as opposed to an end-to-end test that would account for a client's entire setup.

### REST API tests

TBC

### Browser tests

Before running these tests, start ChromeDriver in a separate terminal window:

```powershell
chromedriver --port=9515
```

PhpStorm Run configurations are included for convenience, including a Pest template that tells it to find environment variables in `./tests/env.dusk.local`.

Alternatively, you can run tests from the command line directly - you will just need to specify the environment variables another way.
