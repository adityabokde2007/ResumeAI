<?php
require 'includes/config.php';
$db = getDB();
$db->query("ALTER TABLE users ADD COLUMN profile_changes_count INT DEFAULT 0");
$db->query("ALTER TABLE users ADD COLUMN profile_change_window DATETIME NULL");
echo "Columns added.";
