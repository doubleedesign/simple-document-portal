<?php
namespace Doubleedesign\SimpleDocumentPortal;
use Doubleedesign\Comet\Core\{FileGroup, File};
use WP_Query;

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

    /**
     * Utility function for the archive template, to build file group components for a folder
     *
     * @param  $folder_id
     *
     * @return FileGroup|null
     */
    public static function create_filegroup_component($folder_id): ?FileGroup {
        $query = new WP_Query([
            'post_type'      => 'document',
            'tax_query'      => [
                [
                    'taxonomy'         => 'folder',
                    'field'            => 'term_id',
                    'terms'            => $folder_id,
                    'include_children' => false,
                ],
            ],
            'posts_per_page' => -1,
        ]);
        $document_ids = wp_list_pluck($query->posts, 'ID');
        wp_reset_postdata();

        if (empty($document_ids)) {
            return null;
        }

        $files = array_map(
            function($post_id) {
                $attachment_id = get_post_meta($post_id, 'protected_document_file', true);
                $filesize = wp_get_attachment_metadata($attachment_id)['filesize'] ?? '';
                if ($filesize) {
                    $filesize = number_format($filesize / 1024 / 1024, 2) . ' MB';
                }

                return new File([
                    'title'       => get_the_title($post_id),
                    'url'         => wp_get_attachment_url($attachment_id),
                    'description' => get_the_excerpt($attachment_id),
                    'size'        => $filesize,
                    'mimeType'    => get_post_mime_type($attachment_id) ?? '',
                    'uploadDate'  => get_post_datetime($attachment_id)->format('j F, Y'),
                    'context' 	   => 'file-group'
                ]);
            },
            $document_ids
        );

        return new FileGroup(
            ['colorTheme'   => get_option('options_portal_layout_portal_colour_theme') ?? 'dark'],
            $files
        );
    }
}
