<?php
namespace Doubleedesign\SimpleDocumentPortal;

class TemplateHandler {
    public function __construct() {
        add_filter('template_include', [$this, 'load_document_templates']);
        add_action('wp_enqueue_scripts', [$this, 'load_frontend_assets']);
        add_filter('script_loader_tag', [$this, 'script_type_module'], 10, 3);
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

    public function load_frontend_assets(): void {
        $rootDir = plugin_dir_url(__FILE__) . '..';
        wp_enqueue_style('comet-responsive-panels', $rootDir . '/vendor/doubleedesign/comet-responsive-panels/dist/src/components/global.css', [], '0.0.2');
        wp_enqueue_style('comet-responsive-panels', $rootDir . '/vendor/doubleedesign/comet-responsive-panels/dist/src/components/ResponsivePanels/responsive-panels.css', [], '0.0.2');
        wp_enqueue_script('comet-responsive-panels', $rootDir . '/vendor/doubleedesign/comet-responsive-panels/dist/src/components/ResponsivePanels/responsive-panels.js', [], '0.0.2', true);
    }

    /**
     * Add type=module to script tags
     *
     * @param  $tag
     * @param  $handle
     * @param  $src
     *
     * @return mixed|string
     */
    public function script_type_module($tag, $handle, $src): mixed {
        if (str_starts_with($handle, 'comet-')) {
            $tag = '<script type="module" src="' . esc_url($src) . '" id="' . $handle . '" ></script>';
        }

        return $tag;
    }
}
