<?php
require 'includes/config.php';
$db = getDB();
$res = $db->query('SELECT id, email, pending_email FROM users');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
