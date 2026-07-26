<?php
require_once 'db.php';
include 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/tasks.css?v=<?php echo time(); ?>">

<div id="task-notifications"></div>

<div class="row mb-4 animate-fade-in">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1">Team Workspace</h2>
        <p class="text-muted small">Manage your inventory tasks, reminders, and team collaborations.</p>
    </div>
    <div class="col-md-4 text-md-end">
        <button class="btn btn-gradient-plus px-4" data-bs-toggle="modal" data-bs-target="#taskModal">
            <i class="fas fa-plus me-2"></i> Create New Task
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Sidebar Filters -->
    <div class="col-lg-3">
        <div class="filter-sidebar">
            <div class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-secondary border-opacity-25"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="task-search" class="form-control bg-transparent border-secondary border-opacity-25" placeholder="Search tasks...">
                </div>
            </div>

            <h6 class="text-uppercase small fw-bold text-muted mb-3 tracking-wider">Filters</h6>
            <a href="#" class="filter-link active" data-filter="all"><i class="fas fa-tasks"></i> All Tasks</a>
            <a href="#" class="filter-link" data-filter="today"><i class="fas fa-calendar-day"></i> Today</a>
            <a href="#" class="filter-link" data-filter="pending"><i class="fas fa-clock"></i> Pending</a>
            <a href="#" class="filter-link" data-filter="overdue"><i class="fas fa-exclamation-circle text-danger"></i> Overdue</a>
            <a href="#" class="filter-link" data-filter="completed"><i class="fas fa-check-circle text-success"></i> Completed</a>

            <h6 class="text-uppercase small fw-bold text-muted mt-4 mb-3 tracking-wider">Progress Overview</h6>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Task Completion</span>
                <span id="task-progress-text" class="fw-bold text-primary">0%</span>
            </div>
            <div class="progress-luxury mb-4">
                <div id="task-progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>

            <div class="card bg-primary bg-opacity-10 border-0 p-3 mt-4">
                <div class="d-flex align-items-center">
                    <div class="bg-primary p-2 rounded-circle me-3"><i class="fas fa-lightbulb text-white"></i></div>
                    <div class="small">
                        <div class="fw-bold">Pro Tip</div>
                        <div class="text-muted opacity-75">Drag and drop tasks to prioritize your workflow.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task List -->
    <div class="col-lg-9">
        <div id="task-list" class="sortable">
            <!-- Tasks will be loaded here via JS -->
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Task Modal -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-secondary border-opacity-10">
                <h5 class="modal-title fw-bold" id="taskModalLabel">Create New Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="task-form">
                <input type="hidden" name="id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold">Task Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g., Update SSD stock levels">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold">Description (Optional)</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Provide more details about the task..."></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="Low">Low</option>
                                <option value="Medium" selected>Medium</option>
                                <option value="High">High</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Category</label>
                            <select name="category" class="form-select">
                                <option value="Inventory">Inventory</option>
                                <option value="Orders">Orders</option>
                                <option value="Payment">Payment</option>
                                <option value="Delivery">Delivery</option>
                                <option value="Customer Follow-up">Customer Follow-up</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Due Date & Time</label>
                            <input type="datetime-local" name="due_date" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold">Assign To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">No assignment</option>
                                <!-- Users loaded via JS -->
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold">Status</label>
                            <select name="status" class="form-select">
                                <option value="Pending">Pending</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold">Attachments</label>
                            <input type="file" name="attachments[]" class="form-control" multiple>
                            <div class="form-text text-muted small">Attach screenshots or relevant documents.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-10">
                    <button type="button" class="btn btn-link text-muted text-decoration-none" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">Save Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="assets/js/tasks.js?v=<?php echo time(); ?>"></script>

<?php include 'includes/footer.php'; ?>
