<?php
$db = new SQLite3(__DIR__ . '/../projects.db');
$db->enableExceptions(true);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $projectId = (int)($_POST['project_id'] ?? 0);
    $taskId    = (int)($_POST['task_id'] ?? 0);
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $status    = $_POST['status'] ?? 'todo';
    $priority  = $_POST['priority'] ?? 'medium';
    $dueDate   = $_POST['due_date'] ?? null;

    if (!$title || !$projectId) {
        header("Location: ../index.php?project=$projectId");
        exit;
    }

    $allowedStatus   = ['todo', 'in-progress', 'done'];
    $allowedPriority = ['low', 'medium', 'high'];
    if (!in_array($status, $allowedStatus)) $status = 'todo';
    if (!in_array($priority, $allowedPriority)) $priority = 'medium';
    $dueDateVal = $dueDate ? $dueDate : null;

    if ($taskId > 0) {
        // Update existing
        $stmt = $db->prepare("UPDATE tasks SET title=:title, description=:desc, status=:status, priority=:priority, due_date=:due WHERE id=:id");
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':desc', $desc);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':priority', $priority);
        $stmt->bindValue(':due', $dueDateVal);
        $stmt->bindValue(':id', $taskId);
        $stmt->execute();
    } else {
        // Insert new
        $stmt = $db->prepare("INSERT INTO tasks (project_id, title, description, status, priority, due_date) VALUES (:pid, :title, :desc, :status, :priority, :due)");
        $stmt->bindValue(':pid', $projectId);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':desc', $desc);
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':priority', $priority);
        $stmt->bindValue(':due', $dueDateVal);
        $stmt->execute();
    }

    header("Location: ../index.php?project=$projectId");
    exit;
}

if ($method === 'GET' && isset($_GET['delete'])) {
    $id        = (int)$_GET['delete'];
    $projectId = (int)($_GET['project'] ?? 0);
    $db->exec("DELETE FROM tasks WHERE id = $id");
    header("Location: ../index.php?project=$projectId");
    exit;
}

header('Location: ../index.php');
