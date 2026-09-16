<?php
declare(strict_types=1);

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function request_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '{}', true);
    return is_array($data) ? $data : [];
}

function slug_id(string $value, string $fallback): string
{
    $slug = strtolower(trim((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value), '-'));
    return $slug !== '' ? $slug : $fallback;
}

function default_settings(): array
{
    return [
        'version' => 1,
        'sections' => [
            [
                'id' => 'section-requirement-issue-details',
                'title' => 'Requirement & Issue Details',
                'type' => 'detail',
                'active' => true,
                'fields' => [
                    ['id' => 'field-current-behaviour', 'type' => 'text', 'label' => 'Current Behaviour', 'active' => true, 'required' => false],
                    ['id' => 'field-expected-behaviour', 'type' => 'text', 'label' => 'Expected Behaviour', 'active' => true, 'required' => false],
                    ['id' => 'field-reproduction-steps', 'type' => 'textarea', 'label' => 'Reproduction Steps', 'active' => true, 'required' => false],
                ],
            ],
            [
                'id' => 'section-technical-analysis',
                'title' => 'Technical Analysis & Implementation',
                'type' => 'detail',
                'active' => true,
                'fields' => [
                    ['id' => 'field-root-cause', 'type' => 'text', 'label' => 'Root Cause', 'active' => true, 'required' => false],
                    ['id' => 'field-affected-area', 'type' => 'text', 'label' => 'Affected Area / Modules', 'active' => true, 'required' => false],
                    ['id' => 'field-implementation-details', 'type' => 'textarea', 'label' => 'Implementation Details', 'active' => true, 'required' => false],
                ],
            ],
        ],
    ];
}

function load_settings(): array
{
    $row = db()->query('SELECT settings_json FROM task_form_settings ORDER BY id DESC LIMIT 1')->fetch();
    if (!$row) {
        return default_settings();
    }

    $settings = json_decode($row['settings_json'], true);
    return is_array($settings) && isset($settings['sections']) ? $settings : default_settings();
}

function next_task_id(): int
{
    $value = db()->query('SELECT COALESCE(MAX(task_id), 21170) + 1 FROM tasks')->fetchColumn();
    return (int) $value;
}

function form_compatibility(array $snapshot, array $current): array
{
    $currentSections = [];
    foreach ($current['sections'] ?? [] as $section) {
        $currentSections[$section['id']] = $section;
    }

    $issues = [];
    foreach ($snapshot['sections'] ?? [] as $savedSection) {
        if (empty($savedSection['active'])) continue;
        $sectionId = (string) ($savedSection['id'] ?? '');
        $currentSection = $currentSections[$sectionId] ?? null;
        if (!$currentSection) {
            $issues[] = 'Section "' . ($savedSection['title'] ?? $sectionId) . '" no longer exists.';
            continue;
        }
        if (empty($currentSection['active'])) {
            $issues[] = 'Section "' . ($savedSection['title'] ?? $sectionId) . '" is inactive.';
        }

        $currentFields = [];
        foreach ($currentSection['fields'] ?? [] as $field) {
            $currentFields[$field['id']] = $field;
        }
        foreach ($savedSection['fields'] ?? [] as $savedField) {
            if (empty($savedField['active'])) continue;
            $fieldId = (string) ($savedField['id'] ?? '');
            $currentField = $currentFields[$fieldId] ?? null;
            $fieldName = $savedField['label'] ?? $fieldId;
            if (!$currentField) {
                $issues[] = 'Field "' . $fieldName . '" no longer exists.';
                continue;
            }
            if (empty($currentField['active'])) {
                $issues[] = 'Field "' . $fieldName . '" is inactive.';
            } elseif (($currentField['type'] ?? '') !== ($savedField['type'] ?? '')) {
                $issues[] = 'Field "' . $fieldName . '" changed its field type.';
            }
        }
    }

    return ['editable' => $issues === [], 'issues' => $issues];
}

function find_legacy_task_snapshot(array $values): array
{
    $fieldIds = array_column($values, 'field_id');
    $sectionIds = array_unique(array_column($values, 'section_id'));
    $rows = db()->query('SELECT settings_json FROM task_form_settings ORDER BY id DESC')->fetchAll();
    $bestSnapshot = load_settings();
    $bestScore = -1;

    foreach ($rows as $row) {
        $snapshot = json_decode($row['settings_json'], true);
        if (!is_array($snapshot)) continue;
        $score = 0;
        foreach ($snapshot['sections'] ?? [] as $section) {
            if (in_array($section['id'] ?? '', $sectionIds, true)) $score++;
            foreach ($section['fields'] ?? [] as $field) {
                if (in_array($field['id'] ?? '', $fieldIds, true)) $score++;
            }
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestSnapshot = $snapshot;
        }
    }

    foreach ($bestSnapshot['sections'] ?? [] as $sectionIndex => $section) {
        if (!in_array($section['id'] ?? '', $sectionIds, true)) continue;
        $bestSnapshot['sections'][$sectionIndex]['active'] = true;
        foreach ($section['fields'] ?? [] as $fieldIndex => $field) {
            if (in_array($field['id'] ?? '', $fieldIds, true)) {
                $bestSnapshot['sections'][$sectionIndex]['fields'][$fieldIndex]['active'] = true;
            }
        }
    }

    return $bestSnapshot;
}
