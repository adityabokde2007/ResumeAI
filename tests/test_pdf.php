<?php
require_once 'includes/config.php';
require_once 'vendor/autoload.php';

$db = getDB();
$resumes = [
    ['file_path' => 'C:\\xampp\\htdocs\\resumeai_project\\resumeai\\uploads\\resume_4_1778769978_c5c962de.pdf']
];

foreach ($resumes as $resume) {
    echo "Testing file: " . $resume['file_path'] . "\n";


try {
    $parser = new \Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($resume['file_path']);
    $text = $pdf->getText();
    echo "--- RAW TEXT LENGTH: " . strlen($text) . " ---\n";
    echo substr($text, 0, 500) . "\n\n";
    
    // Test cleaning
    $cleaned = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    $cleaned = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', ' ', $cleaned);
    echo "--- CLEANED TEXT LENGTH: " . strlen($cleaned) . " ---\n";
    echo substr($cleaned, 0, 500) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
}
