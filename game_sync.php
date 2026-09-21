<?php
// game_sync.php
require_once 'config.php';
header('Content-Type: application/json');

$user = get_user($pdo);
if (!$user) { echo json_encode(['error' => 'Unauthorized']); exit; }

$code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("SELECT m.*, u1.name as p1_name, u2.name as p2_name FROM matches m LEFT JOIN users u1 ON m.player1_id = u1.id LEFT JOIN users u2 ON m.player2_id = u2.id WHERE m.room_code = ?");
$stmt->execute([$code]);
$match = $stmt->fetch();

if (!$match) { echo json_encode(['error' => 'Match not found']); exit; }

$action = $_POST['action'] ?? $_GET['action'] ?? 'get_state';

// 1. Get Live State
if ($action === 'get_state') {
    echo json_encode([
        'status' => $match['status'],
        'player1_id' => $match['player1_id'],
        'player2_id' => $match['player2_id'],
        'p1_name' => $match['p1_name'],
        'p2_name' => $match['p2_name'],
        'current_turn' => $match['current_turn'],
        'winner_id' => $match['winner_id'],
        'win_amount' => $match['win_amount'],
        'game_state' => json_decode($match['game_state'], true),
        'my_id' => $user['id']
    ]);
    exit;
}

// 2. Play Ludo Move (Dice Roll & Advance)
if ($action === 'ludo_roll' && $match['status'] === 'in_progress') {
    if ($match['current_turn'] != $user['id']) { echo json_encode(['error' => 'Not your turn']); exit; }
    
    $state = json_decode($match['game_state'], true);
    $dice = rand(1, 6);
    $isP1 = ($user['id'] == $match['player1_id']);
    
    if ($isP1) {
        $state['p1_pos'] += $dice;
        if ($state['p1_pos'] >= 30) { settleMatch($pdo, $match, $user['id']); exit; }
    } else {
        $state['p2_pos'] += $dice;
        if ($state['p2_pos'] >= 30) { settleMatch($pdo, $match, $user['id']); exit; }
    }

    $state['dice'] = $dice;
    $next_turn = ($isP1) ? $match['player2_id'] : $match['player1_id'];

    $pdo->prepare("UPDATE matches SET game_state = ?, current_turn = ? WHERE id = ?")
        ->execute([json_encode($state), $next_turn, $match['id']]);
    
    echo json_encode(['success' => true, 'dice' => $dice, 'state' => $state]);
    exit;
}

// 3. Submit Answer in Q&A Challenge
if ($action === 'submit_qa_answer' && $match['status'] === 'in_progress') {
    $submitted = strtolower(trim($_POST['answer'] ?? ''));
    $correct = strtolower(trim($match['custom_answer']));

    if ($submitted === $correct) {
        // Player 2 (Challenger) Wins!
        settleMatch($pdo, $match, $user['id']);
    } else {
        // Player 2 Failed -> Player 1 (Creator) Wins!
        settleMatch($pdo, $match, $match['player1_id']);
    }
    exit;
}

// Helper: Settle Winner & Payout
function settleMatch($pdo, $match, $winnerId) {
    $pdo->beginTransaction();
    // Credit 75% Win Amount to Winner
    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$match['win_amount'], $winnerId]);
    $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'match_win', 'Won Match #{$match['room_code']}')")->execute([$winnerId, $match['win_amount']]);
    
    // Mark match completed
    $pdo->prepare("UPDATE matches SET status = 'completed', winner_id = ? WHERE id = ?")->execute([$winnerId, $match['id']]);
    $pdo->commit();
    echo json_encode(['status' => 'completed', 'winner_id' => $winnerId]);
}