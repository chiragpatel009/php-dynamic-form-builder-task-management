<?php
declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'dynamic_task_form_builder';
const DB_USER = 'root';
const DB_PASS = '';
const MAX_UPLOAD_BYTES = 5242880;
const UPLOAD_DIR = __DIR__ . '/../uploads/';
const UPLOAD_URL = 'uploads/';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}
