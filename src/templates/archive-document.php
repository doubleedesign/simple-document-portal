<?php
use Doubleedesign\SimpleDocumentPortal\TemplateHandler;
use Doubleedesign\Comet\Core\{ResponsivePanels, ResponsivePanel, PreprocessedHTML};

get_header();

do_action('simple_document_portal_archive_template_before_content');

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
        $top_level_file_group = TemplateHandler::create_filegroup_component($folder->term_id);

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
                ['title' => $folder->name, 'classes' => ['entry-content']],
                [new PreprocessedHTML($content)]
            );
        }

        // Create a file group for each subfolder
        $innerComponents = array_map(function($subfolder) use ($folder) {
            $file_group = TemplateHandler::create_filegroup_component($subfolder->term_id);
            if ($file_group === null) {
                $content = <<<HTML
				<div class="file-group-wrapper">
					<h2>$subfolder->name</h2>
					<div class="no-documents-message">
						<p>No documents found in this subfolder.</p>
					</div>
				</div>
				HTML;

                return new PreprocessedHTML($content);
            }

            ob_start();
            $file_group->render();
            $rendered_files = ob_get_clean();

            $content = <<<HTML
				<div class="file-group-wrapper">
					<h2>$subfolder->name</h2>
					$rendered_files
				</div>
			HTML;

            return new PreprocessedHTML($content);
        }, $subfolders);

        // If there are subfolders but no documents in the top level directly,
        // we don't want to show anything for the top level so just pass the subfolders for the inner component
        if ($top_level_file_group === null) {
            return new ResponsivePanel(
                ['title' => $folder->name, 'classes' => ['entry-content']],
                $innerComponents
            );
        }

        // If there are both top level documents and subfolders, pass in both as the inner components
        return new ResponsivePanel(
            ['title' => $folder->name, 'classes' => ['entry-content']],
            [$top_level_file_group, ...$innerComponents]
        );
    }, $folders);

    $component = new ResponsivePanels(
        array(
            'breakpoint' => get_option('options_portal_layout_layout_switch_breakpoint') . 'px' ?? '768px',
            'colorTheme' => get_option('options_portal_layout_portal_colour_theme') ?? 'primary',
        ),
        $panels
    );
    $component->render();
}

do_action('simple_document_portal_archive_template_after_content');
get_footer();
