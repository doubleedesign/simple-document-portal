<?php
namespace Doubleedesign\SimpleDocumentPortal;

use WP_Term;

/**
 * Class for managing UI-specific customisations for the admin area that do not affect data.
 * This includes customisations to the document post list table, quick edit, publish box text.
 * Note: Customisations that affect data (like ACF fields, save actions, etc.) are located in the DocumentsAdmin and FoldersAdmin classes.
 */
class AdminUI {

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets'], 30);

        add_filter('plugin_action_links', [$this, 'add_plugin_action_links'], 10, 4);

        add_filter('manage_document_posts_columns', [$this, 'add_document_admin_columns']);
        add_filter('manage_edit-document_sortable_columns', [$this, 'add_document_admin_sortable_columns'], 10, 1);
		add_action('manage_document_posts_custom_column', [$this, 'populate_document_admin_columns'], 10, 2);

		add_filter('post_row_actions', [$this, 'customise_row_actions'], 10, 2);
		add_filter('quick_edit_enabled_for_post_type', [$this, 'disable_quick_edit_for_documents'], 10, 2);
		add_filter('disable_months_dropdown', [$this, 'disable_admin_list_filter'], 10, 2);
		add_filter('disable_categories_dropdown', [$this, 'disable_admin_list_filter'], 10, 2);
		add_action('restrict_manage_posts', [$this, 'add_folder_filter_to_document_list'], 10, 1);
		add_filter('display_post_states', [$this, 'do_not_add_private_status_text_to_documents'], 10, 2);

        add_filter('gettext', [$this, 'update_publish_box_text'], 10, 3);
        add_filter('esc_html', [$this, 'hack_publish_box_visibility_text'], 20, 1);

        add_filter('acf/prepare_field', [$this, 'prepare_fields_that_should_have_instructions_as_tooltips'], 10, 1);
        add_filter('acf/get_field_label', [$this, 'render_some_acf_field_instructions_as_tooltips'], 10, 3);
    }

    public function enqueue_assets(): void {
		wp_enqueue_style(
			'simple-document-portal-admin',
			plugin_dir_url(__FILE__) . 'assets/admin-style.css',
			[],
			filemtime(plugin_dir_path(__FILE__) . 'assets/admin-style.css')
		);

		wp_enqueue_script(
			'simple-document-portal-admin',
			plugin_dir_url(__FILE__) . 'assets/admin-js.js',
			[],
			filemtime(plugin_dir_path(__FILE__) . 'assets/admin-js.js'),
            true
        );
    }

    public function add_plugin_action_links($actions, $plugin_file, $plugin_data, $context): array {
        if ($plugin_file === 'simple-document-portal/index.php') {
            $actions['settings'] = sprintf(
                '<a href="%s">%s</a>',
                esc_url(admin_url('/edit.php?post_type=document&page=settings')),
                __('Settings', 'simple-document-portal')
            );
        }

        return $actions;
    }

    public function add_document_admin_columns($columns): array {
        $columns['document_file'] = __('File', 'simple-document-portal');
		$columns['document_folder'] = __('Folder', 'simple-document-portal');
		$columns['date'] = __('Date added', 'simple-document-portal');

		// Put the file column after the title column
		return array_reduce(array_keys($columns), function($carry, $key) use ($columns) {
			$carry[$key] = $columns[$key];
			if($key === 'title') {
				$carry['document_file'] = __('File', 'simple-document-portal');
				$carry['document_folder'] = __('Folder', 'simple-document-portal');
				$carry['date'] = __('Date added', 'simple-document-portal');
			}

			return $carry;
		}, []);
	}

	public function add_document_admin_sortable_columns($columns): array {
		$columns['document_folder'] = __('Folder', 'simple-document-portal');

		return $columns;
	}

	public function populate_document_admin_columns($column, $post_id): void {
		if($column === 'document_file') {
			$file_id = get_post_meta($post_id, 'protected_document_file', true);
			if($file_id) {
				$file_url = wp_get_attachment_url($file_id);
				if($file_url) {
					echo '<a href="' . esc_url($file_url) . '" target="_blank">' . esc_html(basename($file_url)) . '</a>';
				}
				else {
					echo '-';
				}
			}
			else {
				echo '-';
			}
		}
		if($column === 'document_folder') {
			$folders = wp_get_post_terms($post_id, 'folder');
			if(!empty($folders) && !is_wp_error($folders)) {
				echo implode(', ', array_map(function(WP_Term $folder) {
					$url = add_query_arg('folder', $folder->slug, admin_url('edit.php?post_type=document'));

					return sprintf('<a href="%s">%s</a>', esc_url($url), esc_html($folder->name));
				}, $folders));
			}
			else {
				echo '-';
			}
		}
	}

	public function customise_row_actions($actions, $post): array {
		if($post->post_type === 'document') {
			unset($actions['view']);
		}

		return $actions;
	}

	/**
	 * In the absence of the ability to remove the slug, date, password, and private checkbox fields from quick edit,
	 * let's just disable quick edit for documents entirely.
	 *
	 * @param  $enabled
	 * @param  $post_type
	 *
	 * @return bool
	 */
	public function disable_quick_edit_for_documents($enabled, $post_type): bool {
		if($post_type === 'document') {
			return false;
		}

		return $enabled;
	}

	public function disable_admin_list_filter($disabled, $post_type): bool {
		if($post_type === 'document') {
			return true; // Disable the filter for whichever apply_filters() this was called on, e.g. 'disable_months_dropdown'
		}

		return $disabled;
	}

	/**
	 * Add folder taxonomy filter to the admin document list table.
	 */
	public function add_folder_filter_to_document_list($post_type): void {
		if($post_type === 'document' && is_object_in_taxonomy($post_type, 'folder')) {
			$fieldLabel = get_taxonomy('folder')->labels->filter_by_item;
			$foldersInUse = get_terms(array(
				'taxonomy'   => 'folder',
				'object_ids' => get_posts(array(
					'post_type'   => 'document',
					'post_status' => array('publish', 'private', 'draft', 'pending'),
					'numberposts' => -1,
					'fields'      => 'ids'
				)),
				'meta_key'   => 'prefix',
				'orderby'    => 'meta_value',
				'order'      => 'ASC',
			));

			// Use the taxonomy name as the select name for automatic filtering
			$displayed_folder = $_GET['folder'] ?? ''; // should be the slug
			$options = array_map(function($folder) use ($displayed_folder) {
				$selected = selected($folder->slug, $displayed_folder, false) ? 'selected' : '';

				return sprintf('<option value="%s" %s>%s</option>', esc_attr($folder->slug), $selected, esc_html($folder->name));
			}, $foldersInUse);

			$options = implode('', $options);
			$options = '<option value="">' . __('All folders', 'simple-document-portal') . '</option>' . $options;

			echo <<<OUTPUT
            <label for="filter-by-folder" class="screen-reader-text">$fieldLabel</label>
            <select name="folder" id="filter-by-folder">$options</select>
            OUTPUT;
		}
	}

	/**
	 * Remove the "Private" status text from the document list table (all documents are private so it's superfluous).
	 *
	 * @param  $post_states
	 * @param  $post
	 *
	 * @return array
	 */
	public function do_not_add_private_status_text_to_documents($post_states, $post): array {
		if($post->post_type === 'document') {
			unset($post_states['private']);
		}

		return $post_states;
	}

	public function update_publish_box_text($translated_text, $text, $domain): ?string {
		// Rather than check where we are at the top (which will do it for every string),
		// check after we have ascertained that we're looking at the right piece of text.
		global $current_screen;

		if($text === 'Public' && $current_screen->id === 'document') {
			return $translated_text . '<small>' . __('Can only be used to revert to draft or pending review; published documents will automatically be set to private when saved', 'simple_document_portal') . '</small>';
		}
		if($text === 'Private' && $current_screen->id === 'document') {
			return $translated_text . '<small>' . __('Accessible to logged-in users with the appropriate role or permission', 'simple_document_portal') . '</small>';
		}

		return $translated_text;
	}

	/**
	 * Fix the visibility text in the publish box after update_publish_box_text() has run,
	 * because there's no filter to properly differentiate but one uses esc_html and the other does not, so we just have to use that.
	 *
	 * @param  $text
	 *
	 * @return string
	 */
	public function hack_publish_box_visibility_text($text): string {
		// Rather than check where we are at the top (which will do it for every string),
		// check after we have ascertained that we're looking at the right piece of text.
		global $current_screen;

		if(str_starts_with($text, 'Private') || str_starts_with($text, 'Public') && $current_screen->id === 'document') {
			$html = html_entity_decode($text);
			// If we got this far and it has <small> tags, assume this is the text n the publish box
			$text = preg_replace('/<small>.*?<\/small>/', '', $html);
			$text = trim($text); // Remove any leading/trailing whitespace
		}

        return $text;
    }

    /**
     * ACF does not have a filter to allow us to remove the instructions from the DOM,
     * and I hate hacking such things with display:none or removing from the DOM on the client side with JS.
     * This workaround moves the instructions into a custom field
     * (which we then use in our custom label rendering function to render an icon + tooltip instead of the usual instruction markup).
     *
     * @param  $field
     *
     * @return array
     */
    public function prepare_fields_that_should_have_instructions_as_tooltips($field): array {
        if ($this->should_render_instructions_as_tooltips($field) && $field['instructions']) {
            $field['tooltip'] = $field['instructions'];
            $field['instructions'] = '';
        }

        return $field;
    }

    public function render_some_acf_field_instructions_as_tooltips($label, $field, $context): string {
        if ($this->should_render_instructions_as_tooltips($field) && isset($field['tooltip'])) {
            // Note: Something is stripping tabindex from non-interactive elements like <span> in the admin, so we have to use a <button>
            // type="button" to make it focusable and accessible, without it submitting the form.
            return <<<HTML
				{$label}
					<button type="button" class="acf-js-tooltip" title="{$field['tooltip']}">
						<span class="dashicons dashicons-editor-help"></span>
						<span class="screen-reader-text" role="tooltip">{$field['tooltip']}</span>
					</button>
				</div>
				HTML;
        }

        return $label;
    }

    protected function should_render_instructions_as_tooltips($field): bool {
        return isset($field['parent_repeater']) &&
            ($field['parent_repeater'] === 'field_folders' || $field['parent_repeater'] === 'field_subfolders');
    }
}
