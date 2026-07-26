document.addEventListener('DOMContentLoaded', function() {
    const taskList = document.getElementById('task-list');
    const taskForm = document.getElementById('task-form');
    const notificationContainer = document.getElementById('task-notifications');
    let currentFilter = 'all';
    let currentSearch = '';

    // Initialize Sortable
    if (typeof Sortable !== 'undefined' && taskList) {
        new Sortable(taskList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                const orders = Array.from(taskList.children).map(card => card.dataset.id);
                updateTaskOrder(orders);
            }
        });
    }

    // Load Tasks
    loadTasks();
    loadUsers();

    // Event Listeners
    if (taskForm) {
        taskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveTask();
        });
    }

    // Filter Buttons
    document.querySelectorAll('.filter-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.filter-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            loadTasks();
        });
    });

    // Search Input
    const searchInput = document.getElementById('task-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            currentSearch = this.value;
            loadTasks();
        }, 500));
    }

    function loadTasks() {
        if (!taskList) return;
        
        fetch(`api/tasks.php?action=list&filter=${currentFilter}&search=${encodeURIComponent(currentSearch)}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderTasks(data.data);
                    updateProgressBar(data.data);
                }
            });
    }

    function renderTasks(tasks) {
        taskList.innerHTML = '';
        tasks.forEach((task, index) => {
            const card = document.createElement('div');
            card.className = `task-card p-3 animate-task priority-${task.priority}`;
            card.style.animationDelay = `${index * 0.05}s`;
            card.dataset.id = task.id;

            const isCompleted = task.status === 'Completed';
            const isOverdue = task.status === 'Pending' && task.due_date && new Date(task.due_date) < new Date();

            card.innerHTML = `
                <div class="d-flex align-items-start">
                    <div class="form-check me-3">
                        <input type="checkbox" class="form-check-input rounded-circle task-toggle" ${isCompleted ? 'checked' : ''} data-id="${task.id}">
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1 ${isCompleted ? 'task-status-completed' : (isOverdue ? 'task-status-overdue' : '')}">${task.title}</h6>
                            <div class="dropdown">
                                <button class="btn btn-link btn-sm text-muted p-0" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                                    <li><a class="dropdown-item edit-task" href="#" data-id="${task.id}"><i class="fas fa-edit me-2"></i>Edit</a></li>
                                    <li><a class="dropdown-item text-danger delete-task" href="#" data-id="${task.id}"><i class="fas fa-trash me-2"></i>Delete</a></li>
                                </ul>
                            </div>
                        </div>
                        <p class="text-muted small mb-2">${task.description || 'No description'}</p>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="task-category-badge"><i class="fas fa-tag me-1"></i>${task.category}</span>
                            ${task.due_date ? `<span class="small ${isOverdue ? 'text-danger fw-bold' : 'text-muted'}"><i class="fas fa-calendar-alt me-1"></i>${formatDate(task.due_date)}</span>` : ''}
                            ${task.assigned_username ? `<span class="small text-muted"><i class="fas fa-user me-1"></i>${task.assigned_username}</span>` : ''}
                            ${task.attachments.length > 0 ? `<span class="small text-muted"><i class="fas fa-paperclip me-1"></i>${task.attachments.length}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
            taskList.appendChild(card);
        });

        // Attach listeners for toggle and edit
        document.querySelectorAll('.task-toggle').forEach(btn => {
            btn.addEventListener('change', function() {
                const status = this.checked ? 'Completed' : 'Pending';
                toggleTaskStatus(this.dataset.id, status);
            });
        });

        document.querySelectorAll('.edit-task').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                openEditModal(this.dataset.id);
            });
        });

        document.querySelectorAll('.delete-task').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to delete this task?')) {
                    deleteTask(this.dataset.id);
                }
            });
        });
    }

    function saveTask() {
        const formData = new FormData(taskForm);
        const action = formData.get('id') ? 'update' : 'create';

        fetch(`api/tasks.php?action=${action}`, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('taskModal')).hide();
                taskForm.reset();
                loadTasks();
                showNotification(action === 'create' ? 'Task created successfully!' : 'Task updated successfully!');
            }
        });
    }

    function toggleTaskStatus(id, status) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);

        fetch('api/tasks.php?action=toggle_status', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadTasks();
                if (status === 'Completed') {
                    playSuccessSound();
                    showNotification('Task completed! Great job!');
                }
            }
        });
    }

    function deleteTask(id) {
        const formData = new FormData();
        formData.append('id', id);

        fetch('api/tasks.php?action=delete', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadTasks();
                showNotification('Task deleted.');
            }
        });
    }

    function updateTaskOrder(orders) {
        const formData = new FormData();
        orders.forEach((id, index) => formData.append(`orders[${index}]`, id));

        fetch('api/tasks.php?action=update_order', {
            method: 'POST',
            body: formData
        });
    }

    function openEditModal(id) {
        fetch(`api/tasks.php?action=list`)
            .then(res => res.json())
            .then(data => {
                const task = data.data.find(t => t.id == id);
                if (task) {
                    const form = document.getElementById('task-form');
                    form.id.value = task.id;
                    form.title.value = task.title;
                    form.description.value = task.description;
                    form.priority.value = task.priority;
                    form.category.value = task.category;
                    form.due_date.value = task.due_date ? task.due_date.replace(' ', 'T') : '';
                    form.assigned_to.value = task.assigned_to || '';
                    form.status.value = task.status;

                    const modalTitle = document.getElementById('taskModalLabel');
                    modalTitle.innerText = 'Edit Task';
                    
                    const modal = new bootstrap.Modal(document.getElementById('taskModal'));
                    modal.show();
                }
            });
    }

    function loadUsers() {
        const select = document.querySelector('select[name="assigned_to"]');
        if (!select) return;
        
        fetch('api/tasks.php?action=users')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    data.data.forEach(user => {
                        const opt = document.createElement('option');
                        opt.value = user.id;
                        opt.textContent = user.username;
                        select.appendChild(opt);
                    });
                }
            });
    }

    function updateProgressBar(tasks) {
        const total = tasks.length;
        const completed = tasks.filter(t => t.status === 'Completed').length;
        const percent = total > 0 ? Math.round((completed / total) * 100) : 0;

        const bar = document.getElementById('task-progress-bar');
        const text = document.getElementById('task-progress-text');
        if (bar) bar.style.width = `${percent}%`;
        if (text) text.innerText = `${percent}% Completed`;
    }

    // Notifications and Reminders
    function checkReminders() {
        fetch('api/tasks.php?action=reminders')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.reminders.length > 0) {
                    data.reminders.forEach(reminder => {
                        showNotification(`Reminder: ${reminder.title} is due soon!`, 'important');
                        playAlertSound();
                    });
                }
            });
    }

    // Check reminders every 1 minute
    setInterval(checkReminders, 60000);
    // Initial check
    setTimeout(checkReminders, 5000);

    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        const icon = type === 'important' ? 'fa-exclamation-circle text-danger' : 'fa-info-circle text-info';
        
        toast.innerHTML = `
            <i class="fas ${icon}"></i>
            <div class="flex-grow-1">${message}</div>
        `;
        
        notificationContainer.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }

    function playSuccessSound() {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        audio.play().catch(e => {}); // Handle blocked autoplay
    }

    function playAlertSound() {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2571/2571-preview.mp3');
        audio.play().catch(e => {});
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }
});
