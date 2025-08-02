<?php
namespace Doubleedesign\SimpleDocumentPortal\Tests\E2E;

use Laravel\Dusk\Browser;
use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\{DesiredCapabilities, RemoteWebDriver};
use Closure;

class DuskTestCase extends TestCase {
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
    public function browse(Closure $callback): void {
        $browser = new Browser($this->driver());

        try {
            $callback($browser);
        }
        finally {
            $browser->pause(3000);
            $browser->quit();
        }
    }
}
