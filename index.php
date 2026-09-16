<?php
$pageTitle = 'Task Information';
require_once __DIR__ . '/includes/header.php';
$settings = load_settings();
?>
<div class="page-heading"><div><span class="eyebrow">Workspace</span><h1>Task Information</h1><p class="page-subtitle">Capture the work clearly, then keep every task easy to revisit.</p></div><a class="btn btn-primary" href="settings.php"><i class="bi bi-sliders2"></i> Form Settings</a></div>
<form id="task-form" enctype="multipart/form-data" novalidate>
    <input type="hidden" id="database-id" name="database_id" value="">
    <section class="form-section intro-section"><div class="section-heading"><div><span class="section-kicker">Task Information</span><h2>Start with the basics</h2></div><span class="section-index">01</span></div><div class="row g-3"><div class="col-md-4"><label class="form-label" for="task-name">Task Name <span class="required-mark">*</span></label><input class="form-control" id="task-name" name="task_name" required placeholder="e.g. Login functionality issue"></div><div class="col-md-4"><label class="form-label" for="task-id">Task ID</label><input class="form-control" id="task-id" value="Generated on save" disabled></div><div class="col-md-4"><label class="form-label" for="project-product">Project / Product</label><input class="form-control" id="project-product" name="project_product" placeholder="e.g. Customer Portal"></div></div></section>
    <div id="dynamic-sections"></div>
    <div class="form-actions"><button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> <span id="submit-label">Save Task</span></button><button type="button" class="btn btn-light" id="clear-task"><i class="bi bi-arrow-counterclockwise"></i> Clear</button></div>
</form>
<section class="listing-section"><div class="section-heading"><div><span class="section-kicker">History</span><h2>Saved tasks</h2></div><span class="record-count" id="task-count">0 records</span></div><div class="table-responsive"><table id="task-table" class="table task-table"><thead><tr><th>Task ID</th><th>Task Name</th><th>Project / Product</th><th>Created</th><th class="text-end">Actions</th></tr></thead><tbody id="task-list"></tbody></table></div></section>
<div class="modal fade" id="view-modal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content"><div class="modal-header"><div><span class="eyebrow">Read only</span><h2 class="modal-title">Task details</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="view-content"></div></div></div></div>
<script>window.taskSettings = <?= json_encode($settings, JSON_UNESCAPED_SLASHES) ?>;</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
