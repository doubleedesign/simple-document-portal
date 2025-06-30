document.addEventListener('DOMContentLoaded', function () {

	// Move folder taxonomy ACF fields to the top of the DOM so keyboard navigation works as expected
	// First, the add term page
	const folderTaxonomyFieldsCreate = document.querySelector('.wp-admin.edit-tags-php #acf-term-fields');
	if(folderTaxonomyFieldsCreate) {
		const parentCreate = folderTaxonomyFieldsCreate.parentNode;
		if (parentCreate) {
			parentCreate.insertBefore(folderTaxonomyFieldsCreate, parentCreate.firstChild);
		}
	}

	// Second, the edit term page
	const folderTaxonomyFieldsEdit = document.querySelector('.wp-admin.term-php #edittag .form-table:has(.acf-field)');
	if(folderTaxonomyFieldsEdit) {
		const parentEdit = folderTaxonomyFieldsEdit.parentNode;
		if (parentEdit) {
			parentEdit.insertBefore(folderTaxonomyFieldsEdit, parentEdit.firstChild);
		}

		// Also fix the markup to match the built-in fields
		const labelCell = folderTaxonomyFieldsEdit.querySelector('td.acf-label');
		if (labelCell) {
			const newLabelCell = document.createElement('th');
			newLabelCell.setAttribute('scope', 'row');
			newLabelCell.className = 'acf-label';
			newLabelCell.innerHTML = labelCell.innerHTML;
			labelCell.parentNode.replaceChild(newLabelCell, labelCell);
		}
	}
});
