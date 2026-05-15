<?php
require_once 'includes/config.php';
require_once 'vendor/autoload.php';

$resumePath = 'C:\\xampp\\htdocs\\resumeai_project\\resumeai\\uploads\\resume_4_1778769978_c5c962de.pdf';

$parser = new \Smalot\PdfParser\Parser();
$pdf    = $parser->parseFile($resumePath);
$text   = $pdf->getText();

echo "Text length: " . strlen($text) . "\n";

if (empty(trim($text))) {
    echo "Text is empty! Triggering fallback.\n";
    $text = "[SYSTEM NOTE...]";
}

$text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
$text = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', ' ', $text);
$text = mb_substr($text, 0, 15000);

$keywords = "Data Scientist, Machine Learning, Python";
$prompt = "You are a professional ATS resume analyzer. Analyze the resume against job role keywords and respond ONLY with valid JSON.\n\nResume text: " . $text . "\n\nJob keywords: " . $keywords . "\n\nReturn JSON with exactly these keys: score (0-100 integer), matched_skills (array of strings), missing_skills (array of strings), suggestions (array of strings, max 5), strength (one of: Weak, Average, Good, Excellent). DO NOT output markdown blocks or any other text.";

$ch = curl_init(GROQ_API_URL);
$payloadData = [
    'model' => GROQ_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => 'You are a professional ATS resume analyzer. Respond ONLY with raw valid JSON without markdown tags.'],
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.1
];
$payload = json_encode($payloadData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . GROQ_API_KEY]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
echo "Groq Response:\n" . $response . "\n";
