<?php
/**
 * Plugin Name:       Simple Document Portal
 * Description:       Simple management of restricted files. NOTE: This plugin requires the ability to create and write to a directory called "documents" that is one level above the web root on your server.
 * Author:            Double-E Design
 * Author URI:        https://www.doubleedesign.com.au
 * Version:           1.0
 * Text Domain:       simple-document-portal
 * Requires plugins:  advanced-custom-fields-pro
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/woocommerce/action-scheduler/action-scheduler.php';

use Doubleedesign\Comet\Core\Config;
use Doubleedesign\SimpleDocumentPortal\PluginEntrypoint;

Config::set_blade_component_paths([
    __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'doubleedesign' . DIRECTORY_SEPARATOR . 'comet-file-group' . DIRECTORY_SEPARATOR . 'src',
    __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'doubleedesign' . DIRECTORY_SEPARATOR . 'comet-responsive-panels' . DIRECTORY_SEPARATOR . 'src',
]);

new PluginEntrypoint();

function activate_simple_document_portal(): void {
    PluginEntrypoint::activate();
}
function deactivate_simple_document_portal(): void {
    PluginEntrypoint::deactivate();
}
function uninstall_simple_document_portal(): void {
    PluginEntrypoint::uninstall();
}
register_activation_hook(__FILE__, 'activate_simple_document_portal');
register_deactivation_hook(__FILE__, 'deactivate_simple_document_portal');
register_uninstall_hook(__FILE__, 'uninstall_simple_document_portal');
