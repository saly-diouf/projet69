<?php
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$etude_id = (int) getParam('id', 0);

$stmt = $db->prepare("SELECT * FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);
$etude = $stmt->fetch();

if (!$etude) {
    redirect(APP_URL . "/etudes/list.php");
}

$stmt = $db->prepare("DELETE FROM etudes WHERE id = ?");
$stmt->execute([$etude_id]);

redirect(APP_URL . "/etudes/list.php?deleted=1");
