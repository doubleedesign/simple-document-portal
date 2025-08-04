<?php
namespace Doubleedesign\SimpleDocumentPortal\Tests\Unit;
use Doubleedesign\SimpleDocumentPortal\UserPermissions;
use Mockery;
use Spies\MockObject;
use function Spies\{mock_object_of};

class WP_Roles_Mock {

    public static function create(): MockObject {
        $mock = Mockery::mock('WP_Roles')->makePartial();
        $mock->shouldIgnoreMissing();
        $mock->allows('add_cap')->withAnyArgs();
        $mock->allows('remove_cap')->withAnyArgs();

        $spyable = mock_object_of($mock)->and_ignore_missing();
        $spyable->roles = self::get_roles();

        return $spyable;
    }

    public static function get_roles(): array {
        $capability_map = UserPermissions::get_capability_map();
        $all_relevant_caps = array_merge(array_keys($capability_map), array_values($capability_map));
        $all_relevant_caps = array_fill_keys($all_relevant_caps, true);
        $no_relevant_caps = array_fill_keys(array_keys($all_relevant_caps), false);

        return array(
            'administrator' => array(
                'name'         => 'Administrator',
                'capabilities' => $all_relevant_caps
            ),
            'editor' => array(
                'name'         => 'Editor',
                'capabilities' => [...$all_relevant_caps,
                    'manage_options'           => false,
                    'manage_documents_options' => false,
                ]
            ),
            'subscriber' => array(
                'name'         => 'Subscriber',
                'capabilities' => $no_relevant_caps
            ),
            'portal_member' => array(
                'name'         => 'Portal Member',
                'capabilities' => [
                    ...$no_relevant_caps,
                    'read_documents'           => true,
                    'read_private_posts'       => true,
                ]
            ),
        );
    }
}
