(() => {
  const container = document.getElementById('vri-form-fields');
  const addButton = document.getElementById('vri-add-field');

  function renumberRows() {
    if (!container) return;
    [...container.querySelectorAll('.vri-form-field-row')].forEach((row, index) => {
      const required = row.querySelector('input[type="checkbox"][name^="field_required"]');
      if (required) required.name = 'field_required[' + index + ']';
    });
  }

  addButton?.addEventListener('click', () => {
    if (!container) return;
    const row = document.createElement('div');
    row.className = 'vri-form-field-row';
    row.innerHTML =
      '<span class="vri-drag">↕</span>' +
      '<input name="field_key[]" placeholder="key">' +
      '<select name="field_type[]">' +
        '<option value="text">text</option>' +
        '<option value="textarea">textarea</option>' +
        '<option value="email">email</option>' +
        '<option value="url">url</option>' +
        '<option value="number">number</option>' +
        '<option value="select">select</option>' +
        '<option value="checkbox">checkbox</option>' +
      '</select>' +
      '<input name="field_label_it[]" placeholder="Label IT">' +
      '<input name="field_label_en[]" placeholder="Label EN">' +
      '<input name="field_options[]" placeholder="Opzioni separate da |">' +
      '<label><input type="checkbox" value="1"> required</label>' +
      '<input type="hidden" name="field_locked[]" value="0">' +
      '<button type="button" class="button-link-delete vri-remove-field">Rimuovi</button>';
    container.appendChild(row);
    renumberRows();
  });

  container?.addEventListener('click', event => {
    const remove = event.target.closest('.vri-remove-field');
    if (!remove) return;
    event.preventDefault();
    remove.closest('.vri-form-field-row')?.remove();
    renumberRows();
  });

  container?.closest('form')?.addEventListener('submit', renumberRows);
  renumberRows();
})();