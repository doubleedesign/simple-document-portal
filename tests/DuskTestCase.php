<?php
namespace Doubleedesign\SimpleDocumentPortal\Tests;

use Laravel\Dusk\Browser;
use Orchestra\Testbench\TestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\{DesiredCapabilities, RemoteWebDriver};

abstract class DuskTestCase extends TestCase {
    protected function setUp(): void {
        parent::setUp();
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver {
        $options = (new ChromeOptions())->addArguments([
            '--disable-gpu',
            '--headless',
            '--window-size=1920,1080',
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    /**
     * Run a browser test with Laravel Dusk's Browser wrapper.
     */
    protected function browse(callable $callback): void {
        $browser = new Browser($this->driver());

        try {
            $callback($browser);
        }
        finally {
            $browser->quit();
        }
    }
}
