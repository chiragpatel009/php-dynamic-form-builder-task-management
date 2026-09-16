<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'Task Builder') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <link href="assets/css/attachments.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
</head>
<body>
<header class="app-header">
    <div class="container-fluid app-shell d-flex align-items-center">
        <a class="brand" href="index.php"><span class="brand-mark">T</span> TaskFlow</a>
    </div>
</header>
<main class="container-fluid app-shell py-4">
