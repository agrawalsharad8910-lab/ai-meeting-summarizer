<?php
// ============================================================
// AI Meeting Minutes Summarizer - Meetings CRUD API
// ============================================================

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

if (isset($input['action'])) {
    $action = $input['action'];
}

$db = getDBConnection();
$userId = $_SESSION['user_id'] ?? 1; // Default demo user if not logged in

switch ($action) {
    case 'list':
        handleListMeetings($db, $userId);
        break;
    case 'get':
        $meetingId = intval($_GET['id'] ?? $input['id'] ?? 0);
        handleGetMeeting($db, $meetingId);
        break;
    case 'save':
        handleSaveMeeting($db, $userId, $input);
        break;
    case 'delete':
        $meetingId = intval($_GET['id'] ?? $input['id'] ?? 0);
        handleDeleteMeeting($db, $meetingId);
        break;
    default:
        handleListMeetings($db, $userId);
}

/**
 * List all meetings with stats
 */
function handleListMeetings($db, $userId) {
    if (!$db) {
        // Sample fallback meetings for demo when MySQL is disconnected
        $demoMeetings = getDemoMeetingsList();
        sendJsonResponse(true, $demoMeetings, 'Fetched meetings (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("
            SELECT m.*, 
                   (SELECT COUNT(*) FROM action_items ai WHERE ai.meeting_id = m.id) as total_action_items,
                   (SELECT COUNT(*) FROM action_items ai WHERE ai.meeting_id = m.id AND ai.status = 'completed') as completed_action_items
            FROM meetings m 
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
        $meetings = $stmt->fetchAll();

        foreach ($meetings as &$m) {
            $m['key_points'] = json_decode($m['key_points'] ?? '[]', true);
        }

        sendJsonResponse(true, $meetings, 'Meetings fetched successfully.');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}

/**
 * Get detailed meeting by ID
 */
function handleGetMeeting($db, $meetingId) {
    if ($meetingId <= 0) {
        sendJsonResponse(false, [], 'Invalid meeting ID.', 400);
    }

    if (!$db) {
        // Fallback demo detail
        $demoMeeting = getDemoMeetingDetail($meetingId);
        sendJsonResponse(true, $demoMeeting, 'Meeting details fetched (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("SELECT * FROM meetings WHERE id = ?");
        $stmt->execute([$meetingId]);
        $meeting = $stmt->fetch();

        if (!$meeting) {
            sendJsonResponse(false, [], 'Meeting not found.', 404);
        }

        $meeting['key_points'] = json_decode($meeting['key_points'] ?? '[]', true);

        // Fetch Action Items
        $stmtAi = $db->prepare("SELECT * FROM action_items WHERE meeting_id = ? ORDER BY id ASC");
        $stmtAi->execute([$meetingId]);
        $meeting['action_items'] = $stmtAi->fetchAll();

        // Fetch Key Decisions
        $stmtKd = $db->prepare("SELECT * FROM key_decisions WHERE meeting_id = ? ORDER BY id ASC");
        $stmtKd->execute([$meetingId]);
        $meeting['key_decisions'] = array_column($stmtKd->fetchAll(), 'decision');

        sendJsonResponse(true, $meeting, 'Meeting details loaded.');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}

/**
 * Save new meeting record
 */
function handleSaveMeeting($db, $userId, $input) {
    $title      = trim($input['title'] ?? 'Untitled Meeting');
    $department = trim($input['department'] ?? 'General');
    $date       = $input['meeting_date'] ?? date('Y-m-d');
    $duration   = intval($input['duration_minutes'] ?? 30);
    $transcript = trim($input['raw_transcript'] ?? '');
    $summary    = trim($input['executive_summary'] ?? '');
    $keyPoints  = json_encode($input['key_points'] ?? []);
    $sentiment  = trim($input['sentiment'] ?? 'Productive');
    $wordCount  = intval($input['word_count'] ?? str_word_count($transcript));
    
    $actionItems = $input['action_items'] ?? [];
    $decisions   = $input['key_decisions'] ?? [];

    if (empty($transcript)) {
        sendJsonResponse(false, [], 'Meeting transcript cannot be empty.', 400);
    }

    if (!$db) {
        $newId = time();
        sendJsonResponse(true, ['id' => $newId, 'title' => $title], 'Meeting saved successfully (Demo Mode).');
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("
            INSERT INTO meetings (user_id, title, department, meeting_date, duration_minutes, raw_transcript, executive_summary, key_points, sentiment, word_count)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $department, $date, $duration, $transcript, $summary, $keyPoints, $sentiment, $wordCount]);
        $meetingId = $db->lastInsertId();

        // Insert Action Items
        if (!empty($actionItems)) {
            $stmtAi = $db->prepare("INSERT INTO action_items (meeting_id, task, assignee, due_date, status) VALUES (?, ?, ?, ?, ?)");
            foreach ($actionItems as $item) {
                $task     = is_array($item) ? ($item['task'] ?? '') : $item;
                $assignee = is_array($item) ? ($item['assignee'] ?? 'Unassigned') : 'Unassigned';
                $dueDate  = is_array($item) ? ($item['due_date'] ?? date('Y-m-d', strtotime('+3 days'))) : date('Y-m-d', strtotime('+3 days'));
                $status   = is_array($item) ? ($item['status'] ?? 'pending') : 'pending';
                if (!empty($task)) {
                    $stmtAi->execute([$meetingId, $task, $assignee, $dueDate, $status]);
                }
            }
        }

        // Insert Key Decisions
        if (!empty($decisions)) {
            $stmtKd = $db->prepare("INSERT INTO key_decisions (meeting_id, decision) VALUES (?, ?)");
            foreach ($decisions as $dec) {
                $decisionText = is_array($dec) ? ($dec['decision'] ?? '') : $dec;
                if (!empty($decisionText)) {
                    $stmtKd->execute([$meetingId, $decisionText]);
                }
            }
        }

        $db->commit();
        sendJsonResponse(true, ['id' => $meetingId, 'title' => $title], 'Meeting minutes and AI summary saved to database!');
    } catch (Exception $e) {
        if ($db) $db->rollBack();
        sendJsonResponse(false, [], 'Failed to save meeting: ' . $e->getMessage(), 500);
    }
}

/**
 * Delete meeting record
 */
function handleDeleteMeeting($db, $meetingId) {
    if (!$db) {
        sendJsonResponse(true, [], 'Meeting deleted (Demo Mode).');
    }

    try {
        $stmt = $db->prepare("DELETE FROM meetings WHERE id = ?");
        $stmt->execute([$meetingId]);
        sendJsonResponse(true, [], 'Meeting removed.');
    } catch (Exception $e) {
        sendJsonResponse(false, [], 'Database error: ' . $e->getMessage(), 500);
    }
}

// Fallback Helper Functions for Demo Data
function getDemoMeetingsList() {
    return [
        [
            'id' => 1,
            'title' => 'Project Alpha Architecture & Database Design',
            'department' => 'Engineering',
            'meeting_date' => '2026-07-20',
            'duration_minutes' => 45,
            'executive_summary' => 'The engineering team aligned on the database schema and architecture for the AI Meeting Summarizer project.',
            'sentiment' => 'Productive',
            'word_count' => 184,
            'total_action_items' => 3,
            'completed_action_items' => 1,
            'created_at' => '2026-07-20 10:30:00'
        ],
        [
            'id' => 2,
            'title' => 'Product Roadmap & Sprint 4 Deliverables',
            'department' => 'Product Management',
            'meeting_date' => '2026-07-18',
            'duration_minutes' => 60,
            'executive_summary' => 'Reviewed user feedback on speech-to-text accuracy and finalized priorities for Sprint 4 release.',
            'sentiment' => 'Decision-Heavy',
            'word_count' => 320,
            'total_action_items' => 4,
            'completed_action_items' => 2,
            'created_at' => '2026-07-18 14:00:00'
        ]
    ];
}

function getDemoMeetingDetail($id) {
    return [
        'id' => $id,
        'title' => 'Project Alpha Architecture & Database Design',
        'department' => 'Engineering',
        'meeting_date' => '2026-07-20',
        'duration_minutes' => 45,
        'raw_transcript' => "Alex: Welcome team. Today we need to finalize the database schema for our AI Summarizer project.\nSarah: I reviewed the proposed MySQL schema. We have users, meetings, action_items, and key_decisions tables.\nJohn: What about the Gemini API integration?\nAlex: Sarah will implement the Gemini API integration with an offline NLP fallback rules engine.\nJohn: Perfect. I will build the frontend dashboard using CSS custom properties, glassmorphism UI, and dark mode by Friday.",
        'executive_summary' => 'The engineering team aligned on the database schema and architecture for the AI Meeting Summarizer project. Key decisions were made regarding Gemini API fallback mechanisms, frontend glassmorphism design, and Web Speech API integration.',
        'key_points' => [
            "Finalized 4-table MySQL schema for users, meetings, action items, and decisions.",
            "Decided on dual-mode AI engine (Gemini API with offline NLP fallback).",
            "Frontend built with CSS glassmorphism and modern dark mode.",
            "Web Speech API selected for live voice-to-text recording."
        ],
        'sentiment' => 'Productive',
        'word_count' => 184,
        'created_at' => '2026-07-20 10:30:00',
        'action_items' => [
            ['id' => 1, 'task' => 'Implement Gemini API integration with PHP offline NLP fallback', 'assignee' => 'Sarah', 'due_date' => '2026-07-24', 'status' => 'pending'],
            ['id' => 2, 'task' => 'Build responsive Frontend Dashboard with glassmorphism UI', 'assignee' => 'John', 'due_date' => '2026-07-25', 'status' => 'pending'],
            ['id' => 3, 'task' => 'Integrate Web Speech API for live voice recording component', 'assignee' => 'John', 'due_date' => '2026-07-26', 'status' => 'completed']
        ],
        'key_decisions' => [
            'Use PDO MySQL with prepared statements for database operations.',
            'Implement an offline NLP fallback summarizer so the app can be presented offline without API keys.'
        ]
    ];
}
