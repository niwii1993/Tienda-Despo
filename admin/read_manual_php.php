<?php
// admin/read_manual_php.php

$file = __DIR__ . '/../api_pdf_extracted.txt';
$content = file_get_contents($file);

// Convert UTF-16LE to UTF-8
$utf8 = mb_convert_encoding($content, 'UTF-8', 'UTF-16LE');

// Normalize line endings? No, just search.
$pos = mb_stripos($utf8, 'ImportPedidos');

if ($pos !== false) {
    echo "FOUND at pos $pos\n";
    $start = max(0, $pos - 1000);
    $len = 5000; // Get large chunk
    $chunk = mb_substr($utf8, $start, $len);
    echo "CONTEXT:\n" . $chunk . "\n";
} else {
    echo "ImportPedidos NOT found in UTF8 string.\n";
    // Debug: print first 100 chars
    echo "First 100 chars: " . mb_substr($utf8, 0, 100) . "\n";
}
?>