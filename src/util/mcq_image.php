<?php
// src/util/mcq_image.php

define('MCQ_IMG_ROOT',     dirname(__DIR__, 2) . '/assets/uploads/mcqs/');
define('MCQ_IMG_WEB_BASE', 'assets/uploads/mcqs/');
define('MCQ_IMG_MAX_BYTES', 2 * 1024 * 1024); // 2MB
define('MCQ_IMG_ALLOWED',  ['image/jpeg', 'image/png', 'image/webp']);

function upload_mcq_image(array $file): string|false
{
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MCQ_IMG_MAX_BYTES) return false;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, MCQ_IMG_ALLOWED, true)) return false;

    $ext = match ($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => null,
    };
    if ($ext === null) return false;

    $subdir = date('Y/m') . '/';
    $dir    = MCQ_IMG_ROOT . $subdir;

    if (!is_dir($dir) && !mkdir($dir, 0755, true)) return false;

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest     = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    return MCQ_IMG_WEB_BASE . $subdir . $filename;
}

function delete_mcq_image(string $path): void
{
    if ($path === '') return;

    $base = realpath(MCQ_IMG_ROOT);
    $full = realpath(dirname(__DIR__, 2) . '/' . $path);

    if (!$full || !$base) return;
    if (!str_starts_with($full, $base)) return;
    if (is_file($full)) unlink($full);
}