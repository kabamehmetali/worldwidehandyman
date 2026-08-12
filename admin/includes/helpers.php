<?php
/**
 * Admin helpers — image uploads, small utilities.
 */

/**
 * Validate and store an uploaded image.
 *
 * @param array  $file   One entry from $_FILES
 * @param string $subdir Subfolder inside assets/uploads (e.g. 'gallery')
 * @param string $prefix Filename prefix
 * @return array{0: ?string, 1: string} [relative path or null, error message]
 */
function handle_image_upload(array $file, string $subdir, string $prefix = 'img'): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, 'No file uploaded.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload failed (error code ' . (int) $file['error'] . ').'];
    }
    if ($file['size'] > 8 * 1024 * 1024) {
        return [null, 'Image is too large (max 8 MB).'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        return [null, 'Unsupported image type — use JPG, PNG, GIF or WebP.'];
    }

    $subdir = preg_replace('/[^a-z0-9_-]/i', '', $subdir);
    $dir = APP_ROOT . '/assets/uploads/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        return [null, 'Could not create the upload folder.'];
    }

    $name = preg_replace('/[^a-z0-9_-]/i', '', $prefix) . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $extensions[$mime];
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return [null, 'Could not save the uploaded file.'];
    }
    @chmod($dest, 0664);

    return ['assets/uploads/' . $subdir . '/' . $name, ''];
}

/** Delete a previously uploaded file — only ever touches assets/uploads. */
function delete_upload(string $relativePath): void
{
    if (strpos($relativePath, 'assets/uploads/') !== 0 || strpos($relativePath, '..') !== false) {
        return; // seeded assets & anything outside uploads are never deleted
    }
    $full = APP_ROOT . '/' . $relativePath;
    if (is_file($full)) {
        @unlink($full);
    }
}

/** Re-sequence sort_order for a table after up/down moves. */
function move_row(string $table, int $id, string $direction): void
{
    $allowed = ['services', 'gallery', 'testimonials', 'faqs', 'nav_links',
        'seo_locations', 'seo_services'];
    if (!in_array($table, $allowed, true)) {
        return;
    }
    $rows = db()->query("SELECT id FROM {$table} ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $index = array_search($id, array_map('intval', $rows), true);
    if ($index === false) {
        return;
    }
    $swap = $direction === 'up' ? $index - 1 : $index + 1;
    if ($swap < 0 || $swap >= count($rows)) {
        return;
    }
    [$rows[$index], $rows[$swap]] = [$rows[$swap], $rows[$index]];
    $stmt = db()->prepare("UPDATE {$table} SET sort_order = ? WHERE id = ?");
    foreach ($rows as $position => $rowId) {
        $stmt->execute([$position + 1, $rowId]);
    }
}

/** Make a URL-safe slug. */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'page';
}
