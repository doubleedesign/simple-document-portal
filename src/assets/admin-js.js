document.addEventListener('DOMContentLoaded', function () {
});

/**
 * For improved keyboard navigation,
 * when "Add Subfolder" button is clicked, focus on the prefix input field.
 */
jQuery(function ($) {
	acf.addAction('append', function ($el) {
		const repeaterField = $el.closest('[data-key="field_subfolders"]');
		if (repeaterField.length) {
			const prefixField = $el.find('[data-name="prefix"] input');
			if (prefixField.length) {
				// Small delay to ensure field is fully initialized
				setTimeout(() => {
					prefixField.focus();
				}, 50);
			}
		}
	});
});
