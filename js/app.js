function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('open');
    });
});

// Close modal on Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(el => el.classList.remove('open'));
    }
});

function editTask(taskId, task) {
    document.getElementById('taskModalTitle').textContent = 'Edit Task';
    document.getElementById('taskIdField').value = taskId;
    document.getElementById('taskTitle').value = task.title || '';
    document.getElementById('taskDescription').value = task.description || '';
    document.getElementById('taskStatus').value = task.status || 'todo';
    document.getElementById('taskPriority').value = task.priority || 'medium';
    document.getElementById('taskDueDate').value = task.due_date || '';
    document.getElementById('taskSubmitBtn').textContent = 'Save Changes';
    openModal('taskModal');
}

// Reset task modal when opened fresh
document.querySelector('[onclick="openModal(\'taskModal\')"]')?.addEventListener('click', () => {
    document.getElementById('taskModalTitle').textContent = 'New Task';
    document.getElementById('taskIdField').value = '';
    document.getElementById('taskTitle').value = '';
    document.getElementById('taskDescription').value = '';
    document.getElementById('taskStatus').value = 'todo';
    document.getElementById('taskPriority').value = 'medium';
    document.getElementById('taskDueDate').value = '';
    document.getElementById('taskSubmitBtn').textContent = 'Add Task';
});

function deleteTask(taskId, projectId) {
    if (!confirm('Delete this task?')) return;
    window.location.href = `api/tasks.php?delete=${taskId}&project=${projectId}`;
}
