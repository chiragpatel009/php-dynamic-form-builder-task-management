<?php
$pageTitle = 'Form Settings';
require_once __DIR__ . '/includes/header.php';
$settings = load_settings();
?>
<div class="page-heading">
    <div><span class="eyebrow">Configuration</span>
        <h1>Task Form Settings</h1>
        <p class="page-subtitle">Shape the form once. Every new task will follow the same clear structure.</p>
    </div>
    <div class="button-row"><button type="button" class="btn btn-outline-primary" id="add-detail"><i class="bi bi-plus-lg"></i> Add Detail Section</button><button type="button" class="btn btn-primary" id="add-review"><i class="bi bi-plus-lg"></i> Add Review Section</button></div>
</div>
<form id="settings-form">
    <div id="section-builder" class="builder-grid"></div>
    <div class="form-actions sticky-actions"><a class="btn btn-light" href="index.php"><i class="bi bi-arrow-left"></i> Back to Tasks</a><button type="submit" class="btn btn-primary"><i class="bi bi-save2"></i> Save Settings</button></div>
</form>
<script>
    window.initialSettings = <?= json_encode($settings, JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>