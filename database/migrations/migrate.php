<?php
require_once __DIR__ . '/includes/config.php';
$db = getDB();

$db->query("ALTER TABLE users ADD COLUMN pending_email VARCHAR(255) NULL");
$db->query("ALTER TABLE users ADD COLUMN email_change_token VARCHAR(255) NULL");

echo "Columns added successfully.\n";
