<?php
// admin/search_manual.php

$file = __DIR__ . '/../api_pdf_extracted.txt';
if (!file_exists($file)) {
    die("File not found: $file");
}

$content = file_get_contents($file);
$lines = explode("\n", $content);

$keywords = ['Import', 'import', 'IMPORT', 'Pedido', 'pedido']; // Simple list
$matches = [];

echo "<h3>Searching...</h3>";

foreach ($lines as $i => $line) {
    foreach ($keywords as $k) {
        if (strpos($line, $k) !== false) {
            // Get context (5 lines before and after)
            $start = max(0, $i - 5);
            $end = min(count($lines) - 1, $i + 50); // Generous context

            $snippet = "";
            for ($j = $start; $j <= $end; $j++) {
                $snippet .= htmlspecialchars($lines[$j]) . "<br>";
            }

            $matches[] = "<strong>Line $i ($k):</strong><br>$snippet<hr>";
            break; // Found one keyword in this line, move to next line
        }
    }
}

if (empty($matches)) {
    echo "No matches found.";
} else {
    echo implode("\n", array_slice($matches, 0, 20)); // Limit output
}
?>