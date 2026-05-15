<?php
require_once 'includes/config.php';

$db = getDB();
$stmt = $db->query("SELECT * FROM resumes ORDER BY id DESC LIMIT 1");
$resume = $stmt->fetch_assoc();

if (!$resume) {
    die("No resumes found");
}

echo "Testing file: " . $resume['file_path'] . "\n";

$content = file_get_contents($resume['file_path']);
// Regex to extract text from raw PDF streams
preg_match_all('/stream(.*?)endstream/s', $content, $matches);
$text = '';

foreach ($matches[1] as $stream) {
    // Some streams are zlib compressed
    $decoded = @gzuncompress(trim($stream));
    if ($decoded !== false) {
        $stream = $decoded;
    }
    // Extract text strings from (Text) Tj or [ (Text) ... ] TJ
    if (preg_match_all('/\((.*?)\)/s', $stream, $textMatches)) {
        foreach ($textMatches[1] as $t) {
            $text .= $t . " ";
        }
    }
}

echo "--- REGEX TEXT LENGTH: " . strlen($text) . " ---\n";
echo substr($text, 0, 500) . "\n";
