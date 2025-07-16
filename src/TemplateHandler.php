<?php
namespace Doubleedesign\SimpleDocumentPortal;

class TemplateHandler {
    public function __construct() {
        add_filter('template_include', [$this, 'load_document_templates']);
    }

    public function load_document_templates($template) {
        // Is folder taxonomy page?
        if (is_tax('folder')) {
            return plugin_dir_path(__FILE__) . 'templates/taxonomy-folder.php';
        }
        if (is_post_type_archive('document')) {
            return plugin_dir_path(__FILE__) . 'templates/archive-document.php';
        }
        if (is_singular('document')) {
            return plugin_dir_path(__FILE__) . 'templates/single-document.php';
        }

        return $template;
    }
}
