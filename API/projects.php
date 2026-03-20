<?php
$db = new SQLite3(__DIR__ . '/../projects.db');
$db->enableExceptions(true);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = $_POST['color'] ?? '#6366f1';

    if (!$name) {
        header('Location: ../index.php');
        exit;
    }

    $stmt = $db->prepare("INSERT INTO projects (name, description, color) VALUES (:name, :desc, :color)");
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':desc', $description);
    $stmt->bindValue(':color', $color);
    $stmt->execute();

    $id = $db->lastInsertRowID();
    header("Location: ../index.php?project=$id");
    exit;
}

if ($method === 'GET' && isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->exec("DELETE FROM projects WHERE id = $id");
    header('Location: ../index.php');
    exit;
}

header('Location: ../index.php');
