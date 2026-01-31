<?php
// Script to extract text from DOCX (ZIP/XML) without external libraries
$docxFile = __DIR__ . '/Manual_API_Sigma.docx';

if (!file_exists($docxFile)) {
    die("Error: File not found: $docxFile\n");
}

$zip = new ZipArchive;
if ($zip->open($docxFile) === TRUE) {
    if (($index = $zip->locateName('word/document.xml')) !== false) {
        $xmlContent = $zip->getFromIndex($index);
        $xml = new DOMDocument();
        $xml->loadXML($xmlContent, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
        echo strip_tags($xml->saveXML());
    } else {
        echo "Error: 'word/document.xml' not found in the DOCX archive.\n";
    }
    $zip->close();
} else {
    echo "Error: Failed to open DOCX file as ZIP.\n";
}
?>