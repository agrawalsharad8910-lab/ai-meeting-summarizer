<?php
// ============================================================
// AI Meeting Minutes Summarizer - Action Items API
// ============================================================

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

$action    = $_GET['action'] ?? $input['action'] ?? '';
$itemId    = intval($input['id'] ?? $_GET['id'] ?? 0);
$status    = $input['status'] ?? 'pending';
$meetingId = intval($input['meeting_id'] ?? 0);
$task      = trim($input['task'] ?? '');
$assignee  = trim($input['assignee'] ?? 'Unassigned');
$dueDate   = $input['due_date'] ?? date('Y-m-d', strtotime('+3 days'));

$db = getDBConnection();

switch ($action) {
    case 'toggle':
        handleToggleStatus($db, $itemId, $status);
        break;
    case 'add':
        handleAddActionItem($db, $meetingId, $task, $assignee, $dueDate);
        break;
    case 'delete':
        handleDeleteActionItem($db, $itemId);
        break;
    default:
        sendJsonResponse(false, [], 'Invalid action specified.', 400);
}

function handleToggleStatus($db, $itemId, $status) {
    if ($itemId <= 0) {
        sendJsonResponse(false, [], 'Invalid action item ID.', 400);
    }

    $newStatus = ($status === 'completed') ? 'completed' : 'pending';

    if (!$db) {
        sendJsonResponse(true, ['id' => $itemId, 'status' => $newStatus], 'Status updated (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("UPDATE action_items SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $itemId]);

        sendJsonResponse(true, ['id' => $itemId, 'status' => $newStatus], 'Task status updated!');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}

function handleAddActionItem($db, $meetingId, $task, $assignee, $dueDate) {
    if ($meetingId <= 0 || empty($task)) {
        sendJsonResponse(false, [], 'Meeting ID and task text are required.', 400);
    }

    if (!$db) {
        sendJsonResponse(true, ['id' => time(), 'task' => $task, 'status' => 'pending'], 'Action item added (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("INSERT INTO action_items (meeting_id, task, assignee, due_date, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$meetingId, $task, $assignee, $dueDate]);

        $newId = $db->lastInsertId();
        sendJsonResponse(true, [
            'id'         => $newId,
            'meeting_id' => $meetingId,
            'task'       => $task,
            'assignee'   => $assignee,
            'due_date'   => $dueDate,
            'status'     => 'pending'
        ], 'Action item added successfully.');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}

function handleDeleteActionItem($db, $itemId) {
    if ($itemId <= 0) {
        sendJsonResponse(false, [], 'Invalid action item ID.', 400);
    }

    if (!$db) {
        sendJsonResponse(true, [], 'Item deleted (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("DELETE FROM action_items WHERE id = ?");
        $stmt->execute([$itemId]);
        sendJsonResponse(true, [], 'Action item deleted.');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}
