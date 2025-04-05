<?php
require 'db.php';

$id = $_GET['id'] ?? 0;
$doc = DB::getDocument($id);

if (!$doc) {
    header("HTTP/1.0 404 Not Found");
    die("File not found");
}

// Set appropriate headers
header("Content-Type: " . $doc['file_type']);
header("Content-Length: " . $doc['file_size']);
header("Content-Disposition: attachment; filename=\"" . $doc['original_name'] . "\"");

// Output the file content
echo $doc['content'];
exit;
?>