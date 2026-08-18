<?php
// index.php
require_once 'config.php';
$user = get_user($pdo);
if (!$user) { header("Location: auth.php"); exit; }

$err = '';
$success = '';

// Handle Create Room
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_room'])) {
    $game_type = $_POST['game_type'];
    $bet = (float)$_POST['bet_amount'];

    if ($bet < 1 || $bet > 500) {
        $err = "Bet must be between ₹1 and ₹500!";
    } elseif ($user['wallet_balance'] < $bet) {
        $err = "Insufficient balance! Add money to wallet.";
    } else {
        // Generate unique 4-digit numeric code
        do {
            $room_code = str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT id FROM matches WHERE room_code = ?");
            $stmt->execute([$room_code]);
        } while ($stmt->fetch());

        $win_amount = $bet * 1.5;      // 75% Payout
        $platform_fee = $bet * 0.5;    // 25% Platform Commission

        $q = $_POST['custom_question'] ?? null;
        $a = trim($_POST['custom_answer'] ?? '');

        // Initial Game State
        $initial_state = json_encode([
            'p1_pos' => 0, 'p2_pos' => 0,
            'dice' => 1, 'chess_board' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR'
        ]);

        $pdo->beginTransaction();
        // Deduct Bet from Player 1
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$bet, $user['id']]);
        $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'match_fee', 'Created Room #$room_code')")->execute([$user['id'], -$bet]);

        $stmt = $pdo->prepare("INSERT INTO matches (room_code, game_type, player1_id, bet_amount, win_amount, platform_fee, custom_question, custom_answer, game_state, current_turn) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$room_code, $game_type, $user['id'], $bet, $win_amount, $platform_fee, $q, $a, $initial_state, $user['id']]);
        $match_id = $pdo->lastInsertId();
        $pdo->commit();

        header("Location: play.php?code=" . $room_code);
        exit;
    }
}

// Handle Join Room via 4-Digit Code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_room'])) {
    $code = trim($_POST['room_code']);
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE room_code = ? AND status = 'waiting'");
    $stmt->execute([$code]);
    $match = $stmt->fetch();

    if (!$match) {
        $err = "Invalid or expired 4-digit room code!";
    } elseif ($match['player1_id'] == $user['id']) {
        header("Location: play.php?code=" . $code);
        exit;
    } elseif ($user['wallet_balance'] < $match['bet_amount']) {
        $err = "Insufficient balance! You need ₹" . $match['bet_amount'] . " to join.";
    } else {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$match['bet_amount'], $user['id']]);
        $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'match_fee', 'Joined Room #$code')")->execute([$user['id'], -$match['bet_amount']]);
        $pdo->prepare("UPDATE matches SET player2_id = ?, status = 'in_progress' WHERE id = ?")->execute([$user['id'], $match['id']]);
        $pdo->commit();

        header("Location: play.php?code=" . $code);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>1v1 Gaming Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding: 40px 8%; }
        @media (max-width: 768px) { .layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="navbar">
        <h2 class="brand-font" style="color: var(--gold);">⚡ CYBER 1v1</h2>
        <div style="display: flex; gap: 15px; align-items: center;">
            <div class="wallet-box">💰 Balance: ₹<?= number_format($user['wallet_balance'], 2) ?></div>
            <a href="logout.php" class="btn-gold" style="padding: 6px 14px; font-size: 13px;">Logout</a>
        </div>
    </div>

    <?php if($err): ?>
        <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid var(--crimson); color: #fff; padding: 12px; margin: 20px 8% 0; border-radius: 10px;">
            ⚠️ <?= $err ?>
        </div>
    <?php endif; ?>

    <div class="layout">
        <!-- Create Room Box -->
        <div class="glass-card">
            <h3 class="brand-font" style="color: var(--gold); margin-bottom: 15px;">➕ Create a Room</h3>
            <form method="POST">
                <input type="hidden" name="create_room" value="1">
                <label style="font-size: 13px; color: var(--text-muted);">Select Game:</label>
                <select name="game_type" id="game_select" class="input-field" onchange="toggleQA(this.value)">
                    <option value="ludo">🎲 2-Player Ludo Battle</option>
                    <option value="chess">♟️ 2-Player Blitz Chess</option>
                    <option value="qa_challenge">❓ 1v1 Custom Q&A Challenge</option>
                </select>

                <label style="font-size: 13px; color: var(--text-muted);">Bet Amount (₹1 to ₹500):</label>
                <input type="number" name="bet_amount" class="input-field" min="1" max="500" value="10" required>

                <!-- Q&A Custom Fields -->
                <div id="qa-fields" style="display: none; border-top: 1px dashed var(--border); padding-top: 15px;">
                    <label style="font-size: 13px; color: var(--gold);">Apna Sawal Likhein (Your Question):</label>
                    <textarea name="custom_question" class="input-field" placeholder="e.g. Mere gaon ka naam kya hai?"></textarea>
                    
                    <label style="font-size: 13px; color: var(--gold);">Sahi Jawab (Correct Answer):</label>
                    <input type="text" name="custom_answer" class="input-field" placeholder="Exact Answer jo doosra banda likhega">
                </div>

                <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 13px;">
                    <span>Pool: <strong id="pool-preview">₹20</strong> | Winner Gets: <strong style="color:var(--emerald);" id="win-preview">₹15</strong> (75%) | Platform Fee: <strong id="fee-preview">₹5</strong></span>
                </div>

                <button type="submit" class="btn-gold" style="width: 100%;">Create 4-Digit Room</button>
            </form>
        </div>

        <!-- Join Room Box -->
        <div class="glass-card">
            <h3 class="brand-font" style="color: var(--emerald); margin-bottom: 15px;">🔑 Join with 4-Digit Code</h3>
            <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Apne dost se 4-digit code lein aur yahan enter karein:</p>
            <form method="POST">
                <input type="hidden" name="join_room" value="1">
                <input type="text" name="room_code" class="input-field" placeholder="Enter 4-Digit Code (e.g. 5832)" maxlength="4" style="font-size: 24px; text-align: center; letter-spacing: 5px; font-weight: bold;" required>
                <button type="submit" class="btn-gold" style="width: 100%; background: var(--emerald); color: #fff;">Join Match & Play</button>
            </form>
        </div>
    </div>

    <script>
        function toggleQA(val) {
            document.getElementById('qa-fields').style.display = (val === 'qa_challenge') ? 'block' : 'none';
        }
    </script>
</body>
</html>
