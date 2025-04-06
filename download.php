<?php
require_once 'db.php';

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$document = DB::getDocument($id);

if (!$document) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

header("Content-Type: " . $document['file_type']);
header("Content-Length: " . $document['file_size']);
header("Content-Disposition: attachment; filename=\"" . $document['original_name'] . "\"");

echo $document['file_content'];
exit;