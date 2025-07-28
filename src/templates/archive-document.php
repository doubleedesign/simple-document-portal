<?php
use Doubleedesign\Comet\Core\{ResponsivePanels, ResponsivePanel, FileGroup, File, PreprocessedHTML};

get_header();

if (!is_user_logged_in()) {
    wp_login_form();
}

// Get all Folder taxonomy terms
$folders = get_terms([
    'taxonomy'   => 'folder',
    'hide_empty' => false,
]);

$test = new File(['https://docportal.test/documents/Test-file-14.pdf']);
$test->render();

$panels = new ResponsivePanels(['breakpoint' => get_option('options_portal_layout_layout_switch_breakpoint') . 'px' ?? '768px'],
    // Create a panel inner component for each folder
    array_map(
        function($folder) {
            $query = new WP_Query([
                'post_type'      => 'document',
                'tax_query'      => [
                    [
                        'taxonomy' => 'folder',
                        'field'    => 'term_id',
                        'terms'    => $folder->term_id,
                    ],
                ],
                'posts_per_page' => -1,
            ]);
            $document_ids = wp_list_pluck($query->posts, 'ID');
            wp_reset_postdata();

            $files = [];
            if (!empty($document_ids)) {
                $files = array_map(
                    function($post_id) {
                        $attachment_id = get_post_meta($post_id, 'protected_document_file', true);
                        if ($attachment_id) {
                            // convert filesize to MB
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
                            ]);
                        }

                        return null;
                    },
                    $document_ids
                );
            }

            return new ResponsivePanel(
                ['title' => $folder->name],
                $files
                    ? [new FileGroup([], $files)]
                    : [new PreprocessedHTML('No documents found in this folder.')]
            );
        },
        $folders
    )
);
$panels->render();

get_footer();
