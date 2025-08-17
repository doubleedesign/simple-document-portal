<?php
use Doubleedesign\Comet\Core\{ResponsivePanels, ResponsivePanel, FileGroup, File, PreprocessedHTML};

function create_filegroup_component($folder_id): ?FileGroup {
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

    return new FileGroup([], $files);
}

get_header(); ?>

	<section class="pseudo-module pseudo-module__simple-document-portal">
		<div class="row">
			<?php
            if (!is_user_logged_in()) {
                wp_login_form();
            }
            else if (!current_user_can('read_documents')) { ?>
				<div class="alert alert--warning">
					<h2><?php echo get_option('options_permission_error_message_heading'); ?></h2>
					<p><?php echo wpautop(get_option('options_permission_error_message_description')); ?></p>
				</div>
			<?php }

            if (current_user_can('read_documents')) {
                // Get all Folder taxonomy terms
                $folders = get_terms([
                    'taxonomy'   => 'folder',
                    'hide_empty' => false,
                    'parent'     => 0,
                ]);

                // Create a panel inner component for each folder
                $panels = array_map(function($folder) {
                    $top_level_file_group = create_filegroup_component($folder->term_id);

                    // Within that, get subfolders
                    $subfolders = get_terms([
                        'taxonomy'   => 'folder',
                        'hide_empty' => false,
                        'parent'     => $folder->term_id,
                    ]);
                    // If there are no files in the top level OR the subfolders, return early with a message
                    if ($top_level_file_group === null && empty($subfolders)) {
                        $content = <<<HTML
						<div class="no-documents-message">
							<p>No documents found in this folder.</p>
						</div>
					HTML;

                        return new ResponsivePanel(
                            ['title' => $folder->name],
                            [new PreprocessedHTML($content)]
                        );
                    }

                    // Create a file group for each subfolder
                    $innerComponents = array_map(function($subfolder) use ($folder) {
                        $file_group = create_filegroup_component($subfolder->term_id);
                        if ($file_group === null) {
                            $content = <<<HTML
							<h2>$subfolder->name</h2>
							<div class="no-documents-message">
								<p>No documents found in this subfolder.</p>
							</div>
						HTML;

                            return new PreprocessedHTML($content);
                        }

                        ob_start();
                        $file_group->render();
                        $rendered_files = ob_get_clean();

                        $content = <<<HTML
						<h2>$subfolder->name</h2>
						$rendered_files
					HTML;

                        return new PreprocessedHTML($content);
                    }, $subfolders);

                    // If there are subfolders but no documents in the top level directly,
                    // we don't want to show anything for the top level so just pass the subfolders for the inner component
                    if ($top_level_file_group === null) {
                        return new ResponsivePanel(
                            ['title' => $folder->name],
                            $innerComponents
                        );
                    }

                    // If there are both top level documents and subfolders, pass in both as the inner components
                    return new ResponsivePanel(
                        ['title' => $folder->name],
                        [$top_level_file_group, ...$innerComponents]
                    );
                }, $folders);

                $component = new ResponsivePanels(
                    ['breakpoint' => get_option('options_portal_layout_layout_switch_breakpoint') . 'px' ?? '768px'],
                    $panels
                );
                $component->render();
            }
?>
		</div>
	</section>

<?php
get_footer();
