<?php
namespace Doubleedesign\SimpleDocumentPortal\Tests\Unit;
use Mockery;
use Spies\MockObject;
use function Spies\{mock_object_of};

/**
 * Class used to create mocks of WP_Query for unit tests.
 * Minimal implementation to test this plugin's usage of WP_Query.
 */
class WP_Query_Mock {

    public static function create(): MockObject {
        $mock = Mockery::mock('WP_Query');
        $mock->allows('get')->withAnyArgs();
        $mock->allows('set')->withAnyArgs();

        return mock_object_of($mock);
    }
}
