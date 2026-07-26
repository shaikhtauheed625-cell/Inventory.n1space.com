<?php
header('Content-Type: application/json');
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        $filter = $_GET['filter'] ?? 'all';
        $category = $_GET['category'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $search = $_GET['search'] ?? '';

        $query = "SELECT t.*, u.username as assigned_username FROM todos t 
                  LEFT JOIN users u ON t.assigned_to = u.id WHERE 1=1";
        $params = [];

        if ($filter == 'pending') $query .= " AND t.status = 'Pending'";
        if ($filter == 'completed') $query .= " AND t.status = 'Completed'";
        if ($filter == 'overdue') $query .= " AND t.status = 'Pending' AND t.due_date < NOW()";
        if ($filter == 'today') $query .= " AND DATE(t.due_date) = CURDATE()";

        if ($category) {
            $query .= " AND t.category = ?";
            $params[] = $category;
        }
        if ($priority) {
            $query .= " AND t.priority = ?";
            $params[] = $priority;
        }
        if ($search) {
            $query .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $query .= " ORDER BY t.sort_order ASC, t.due_date ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $tasks = $stmt->fetchAll();

        // Get attachments for each task
        foreach ($tasks as &$task) {
            $stmt = $pdo->prepare("SELECT * FROM task_attachments WHERE task_id = ?");
            $stmt->execute([$task['id']]);
            $task['attachments'] = $stmt->fetchAll();
        }

        echo json_encode(['success' => true, 'data' => $tasks]);
        break;

    case 'create':
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'Medium';
        $category = $_POST['category'] ?? 'Inventory';
        $due_date = $_POST['due_date'] ?? null;
        $assigned_to = $_POST['assigned_to'] ?: null;

        if (!$title) {
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO todos (title, description, priority, category, due_date, assigned_to) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $priority, $category, $due_date, $assigned_to]);
        $taskId = $pdo->lastInsertId();

        handleUploads($taskId, $pdo);

        echo json_encode(['success' => true, 'id' => $taskId]);
        break;

    case 'update':
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'Medium';
        $category = $_POST['category'] ?? 'Inventory';
        $due_date = $_POST['due_date'] ?? null;
        $assigned_to = $_POST['assigned_to'] ?: null;
        $status = $_POST['status'] ?? 'Pending';

        if (!$id || !$title) {
            echo json_encode(['success' => false, 'message' => 'ID and Title are required']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE todos SET title = ?, description = ?, priority = ?, category = ?, due_date = ?, assigned_to = ?, status = ? WHERE id = ?");
        $stmt->execute([$title, $description, $priority, $category, $due_date, $assigned_to, $status, $id]);

        handleUploads($id, $pdo);

        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $id = $_POST['id'] ?? '';
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID is required']);
            exit;
        }

        // Delete attachments first (files from disk)
        $stmt = $pdo->prepare("SELECT file_path FROM task_attachments WHERE task_id = ?");
        $stmt->execute([$id]);
        $attachments = $stmt->fetchAll();
        foreach ($attachments as $att) {
            if (file_exists('../' . $att['file_path'])) {
                unlink('../' . $att['file_path']);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM todos WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'toggle_status':
        $id = $_POST['id'] ?? '';
        $status = $_POST['status'] ?? 'Pending';
        if (!$id) exit;

        $stmt = $pdo->prepare("UPDATE todos SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
        break;

    case 'update_order':
        $orders = $_POST['orders'] ?? [];
        foreach ($orders as $index => $id) {
            $stmt = $pdo->prepare("UPDATE todos SET sort_order = ? WHERE id = ?");
            $stmt->execute([$index, $id]);
        }
        echo json_encode(['success' => true]);
        break;

    case 'reminders':
        // Fetch tasks that are due soon and haven't sent a reminder yet
        $stmt = $pdo->prepare("SELECT * FROM todos WHERE status = 'Pending' AND due_date IS NOT NULL AND due_date <= DATE_ADD(NOW(), INTERVAL 30 MINUTE) AND reminder_sent = 0");
        $stmt->execute();
        $reminders = $stmt->fetchAll();

        if ($reminders) {
            $ids = array_column($reminders, 'id');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE todos SET reminder_sent = 1 WHERE id IN ($placeholders)")->execute($ids);
        }

        echo json_encode(['success' => true, 'reminders' => $reminders]);
        break;

    case 'users':
        $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function handleUploads($taskId, $pdo) {
    if (!empty($_FILES['attachments']['name'][0])) {
        $files = $_FILES['attachments'];
        $uploadDir = '../uploads/tasks/';
        
        for ($i = 0; $i < count($files['name']); $i++) {
            $fileName = basename($files['name'][$i]);
            $targetPath = $uploadDir . time() . '_' . $fileName;
            $dbPath = 'uploads/tasks/' . time() . '_' . $fileName;

            if (move_uploaded_file($files['tmp_name'][$i], $targetPath)) {
                $stmt = $pdo->prepare("INSERT INTO task_attachments (task_id, file_path, file_name) VALUES (?, ?, ?)");
                $stmt->execute([$taskId, $dbPath, $fileName]);
            }
        }
    }
}
?>
