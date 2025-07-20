<?php
use Doubleedesign\SimpleDocumentPortal\UserPermissions;

test('Roles with read_documents capability', function() {
    $capability = 'read_documents';
    $roles_with_permission = UserPermissions::get_roles_with_permission($capability);

    // \Symfony\Component\VarDumper\VarDumper::dump($roles_with_permission);
    expect($roles_with_permission)->toBeArray();
});
