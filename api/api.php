<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$pdo = db();

try {
    if ($action === 'save_settings') {
        $data = request_json();
        $sections = $data['sections'] ?? [];
        if (!is_array($sections)) {
            json_response(['success' => false, 'message' => 'Invalid section configuration.'], 422);
        }

        $seenSections = [];
        foreach ($sections as $sectionIndex => &$section) {
            $title = trim((string) ($section['title'] ?? ''));
            $section['title'] = $title;
            $section['id'] = slug_id((string) ($section['id'] ?? $title), 'section-' . ($sectionIndex + 1));
            $section['type'] = in_array($section['type'] ?? 'detail', ['detail', 'review'], true) ? $section['type'] : 'detail';
            $section['active'] = !empty($section['active']);
            if ($title === '' || isset($seenSections[$section['id']])) {
                json_response(['success' => false, 'message' => 'Every section needs a unique title and ID.'], 422);
            }
            $seenSections[$section['id']] = true;
            $seenFields = [];
            $fields = is_array($section['fields'] ?? null) ? $section['fields'] : [];
            foreach ($fields as $fieldIndex => &$field) {
                $field['label'] = trim((string) ($field['label'] ?? ''));
                $field['id'] = slug_id((string) ($field['id'] ?? $field['label']), $section['id'] . '-field-' . ($fieldIndex + 1));
                $field['type'] = in_array($field['type'] ?? 'text', ['text', 'textarea', 'radio', 'date', 'attachment'], true) ? $field['type'] : 'text';
                $field['active'] = !empty($field['active']);
                $field['required'] = !empty($field['required']);
                if ($field['label'] === '' || isset($seenFields[$field['id']])) {
                    json_response(['success' => false, 'message' => 'Every field needs a unique label and ID.'], 422);
                }
                $seenFields[$field['id']] = true;
                if ($field['type'] === 'radio') {
                    $options = array_values(array_filter(array_map('trim', (array) ($field['options'] ?? []))));
                    if (count($options) < 2) {
                        json_response(['success' => false, 'message' => 'Radio fields need at least two options.'], 422);
                    }
                    $field['options'] = $options;
                }
                if ($field['type'] === 'attachment') {
                    $extensions = array_values(array_filter(array_map(static fn ($value) => strtolower(ltrim(trim((string) $value), '.')), (array) ($field['extensions'] ?? []))));
                    $field['extensions'] = $extensions ?: ['jpg', 'jpeg', 'png', 'pdf'];
                }
            }
            unset($field);
            $section['fields'] = $fields;
        }
        unset($section);

        $stmt = $pdo->prepare('INSERT INTO task_form_settings (settings_json) VALUES (?)');
        $stmt->execute([json_encode(['version' => 1, 'sections' => $sections], JSON_UNESCAPED_SLASHES)]);
        json_response(['success' => true, 'message' => 'Form settings saved successfully.']);
    }

    if ($action === 'list_tasks') {
        $rows = $pdo->query('SELECT id, task_id, task_name, project_product, created_at, updated_at FROM tasks ORDER BY id DESC')->fetchAll();
        json_response(['success' => true, 'tasks' => $rows]);
    }

    if ($action === 'get_task') {
        $id = (int) ($_GET['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT * FROM tasks WHERE id = ?');
        $stmt->execute([$id]);
        $task = $stmt->fetch();
        if (!$task) json_response(['success' => false, 'message' => 'Task not found.'], 404);
        $values = $pdo->prepare('SELECT section_id, field_id, field_type, field_value FROM task_values WHERE task_id = ?');
        $values->execute([$id]);
        $attachments = $pdo->prepare('SELECT * FROM task_attachments WHERE task_id = ? ORDER BY id');
        $attachments->execute([$id]);
        $taskValues = $values->fetchAll();
        $snapshot = $task['form_snapshot'] ? json_decode($task['form_snapshot'], true) : find_legacy_task_snapshot($taskValues);
        $compatibility = form_compatibility($snapshot, load_settings());
        json_response([
            'success' => true,
            'task' => $task,
            'snapshot' => $snapshot,
            'editable' => $compatibility['editable'],
            'edit_issues' => $compatibility['issues'],
            'values' => $taskValues,
            'attachments' => $attachments->fetchAll(),
        ]);
    }

    if ($action === 'save_task' || $action === 'update_task') {
        $isUpdate = $action === 'update_task';
        $taskDatabaseId = (int) ($_POST['database_id'] ?? 0);
        $taskName = trim((string) ($_POST['task_name'] ?? ''));
        $projectProduct = trim((string) ($_POST['project_product'] ?? ''));
        $settings = load_settings();
        $postedValues = json_decode((string) ($_POST['values'] ?? '{}'), true);
        if ($taskName === '' || !is_array($postedValues)) {
            json_response(['success' => false, 'message' => 'Task name and valid form data are required.'], 422);
        }

        if ($isUpdate) {
            $savedTask = $pdo->prepare('SELECT form_snapshot FROM tasks WHERE id = ?');
            $savedTask->execute([$taskDatabaseId]);
            $savedSnapshot = $savedTask->fetchColumn();
            $savedSnapshot = $savedSnapshot ? json_decode($savedSnapshot, true) : $settings;
            $compatibility = form_compatibility($savedSnapshot, $settings);
            if (!$compatibility['editable']) {
                json_response([
                    'success' => false,
                    'code' => 'FORM_CHANGED',
                    'message' => 'This task cannot be edited because its submitted form is no longer compatible with the current form settings.',
                    'issues' => $compatibility['issues'],
                ], 409);
            }
        }

        $fieldMap = [];
        foreach ($settings['sections'] as $section) {
            if (empty($section['active'])) continue;
            foreach ($section['fields'] as $field) {
                if (!empty($field['active'])) $fieldMap[$field['id']] = [$section['id'], $field];
            }
        }
        foreach ($fieldMap as $fieldId => [$sectionId, $field]) {
            if (!empty($field['required']) && trim((string) ($postedValues[$fieldId] ?? '')) === '') {
                json_response(['success' => false, 'message' => $field['label'] . ' is required.'], 422);
            }
            if ($field['type'] === 'radio' && isset($postedValues[$fieldId]) && !in_array($postedValues[$fieldId], $field['options'], true)) {
                json_response(['success' => false, 'message' => 'Invalid option selected for ' . $field['label'] . '.'], 422);
            }
        }

        $pdo->beginTransaction();
        if ($isUpdate) {
            $stmt = $pdo->prepare('UPDATE tasks SET task_name = ?, project_product = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$taskName, $projectProduct, $taskDatabaseId]);
            $taskIdStmt = $pdo->prepare('SELECT task_id FROM tasks WHERE id = ?');
            $taskIdStmt->execute([$taskDatabaseId]);
            $taskIdValue = (int) $taskIdStmt->fetchColumn();
            $pdo->prepare('DELETE FROM task_values WHERE task_id = ?')->execute([$taskDatabaseId]);
        } else {
            $taskIdValue = next_task_id();
            $stmt = $pdo->prepare('INSERT INTO tasks (task_id, task_name, project_product, form_snapshot) VALUES (?, ?, ?, ?)');
            $stmt->execute([$taskIdValue, $taskName, $projectProduct, json_encode($settings, JSON_UNESCAPED_SLASHES)]);
            $taskDatabaseId = (int) $pdo->lastInsertId();
        }

        $valueStmt = $pdo->prepare('INSERT INTO task_values (task_id, section_id, field_id, field_type, field_value) VALUES (?, ?, ?, ?, ?)');
        foreach ($fieldMap as $fieldId => [$sectionId, $field]) {
            $value = $postedValues[$fieldId] ?? null;
            if (is_array($value)) $value = json_encode($value);
            $valueStmt->execute([$taskDatabaseId, $sectionId, $fieldId, $field['type'], (string) $value]);
        }

        foreach ($_FILES as $fieldId => $file) {
            if (!isset($fieldMap[$fieldId]) || $file['error'] === UPLOAD_ERR_NO_FILE) continue;
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > MAX_UPLOAD_BYTES) {
                throw new RuntimeException('Attachment upload failed or exceeds the 5 MB limit.');
            }
            $field = $fieldMap[$fieldId][1];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($field['type'] !== 'attachment' || !in_array($extension, $field['extensions'], true)) {
                throw new RuntimeException('Invalid attachment type for ' . $field['label'] . '.');
            }
            $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
            $destination = UPLOAD_DIR . $storedName;
            if (!move_uploaded_file($file['tmp_name'], $destination)) throw new RuntimeException('Could not store attachment.');
            $attachmentStmt = $pdo->prepare('INSERT INTO task_attachments (task_id, field_id, original_name, stored_name, file_path, mime_type, file_size) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $attachmentStmt->execute([$taskDatabaseId, $fieldId, basename($file['name']), $storedName, UPLOAD_URL . $storedName, (string) mime_content_type($destination), (int) $file['size']]);
        }
        $pdo->commit();
        json_response(['success' => true, 'message' => $isUpdate ? 'Task updated successfully.' : 'Task saved successfully.', 'task_id' => $taskIdValue]);
    }

    if ($action === 'delete_task') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT stored_name FROM task_attachments WHERE task_id = ?');
        $stmt->execute([$id]);
        foreach ($stmt->fetchAll() as $file) if (is_file(UPLOAD_DIR . $file['stored_name'])) unlink(UPLOAD_DIR . $file['stored_name']);
        $pdo->prepare('DELETE FROM tasks WHERE id = ?')->execute([$id]);
        json_response(['success' => true, 'message' => 'Task deleted successfully.']);
    }

    json_response(['success' => false, 'message' => 'Unknown action.'], 404);
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'message' => $error->getMessage()], 500);
}
