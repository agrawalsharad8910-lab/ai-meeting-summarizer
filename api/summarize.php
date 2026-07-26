<?php
// ============================================================
// AI Meeting Minutes Summarizer - AI Processing Engine
// Dual Mode: Gemini API Integration + Offline Fallback Engine
// ============================================================

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$inputRaw = file_get_contents('php://input');
$input = json_decode($inputRaw, true) ?? $_POST;

$transcript = trim($input['transcript'] ?? '');
$apiKey     = trim($input['api_key'] ?? '');
$title      = trim($input['title'] ?? 'Untitled Meeting');
$department = trim($input['department'] ?? 'General');

if (empty($transcript)) {
    sendJsonResponse(false, [], 'Transcript text is required for AI summarization.', 400);
}

// Perform AI Summarization
$result = processAISummarization($transcript, $apiKey);

sendJsonResponse(true, $result, 'Transcript processed successfully!');

/**
 * Main Summarization Gateway
 */
function processAISummarization($transcript, $apiKey) {
    if (!empty($apiKey)) {
        $geminiResult = callGeminiAPI($transcript, $apiKey);
        if ($geminiResult['success']) {
            return $geminiResult['data'];
        }
    }

    // Offline / Local Rule-Based Natural Language Summarization Engine
    return localNLPProcessing($transcript);
}

/**
 * Google Gemini API Handler
 */
function callGeminiAPI($transcript, $apiKey) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

    $prompt = "You are an expert AI Executive Assistant. Analyze the following meeting transcript and return ONLY a valid JSON object matching this schema without markdown codeblocks:
{
  \"executive_summary\": \"A concise 2-3 sentence overview of the meeting\",
  \"key_points\": [\"Bullet point 1\", \"Bullet point 2\", \"Bullet point 3\"],
  \"action_items\": [
    {\"task\": \"Specific action task\", \"assignee\": \"Name or Unassigned\", \"due_date\": \"YYYY-MM-DD or Next Week\"}
  ],
  \"key_decisions\": [\"Decision 1\", \"Decision 2\"],
  \"sentiment\": \"Productive / Urgent / Decision-Heavy / Informative\",
  \"topics\": [\"Topic 1\", \"Topic 2\", \"Topic 3\"]
}

Transcript:
\"\"\"
{$transcript}
\"\"\"";

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $resData = json_decode($response, true);
        $textOutput = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        // Clean any code block backticks if present
        $cleanJson = preg_replace('/^```json\s*|\s*```$/i', '', trim($textOutput));
        $parsed = json_decode($cleanJson, true);

        if ($parsed && isset($parsed['executive_summary'])) {
            $parsed['source'] = 'Gemini 1.5 Flash AI';
            $parsed['word_count'] = str_word_count($transcript);
            return ['success' => true, 'data' => $parsed];
        }
    }

    return ['success' => false];
}

/**
 * Local Offline Natural Language Processing Summarizer
 * Built for College Demonstrations & Standalone offline operation
 */
function localNLPProcessing($transcript) {
    $lines = explode("\n", $transcript);
    $sentences = [];
    $speakers = [];
    $actionItems = [];
    $decisions = [];
    $keyPoints = [];

    // Common Action Indicators
    $actionKeywords = ['will', 'shall', 'assigned to', 'need to', 'must', 'action item', 'agreed to', 'take care of', 'task', 'handle', 'prepare', 'build', 'create', 'complete', 'review'];
    $decisionKeywords = ['decided', 'agreed', 'approved', 'finalized', 'resolution', 'concluded', 'selected', 'chose'];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;

        // Detect speaker format "Name: Speech"
        $speakerName = 'Unassigned';
        if (preg_match('/^([A-Z][a-zA-Z\s]{1,20}):\s*(.+)$/i', $line, $matches)) {
            $speakerName = trim($matches[1]);
            $speechContent = trim($matches[2]);
            if (!in_array($speakerName, $speakers)) {
                $speakers[] = $speakerName;
            }
        } else {
            $speechContent = $line;
        }

        // Sentence splitting
        $splitSentences = preg_split('/(?<=[.?!])\s+/', $speechContent);
        foreach ($splitSentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) < 10) continue;
            $sentences[] = $sentence;

            // Detect Action Items
            $lowerSentence = strtolower($sentence);
            foreach ($actionKeywords as $kw) {
                if (strpos($lowerSentence, $kw) !== false) {
                    // Extract due date heuristic
                    $dueDate = date('Y-m-d', strtotime('+3 days'));
                    if (strpos($lowerSentence, 'tomorrow') !== false) {
                        $dueDate = date('Y-m-d', strtotime('+1 day'));
                    } elseif (strpos($lowerSentence, 'friday') !== false) {
                        $dueDate = date('Y-m-d', strtotime('next friday'));
                    } elseif (strpos($lowerSentence, 'next week') !== false) {
                        $dueDate = date('Y-m-d', strtotime('+7 days'));
                    }

                    // Attempt to extract assignee name from sentence
                    $assignee = $speakerName;
                    if (preg_match('/(sarah|john|alex|mike|david|emily|lisa|chris|sam|developer|lead)/i', $sentence, $nameMatch)) {
                        $assignee = ucfirst($nameMatch[1]);
                    }

                    $actionItems[] = [
                        'task' => ucfirst($sentence),
                        'assignee' => $assignee,
                        'due_date' => $dueDate,
                        'status' => 'pending'
                    ];
                    break;
                }
            }

            // Detect Decisions
            foreach ($decisionKeywords as $dkw) {
                if (strpos($lowerSentence, $dkw) !== false) {
                    $decisions[] = ucfirst($sentence);
                    break;
                }
            }
        }
    }

    // Limit and clean action items & decisions
    $actionItems = array_slice(array_unique($actionItems, SORT_REGULAR), 0, 5);
    $decisions = array_slice(array_unique($decisions), 0, 4);

    // Fallbacks if transcript is sparse
    if (empty($actionItems)) {
        $actionItems[] = [
            'task' => 'Follow up on discussed topics and finalize agenda for next sync.',
            'assignee' => count($speakers) > 0 ? $speakers[0] : 'Team Lead',
            'due_date' => date('Y-m-d', strtotime('+4 days')),
            'status' => 'pending'
        ];
    }

    if (empty($decisions)) {
        $decisions[] = 'Team aligned on project scope and agreed on next review timeline.';
    }

    // Extract top sentences for key points
    usort($sentences, function($a, $b) {
        return strlen($b) - strlen($a);
    });
    $keyPoints = array_slice($sentences, 0, min(4, count($sentences)));

    // Generate Executive Summary
    $execSummary = "The meeting focused on key updates and planning. " . 
                   (count($speakers) > 0 ? "Key participants included " . implode(', ', array_slice($speakers, 0, 4)) . ". " : "") .
                   "Core discussions covered operational goals, architectural decisions, and assignment of actionable deliverables.";

    // Determine Sentiment
    $wordCount = str_word_count($transcript);
    $sentiment = 'Productive & Collaborative';
    if (count($actionItems) >= 3) {
        $sentiment = 'Action-Oriented & Urgent';
    } elseif (count($decisions) >= 2) {
        $sentiment = 'Decision-Heavy & Strategic';
    }

    // Extract top topics/keywords
    $words = str_word_count(strtolower(preg_replace('/[^a-zA-Z\s]/', '', $transcript)), 1);
    $stopwords = ['the', 'and', 'to', 'a', 'of', 'in', 'i', 'is', 'that', 'for', 'it', 'on', 'was', 'we', 'this', 'be', 'are', 'with', 'have', 'will', 'not', 'as', 'at', 'today', 'meeting', 'team', 'yes', 'so', 'can', 'you'];
    $filteredWords = array_diff($words, $stopwords);
    $wordCounts = array_count_values($filteredWords);
    arsort($wordCounts);
    $topTopics = array_map('ucfirst', array_keys(array_slice($wordCounts, 0, 5)));

    return [
        'executive_summary' => $execSummary,
        'key_points'        => $keyPoints,
        'action_items'      => $actionItems,
        'key_decisions'     => $decisions,
        'sentiment'         => $sentiment,
        'topics'            => $topTopics,
        'word_count'        => $wordCount,
        'speakers'          => $speakers,
        'source'            => 'Smart Local NLP Engine (Offline)'
    ];
}
