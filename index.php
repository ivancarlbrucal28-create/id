<?php
// Database setup
$db = new SQLite3(__DIR__ . '/projects.db');
$db->enableExceptions(true);

// Create tables
$db->exec("
    CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'active',
        color TEXT DEFAULT '#6366f1',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        project_id INTEGER NOT NULL,
        title TEXT NOT NULL,
        description TEXT,
        status TEXT DEFAULT 'todo',
        priority TEXT DEFAULT 'medium',
        due_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    );
");

// Seed if empty
$count = $db->querySingle("SELECT COUNT(*) FROM projects");
if ($count == 0) {
    $db->exec("
        INSERT INTO projects (name, description, status, color) VALUES
        ('Website Redesign', 'Complete overhaul of the company website with modern design', 'active', '#6366f1'),
        ('Mobile App MVP', 'Build and launch the first version of our mobile application', 'active', '#f59e0b'),
        ('API Integration', 'Connect third-party services to our backend system', 'completed', '#10b981');

        INSERT INTO tasks (project_id, title, description, status, priority, due_date) VALUES
        (1, 'Wireframe mockups', 'Create wireframes for all main pages', 'done', 'high', '2025-01-10'),
        (1, 'Design system setup', 'Establish color palette, typography, and components', 'in-progress', 'high', '2025-01-20'),
        (1, 'Homepage development', 'Build the homepage with animations', 'todo', 'medium', '2025-02-01'),
        (1, 'SEO optimization', 'Add meta tags and structured data', 'todo', 'low', '2025-02-15'),
        (2, 'User auth flow', 'Login, register, and password reset screens', 'done', 'high', '2025-01-05'),
        (2, 'Dashboard screen', 'Main dashboard with stats and quick actions', 'in-progress', 'high', '2025-01-25'),
        (2, 'Push notifications', 'Integrate push notification service', 'todo', 'medium', '2025-02-10'),
        (3, 'Stripe payment setup', 'Integrate Stripe for payment processing', 'done', 'high', '2024-12-20'),
        (3, 'Webhook handlers', 'Handle all Stripe webhook events', 'done', 'high', '2024-12-28');
    ");
}

// Get all projects with task counts
$projects = [];
$result = $db->query("
    SELECT p.*,
        COUNT(t.id) as task_count,
        SUM(CASE WHEN t.status = 'done' THEN 1 ELSE 0 END) as done_count
    FROM projects p
    LEFT JOIN tasks t ON t.project_id = p.id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $projects[] = $row;
}

// Get selected project tasks
$selectedProjectId = isset($_GET['project']) ? (int)$_GET['project'] : ($projects[0]['id'] ?? null);
$selectedProject = null;
$tasks = ['todo' => [], 'in-progress' => [], 'done' => []];

if ($selectedProjectId) {
    foreach ($projects as $p) {
        if ($p['id'] == $selectedProjectId) {
            $selectedProject = $p;
            break;
        }
    }
    $result = $db->query("SELECT * FROM tasks WHERE project_id = $selectedProjectId ORDER BY created_at DESC");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $status = $row['status'];
        if (isset($tasks[$status])) {
            $tasks[$status][] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ProjectFlow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="app-shell">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <span class="logo-mark">PF</span>
                <span class="logo-text">ProjectFlow</span>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="section-label">Projects</div>
            <nav class="project-list">
                <?php foreach ($projects as $p): ?>
                <?php
                    $progress = $p['task_count'] > 0 ? round(($p['done_count'] / $p['task_count']) * 100) : 0;
                    $isActive = $p['id'] == $selectedProjectId;
                ?>
                <a href="?project=<?= $p['id'] ?>" class="project-item <?= $isActive ? 'active' : '' ?>">
                    <div class="project-dot" style="background:<?= htmlspecialchars($p['color']) ?>"></div>
                    <div class="project-info">
                        <span class="project-name"><?= htmlspecialchars($p['name']) ?></span>
                        <div class="project-bar">
                            <div class="project-bar-fill" style="width:<?= $progress ?>%;background:<?= htmlspecialchars($p['color']) ?>"></div>
                        </div>
                    </div>
                    <span class="project-pct"><?= $progress ?>%</span>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="sidebar-footer">
            <button class="btn-new-project" onclick="openModal('projectModal')">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Project
            </button>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <?php if ($selectedProject): ?>

        <!-- Header -->
        <header class="content-header">
            <div class="header-left">
                <div class="project-badge" style="background:<?= htmlspecialchars($selectedProject['color']) ?>20;border-color:<?= htmlspecialchars($selectedProject['color']) ?>40">
                    <span class="badge-dot" style="background:<?= htmlspecialchars($selectedProject['color']) ?>"></span>
                    <?= $selectedProject['status'] === 'completed' ? 'Completed' : 'Active' ?>
                </div>
                <h1 class="project-title"><?= htmlspecialchars($selectedProject['name']) ?></h1>
                <p class="project-desc"><?= htmlspecialchars($selectedProject['description']) ?></p>
            </div>
            <div class="header-right">
                <div class="stat-pill">
                    <span class="stat-num"><?= $selectedProject['task_count'] ?></span>
                    <span class="stat-label">Tasks</span>
                </div>
                <div class="stat-pill">
                    <span class="stat-num"><?= $selectedProject['done_count'] ?></span>
                    <span class="stat-label">Done</span>
                </div>
                <button class="btn-primary" onclick="openModal('taskModal')">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Task
                </button>
            </div>
        </header>

        <!-- Kanban Board -->
        <div class="kanban-board">

            <?php
            $columns = [
                'todo'        => ['label' => 'To Do',       'icon' => '○', 'color' => '#94a3b8'],
                'in-progress' => ['label' => 'In Progress', 'icon' => '◑', 'color' => '#f59e0b'],
                'done'        => ['label' => 'Done',        'icon' => '●', 'color' => '#10b981'],
            ];
            foreach ($columns as $colKey => $col):
            ?>
            <div class="kanban-col" data-status="<?= $colKey ?>">
                <div class="col-header">
                    <div class="col-title-group">
                        <span class="col-icon" style="color:<?= $col['color'] ?>"><?= $col['icon'] ?></span>
                        <span class="col-title"><?= $col['label'] ?></span>
                        <span class="col-count"><?= count($tasks[$colKey]) ?></span>
                    </div>
                </div>

                <div class="task-list" id="col-<?= $colKey ?>">
                    <?php foreach ($tasks[$colKey] as $task): ?>
                    <div class="task-card" data-id="<?= $task['id'] ?>">
                        <div class="task-card-top">
                            <span class="priority-tag priority-<?= $task['priority'] ?>"><?= ucfirst($task['priority']) ?></span>
                            <div class="task-actions">
                                <button class="icon-btn" onclick="editTask(<?= $task['id'] ?>, <?= htmlspecialchars(json_encode($task)) ?>)" title="Edit">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button class="icon-btn danger" onclick="deleteTask(<?= $task['id'] ?>, <?= $selectedProjectId ?>)" title="Delete">
                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                                </button>
                            </div>
                        </div>
                        <h3 class="task-title"><?= htmlspecialchars($task['title']) ?></h3>
                        <?php if ($task['description']): ?>
                        <p class="task-desc"><?= htmlspecialchars($task['description']) ?></p>
                        <?php endif; ?>
                        <?php if ($task['due_date']): ?>
                        <div class="task-due">
                            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <?= date('M j, Y', strtotime($task['due_date'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

        </div>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h2>No projects yet</h2>
            <p>Create your first project to get started</p>
            <button class="btn-primary" onclick="openModal('projectModal')">Create Project</button>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- New Project Modal -->
<div class="modal-overlay" id="projectModal">
    <div class="modal">
        <div class="modal-header">
            <h2>New Project</h2>
            <button class="modal-close" onclick="closeModal('projectModal')">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="api/projects.php" method="POST" class="modal-form">
            <div class="form-group">
                <label>Project Name</label>
                <input type="text" name="name" placeholder="e.g. Marketing Campaign" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="What is this project about?" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Color</label>
                <div class="color-picker">
                    <?php foreach (['#6366f1','#f59e0b','#10b981','#ef4444','#3b82f6','#ec4899','#8b5cf6','#14b8a6'] as $c): ?>
                    <label class="color-swatch">
                        <input type="radio" name="color" value="<?= $c ?>" <?= $c === '#6366f1' ? 'checked' : '' ?>>
                        <span style="background:<?= $c ?>"></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="closeModal('projectModal')">Cancel</button>
                <button type="submit" class="btn-primary">Create Project</button>
            </div>
        </form>
    </div>
</div>

<!-- New Task Modal -->
<div class="modal-overlay" id="taskModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="taskModalTitle">New Task</h2>
            <button class="modal-close" onclick="closeModal('taskModal')">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form action="api/tasks.php" method="POST" class="modal-form" id="taskForm">
            <input type="hidden" name="project_id" value="<?= $selectedProjectId ?>">
            <input type="hidden" name="task_id" id="taskIdField" value="">
            <div class="form-group">
                <label>Task Title</label>
                <input type="text" name="title" id="taskTitle" placeholder="e.g. Design homepage wireframe" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="taskDescription" placeholder="Optional details..." rows="2"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="taskStatus">
                        <option value="todo">To Do</option>
                        <option value="in-progress">In Progress</option>
                        <option value="done">Done</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Priority</label>
                    <select name="priority" id="taskPriority">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Due Date</label>
                <input type="date" name="due_date" id="taskDueDate">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-ghost" onclick="closeModal('taskModal')">Cancel</button>
                <button type="submit" class="btn-primary" id="taskSubmitBtn">Add Task</button>
            </div>
        </form>
    </div>
</div>

<script src="js/app.js"></script>
</body>
</html>
