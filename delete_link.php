<?php
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/config.php';
requireLogin();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['link_id'])) {
    if (!csrfVerify()) {
        header("Location: links.php");
        exit;
    }
    $linkId = (int)$_POST['link_id'];
    $stmt = $tekupdo->prepare("DELETE FROM shortened_urls WHERE id = ? AND user_id = ?");
    $stmt->execute([$linkId, $_SESSION['user_id']]);
    setFlash('success', $lang === 'es' ? 'Enlace eliminado.' : 'Link deleted.');
}
header("Location: links.php");
exit;
