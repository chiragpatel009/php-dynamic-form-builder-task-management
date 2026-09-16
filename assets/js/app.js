(() => {
    const api = (action, options = {}) => $.ajax({ url: `api/api.php?action=${action}`, ...options });
    const escapeHtml = (value) => $('<div>').text(value ?? '').html();
    const uid = (prefix) => `${prefix}-${Date.now()}-${Math.random().toString(16).slice(2, 7)}`;
    const prettyType = { text: 'Text Box', textarea: 'Text Area', radio: 'Radio Button', date: 'Date', attachment: 'Attachment' };

    function fieldTemplate(field, sectionId) {
        const options = (field.options || ['Low', 'Medium', 'High']).map(escapeHtml).join('\n');
        const extensions = (field.extensions || ['jpg', 'jpeg', 'png', 'pdf']).join(', ');
        return `<div class="field-builder" data-field-id="${escapeHtml(field.id)}">
            <div class="field-drag"><i class="bi bi-grip-vertical"></i></div>
            <div class="field-main"><div class="field-topline"><span class="field-type-badge"><i class="bi bi-${field.type === 'attachment' ? 'paperclip' : field.type === 'date' ? 'calendar3' : field.type === 'textarea' ? 'textarea-t' : field.type === 'radio' ? 'record-circle' : 'type'}"></i>${prettyType[field.type] || 'Text Box'}</span><button type="button" class="icon-button remove-field" title="Remove field"><i class="bi bi-trash3"></i></button></div>
            <div class="row g-2"><div class="col-md-5"><select class="form-select field-type"><option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option><option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option><option value="radio" ${field.type === 'radio' ? 'selected' : ''}>Radio</option><option value="date" ${field.type === 'date' ? 'selected' : ''}>Date</option><option value="attachment" ${field.type === 'attachment' ? 'selected' : ''}>Attachment</option></select></div><div class="col-md-7"><input class="form-control field-label" value="${escapeHtml(field.label)}" placeholder="Field label"></div><div class="col-12"><input class="form-control field-id" value="${escapeHtml(field.id)}" placeholder="Unique field ID"></div></div>
            ${field.type === 'radio' ? `<label class="small-label">Options, one per line</label><textarea class="form-control field-options" rows="2">${options}</textarea>` : ''}
            ${field.type === 'attachment' ? `<label class="small-label">Allowed extensions</label><input class="form-control field-extensions" value="${escapeHtml(extensions)}" placeholder="jpg, png, pdf">` : ''}
            <div class="field-controls"><label class="switch-label"><input type="checkbox" class="field-active" ${field.active !== false ? 'checked' : ''}><span class="switch"></span> Active</label><label class="switch-label"><input type="checkbox" class="field-required" ${field.required ? 'checked' : ''}><span class="switch"></span> Required</label></div></div></div>`;
    }

    function sectionTemplate(section) {
        return `<article class="builder-card" data-section-id="${escapeHtml(section.id)}"><div class="builder-card-header"><div><span class="section-type-pill">${section.type === 'review' ? 'Review section' : 'Detail section'}</span><h2 class="builder-title">${escapeHtml(section.title)}</h2></div><button type="button" class="icon-button remove-section" title="Remove section"><i class="bi bi-trash3"></i></button></div><div class="row g-2 mb-3"><div class="col-md-7"><label class="small-label">Section title</label><input class="form-control section-title" value="${escapeHtml(section.title)}"></div><div class="col-md-5"><label class="small-label">Section ID</label><input class="form-control section-id" value="${escapeHtml(section.id)}"></div></div><div class="section-controls"><label class="switch-label"><input type="checkbox" class="section-active" ${section.active !== false ? 'checked' : ''}><span class="switch"></span> Active</label><select class="form-select section-type"><option value="detail" ${section.type === 'detail' ? 'selected' : ''}>Detail</option><option value="review" ${section.type === 'review' ? 'selected' : ''}>Review</option></select><button type="button" class="btn btn-sm btn-outline-primary add-field"><i class="bi bi-plus"></i> Add Field</button></div><div class="fields-list">${(section.fields || []).map((field) => fieldTemplate(field, section.id)).join('')}</div></article>`;
    }

    function readSettings() {
        return { version: 1, sections: [...document.querySelectorAll('.builder-card')].map((card) => ({ id: card.querySelector('.section-id').value.trim(), title: card.querySelector('.section-title').value.trim(), type: card.querySelector('.section-type').value, active: card.querySelector('.section-active').checked, fields: [...card.querySelectorAll('.field-builder')].map((field) => ({ id: field.querySelector('.field-id').value.trim(), type: field.querySelector('.field-type')?.value || field.dataset.type || 'text', label: field.querySelector('.field-label').value.trim(), active: field.querySelector('.field-active').checked, required: field.querySelector('.field-required').checked, options: field.querySelector('.field-options')?.value.split('\n').map((v) => v.trim()).filter(Boolean), extensions: field.querySelector('.field-extensions')?.value.split(',').map((v) => v.trim()).filter(Boolean) })) })) };
    }

    function renderSettings() {
        if (!$('#section-builder').length) return;
        $('#section-builder').html((window.initialSettings.sections || []).map(sectionTemplate).join(''));
        $('.field-builder').each(function () { $(this).data('type', $(this).find('.field-type').val()); });
    }

    function renderHome() {
        if (!$('#dynamic-sections').length) return;
        $('#dynamic-sections').html((window.taskSettings.sections || []).filter((section) => section.active).map((section, index) => `<section class="form-section dynamic-section" data-section="${escapeHtml(section.id)}"><div class="section-heading"><div><span class="section-kicker">${section.type === 'review' ? 'Review' : 'Detail'} ${String(index + 2).padStart(2, '0')}</span><h2>${escapeHtml(section.title)}</h2></div><span class="section-index">${String(index + 2).padStart(2, '0')}</span></div><div class="row g-3">${section.fields.filter((field) => field.active).map((field) => renderField(field, section.id)).join('')}</div></section>`).join(''));
    }

    function renderField(field, sectionId, values = {}) {
        const name = `value_${field.id}`;
        let input = `<input class="form-control dynamic-value" name="${name}" data-field-id="${escapeHtml(field.id)}" value="${escapeHtml(values[field.id] || '')}" ${field.required ? 'required' : ''} placeholder="${escapeHtml(field.label)}">`;
        if (field.type === 'textarea') input = `<textarea class="form-control dynamic-value" name="${name}" data-field-id="${escapeHtml(field.id)}" rows="4" ${field.required ? 'required' : ''} placeholder="${escapeHtml(field.label)}">${escapeHtml(values[field.id] || '')}</textarea>`;
        if (field.type === 'date') input = `<input type="date" class="form-control dynamic-value" name="${name}" data-field-id="${escapeHtml(field.id)}" value="${escapeHtml(values[field.id] || '')}" ${field.required ? 'required' : ''}>`;
        if (field.type === 'radio') input = `<div class="radio-row">${(field.options || []).map((option) => `<label><input type="radio" name="${name}" class="dynamic-value" data-field-id="${escapeHtml(field.id)}" value="${escapeHtml(option)}" ${values[field.id] === option ? 'checked' : ''} ${field.required ? 'required' : ''}> <span>${escapeHtml(option)}</span></label>`).join('')}</div>`;
        if (field.type === 'attachment') input = `<input type="file" class="form-control dynamic-file" name="${field.id}" data-field-id="${escapeHtml(field.id)}" accept="${(field.extensions || []).map((extension) => '.' + extension).join(',')}"><div class="attachment-hint">Allowed: ${(field.extensions || []).join(', ')} · Max 5 MB</div>`;
        return `<div class="col-lg-4 col-md-6"><label class="form-label">${escapeHtml(field.label)} ${field.required ? '<span class="required-mark">*</span>' : ''}</label>${input}<div class="invalid-feedback">This field is required.</div></div>`;
    }

    function attachmentMarkup(attachments = []) {
        if (!attachments.length) return '<div class="empty-attachments">No attachments.</div>';
        return attachments.map((attachment) => {
            const image = /\.(jpg|jpeg|png|gif|webp)$/i.test(attachment.original_name);
            return `<div class="attachment-item">${image ? `<img src="${escapeHtml(attachment.file_path)}" alt="${escapeHtml(attachment.original_name)}">` : '<i class="bi bi-file-earmark-pdf file-icon"></i>'}<div><strong>${escapeHtml(attachment.original_name)}</strong>${image ? '' : `<a href="${escapeHtml(attachment.file_path)}" target="_blank" rel="noopener">Open file</a>`}</div></div>`;
        }).join('');
    }

    function collectValues() { const values = {}; $('.dynamic-value').each(function () { if (this.type === 'radio' && !this.checked) return; values[$(this).data('field-id')] = $(this).val(); }); return values; }
    function notify(message, isError = false) { const alert = $(`<div class="toast-message ${isError ? 'toast-error' : ''}">${escapeHtml(message)}</div>`).appendTo('body'); setTimeout(() => alert.addClass('show'), 20); setTimeout(() => alert.remove(), 3200); }

    function loadTasks() { if (!$('#task-list').length) return; api('list_tasks').done((response) => { const tasks = response.tasks || []; const table = $('#task-table'); if ($.fn.dataTable.isDataTable('#task-table')) table.DataTable().destroy(); $('#task-count').text(`${tasks.length} record${tasks.length === 1 ? '' : 's'}`); $('#task-list').html(tasks.length ? tasks.map((task) => `<tr><td><span class="task-number">#${task.task_id}</span></td><td><strong>${escapeHtml(task.task_name)}</strong></td><td>${escapeHtml(task.project_product || '—')}</td><td>${escapeHtml(task.created_at)}</td><td class="text-end"><button class="table-action view-task" data-id="${task.id}" title="View"><i class="bi bi-eye"></i></button><button class="table-action edit-task" data-id="${task.id}" title="Edit"><i class="bi bi-pencil"></i></button><button class="table-action delete-task" data-id="${task.id}" title="Delete"><i class="bi bi-trash3"></i></button></td></tr>`).join('') : '<tr><td colspan="5" class="empty-state">No tasks saved yet.</td></tr>'); if (tasks.length) table.DataTable({ pageLength: 10, lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]], order: [[3, 'desc']], columnDefs: [{ targets: 4, orderable: false, searchable: false }], language: { search: '', searchPlaceholder: 'Search tasks...', lengthMenu: 'Show _MENU_', info: 'Showing _START_ to _END_ of _TOTAL_ tasks', infoEmpty: 'No tasks found', emptyTable: 'No tasks saved yet.' } }); }); }

    function taskDetails(id, callback) { api('get_task', { data: { id } }).done(callback).fail((xhr) => notify(xhr.responseJSON?.message || 'Could not load task.', true)); }

    $(document).on('click', '#add-detail, #add-review', function () { const type = this.id === 'add-review' ? 'review' : 'detail'; $('#section-builder').append(sectionTemplate({ id: uid('section'), title: type === 'review' ? 'New Review Section' : 'New Detail Section', type, active: true, fields: [{ id: uid('field'), type: 'text', label: 'New Field', active: true, required: false }] })); });
    $(document).on('click', '.remove-section', function () { $(this).closest('.builder-card').remove(); });
    $(document).on('click', '.add-field', function () { const card = $(this).closest('.builder-card'); card.find('.fields-list').append(fieldTemplate({ id: uid('field'), type: 'text', label: 'New Field', active: true, required: false }, card.data('section-id'))); });
    $(document).on('click', '.remove-field', function () { $(this).closest('.field-builder').remove(); });
    $(document).on('change', '.field-type', function () { const field = $(this).closest('.field-builder'); const current = { id: field.find('.field-id').val(), type: this.value, label: field.find('.field-label').val(), active: field.find('.field-active').prop('checked'), required: field.find('.field-required').prop('checked'), options: field.find('.field-options').val()?.split('\n'), extensions: field.find('.field-extensions').val()?.split(',') }; field.replaceWith(fieldTemplate(current)); });
    $(document).on('input', '.section-title', function () { $(this).closest('.builder-card').find('.builder-title').text(this.value || 'Untitled section'); });
    $(document).on('submit', '#settings-form', function (event) { event.preventDefault(); api('save_settings', { method: 'POST', contentType: 'application/json', data: JSON.stringify(readSettings()) }).done((response) => { notify(response.message); setTimeout(() => location.href = 'index.php', 800); }).fail((xhr) => notify(xhr.responseJSON?.message || 'Could not save settings.', true)); });
    $(document).on('submit', '#task-form', function (event) { event.preventDefault(); const form = this; if (!form.checkValidity()) { form.classList.add('was-validated'); return; } const data = new FormData(form); data.append('values', JSON.stringify(collectValues())); const action = $('#database-id').val() ? 'update_task' : 'save_task'; api(action, { method: 'POST', data, processData: false, contentType: false }).done((response) => { notify(response.message); $('#task-id').val(response.task_id || 'Saved'); clearForm(); loadTasks(); }).fail((xhr) => notify(xhr.responseJSON?.message || 'Could not save task.', true)); });
    function clearForm() { $('#task-form')[0].reset(); $('#database-id').val(''); $('#task-id').val('Generated on save'); $('#submit-label').text('Save Task'); $('#task-form').removeClass('was-validated'); renderHome(); }
    $(document).on('click', '#clear-task', clearForm);
    $(document).on('click', '.delete-task', function () { if (!confirm('Are you sure you want to delete this task?')) return; api('delete_task', { method: 'POST', data: { id: $(this).data('id') } }).done((response) => { notify(response.message); loadTasks(); }); });
    $(document).on('click', '.view-task, .edit-task', function () { const id = $(this).data('id'); const edit = $(this).hasClass('edit-task'); taskDetails(id, (response) => { const values = {}; (response.values || []).forEach((value) => { values[value.field_id] = value.field_value; }); if (edit) { $('#database-id').val(response.task.id); $('#task-name').val(response.task.task_name); $('#project-product').val(response.task.project_product); $('#task-id').val(response.task.task_id); $('#submit-label').text('Update Task'); $('#dynamic-sections').html(window.taskSettings.sections.filter((section) => section.active).map((section, index) => `<section class="form-section dynamic-section"><div class="section-heading"><div><span class="section-kicker">${section.type === 'review' ? 'Review' : 'Detail'} ${String(index + 2).padStart(2, '0')}</span><h2>${escapeHtml(section.title)}</h2></div><span class="section-index">${String(index + 2).padStart(2, '0')}</span></div><div class="row g-3">${section.fields.filter((field) => field.active).map((field) => renderField(field, section.id, values)).join('')}</div></section>`).join('')); $('#dynamic-sections').append(`<section class="form-section attachment-section"><div class="section-heading"><div><span class="section-kicker">Files</span><h2>Existing attachments</h2></div></div><div class="attachment-list">${attachmentMarkup(response.attachments)}</div></section>`); window.scrollTo({ top: 0, behavior: 'smooth' }); } else { const body = `<div class="detail-summary"><div><span>Task ID</span><strong>#${response.task.task_id}</strong></div><div><span>Task Name</span><strong>${escapeHtml(response.task.task_name)}</strong></div><div><span>Project / Product</span><strong>${escapeHtml(response.task.project_product || '—')}</strong></div></div>${window.taskSettings.sections.filter((section) => section.active).map((section) => `<div class="view-section"><h3>${escapeHtml(section.title)}</h3>${section.fields.filter((field) => field.active).map((field) => `<div class="view-value"><span>${escapeHtml(field.label)}</span><strong>${escapeHtml(values[field.id] || '—')}</strong></div>`).join('')}</div>`).join('')}<div class="view-section"><h3>Attachments</h3><div class="attachment-list">${attachmentMarkup(response.attachments)}</div></div>`; $('#view-content').html(body); bootstrap.Modal.getOrCreateInstance('#view-modal').show(); } }); });
    function savedValues(response) {
        const values = {};
        (response.values || []).forEach((value) => { values[value.field_id] = value.field_value; });
        return values;
    }

    function renderHistoricalTask(response) {
        const values = savedValues(response);
        const snapshot = response.snapshot || window.taskSettings;
        const sections = (snapshot.sections || []).filter((section) => section.active !== false);
        const content = `<div class="detail-summary"><div><span>Task ID</span><strong>#${response.task.task_id}</strong></div><div><span>Task Name</span><strong>${escapeHtml(response.task.task_name)}</strong></div><div><span>Project / Product</span><strong>${escapeHtml(response.task.project_product || '—')}</strong></div></div>${sections.map((section) => `<div class="view-section"><h3>${escapeHtml(section.title)}</h3>${(section.fields || []).filter((field) => field.active !== false).map((field) => `<div class="view-value"><span>${escapeHtml(field.label)}</span><strong>${escapeHtml(values[field.id] || '—')}</strong></div>`).join('')}</div>`).join('')}<div class="view-section"><h3>Attachments</h3><div class="attachment-list">${attachmentMarkup(response.attachments)}</div></div>`;
        $('#view-content').html(content);
        bootstrap.Modal.getOrCreateInstance('#view-modal').show();
    }

    function renderEditableTask(response) {
        const values = savedValues(response);
        $('#database-id').val(response.task.id);
        $('#task-name').val(response.task.task_name);
        $('#project-product').val(response.task.project_product);
        $('#task-id').val(response.task.task_id);
        $('#submit-label').text('Update Task');
        $('#dynamic-sections').html(window.taskSettings.sections.filter((section) => section.active).map((section, index) => `<section class="form-section dynamic-section"><div class="section-heading"><div><span class="section-kicker">${section.type === 'review' ? 'Review' : 'Detail'} ${String(index + 2).padStart(2, '0')}</span><h2>${escapeHtml(section.title)}</h2></div><span class="section-index">${String(index + 2).padStart(2, '0')}</span></div><div class="row g-3">${section.fields.filter((field) => field.active).map((field) => renderField(field, section.id, values)).join('')}</div></section>`).join(''));
        $('#dynamic-sections').append(`<section class="form-section attachment-section"><div class="section-heading"><div><span class="section-kicker">Files</span><h2>Existing attachments</h2></div></div><div class="attachment-list">${attachmentMarkup(response.attachments)}</div></section>`);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.view-task, .edit-task');
        if (!button) return;
        event.preventDefault();
        event.stopPropagation();
        taskDetails(button.dataset.id, (response) => {
            if (button.classList.contains('view-task')) {
                renderHistoricalTask(response);
                return;
            }
            if (!response.editable) {
                const details = response.edit_issues.map((issue) => `<li>${escapeHtml(issue)}</li>`).join('');
                notify(`${response.message} ${response.edit_issues.join(' ')}`, true);
                $('#view-content').html(`<div class="compatibility-warning"><i class="bi bi-exclamation-triangle"></i><h3>Editing is unavailable</h3><p>${escapeHtml(response.message)}</p><ul>${details}</ul><p>View remains available. Restore the original form controls or create a new task using the current settings.</p></div>`);
                bootstrap.Modal.getOrCreateInstance('#view-modal').show();
                return;
            }
            renderEditableTask(response);
        }, true);
    }, true);

    renderSettings(); renderHome(); loadTasks();
})();
