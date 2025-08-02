<?php
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
namespace Doubleedesign\SimpleDocumentPortal\Tests\Unit;
use Mockery;

/**
 * Classes used to create mocks of $wpdb for unit tests.
 * Minimal implementation to test this plugin's usage of $wpdb.
 * This uses Mockery alone because I couldn't make the intercept/transformation of the query to work with Spies.
 * That's why despite being deliberately similar to Spies syntax-wise, it doesn't actually use it.
 */
class WPDB_Mock {
    /**
     * @noinspection PhpMissingFieldTypeInspection
     *
     * @var Mockery\MockInterface $instance
     */
    public $instance;

    public function __construct() {
        $wpdb = Mockery::mock('WPDB');
        $wpdb->shouldAllowMockingProtectedMethods();
        $wpdb->shouldIgnoreMissing();

        $wpdb->posts = 'wp_posts';
        $wpdb->postmeta = 'wp_postmeta';

        $this->instance = $wpdb;
        global $wpdb;
        $wpdb = $this->instance;
    }

    /**
     * Start a fluent chain by specifying the method to stub
     */
    public function stub($method): WPDB_Mock_Chain {
        return new WPDB_Mock_Chain($this->instance, $method);
    }
}

/**
 * Helper class for fluent chaining
 *
 * @internal
 */
class WPDB_Mock_Chain {
    /**
     * @noinspection PhpMissingFieldTypeInspection
     *
     * @var Mockery\MockInterface $instance
     */
    private $instance;
    private string $method;
    private string $query;

    public function __construct($mock, $method) {
        $this->instance = $mock;
        $this->method = $method;
    }

    public function with_sql($query): static {
        $this->query = $query;

        return $this;
    }

    /**
     * Wrapper/alias to simplify mocking return values for queries
     *
     * @param  $return
     *
     * @return void
     */
    public function will_return($return): void {
        $this->instance->allows($this->method)
            ->with(Mockery::on(function($calledQuery) {
                // Remove extra whitespace and normalise the query for comparison
                $normalizedQuery = preg_replace('/\s+/', ' ', trim($calledQuery));

                return $normalizedQuery === $this->query;
            }))->andReturn($return);
    }
}
