<?php
require 'includes/config.php';
$db = getDB();
$db->query("ALTER TABLE users ADD COLUMN last_email_change DATETIME NULL");
echo "Column last_email_change added.";
