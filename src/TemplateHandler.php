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
        $depDir = '/wp-content/plugins/simple-document-portal/vendor/doubleedesign/comet-components-launchpad/src';
        $responsivePanelsDir = '/wp-content/plugins/simple-document-portal/vendor/doubleedesign/comet-responsive-panels/src';
        $fileGroupDir = '/wp-content/plugins/simple-document-portal/vendor/doubleedesign/comet-file-group/src';

        // Only load Comet Components (and thus its Vue assets) on the templates we're expecting to use them
        global $template;
        if ($template === plugin_dir_path(__FILE__) . 'templates/archive-document.php') {
            wp_enqueue_style('comet-global', $depDir . '/components/global.css', [], '0.0.3');
            wp_enqueue_style('comet-file-group', $fileGroupDir . '/components/FileGroup/file-group.css', [], '0.0.3');
            wp_enqueue_style('comet-responsive-panels', $responsivePanelsDir . '/components/ResponsivePanels/responsive-panels.css', [], '0.0.3');
            wp_enqueue_script('comet-responsive-panels', $responsivePanelsDir . '/components/ResponsivePanels/responsive-panels.js', [], '0.0.3', true);
        }
    }

    /**
     * Add type=module and custom attributes to script tags
     *
     * @param  $tag
     * @param  $handle
     * @param  $src
     *
     * @return mixed|string
     */
    public function script_type_module($tag, $handle, $src): mixed {
        if (str_starts_with($handle, 'comet-')) {
            $rootDir = '/wp-content/plugins/simple-document-portal/vendor/doubleedesign/comet-responsive-panels';
            $src = esc_url($src);
            $tag = "<script type=\"module\" data-base-path=\"$rootDir\" src=\"" . $src . "\" id=\"$handle\"></script>";
        }

        return $tag;
    }
}
