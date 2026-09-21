<?php
// arena.php
require_once 'config.php';
$user = get_logged_in_user($pdo);

if (!$user) {
    header("Location: auth.php");
    exit;
}

$game_type = $_GET['game'] ?? 'quiz';
$match_id = null;
$error = '';

// Check balance
if ($user['wallet_balance'] < 10) {
    die("<div style='background:#07090e;color:#fff;text-align:center;padding:50px;'><h2>Insufficient Balance! Need at least ₹10 to play.</h2><br><a href='index.php' style='color:#ffb800;'>Go Back</a></div>");
}

// Handle Matchmaking & Escrow Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_win') {
    $matchId = $_POST['match_id'];
    $winnerScore = (int)$_POST['score'];

    // Settle Match: Add ₹15 to winner wallet
    $pdo->beginTransaction();
    try {
        $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + 15.00 WHERE id = ?")->execute([$user['id']]);
        $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, 15.00, 'match_win', 'Match Win Prize (Pool ₹20 - ₹5 Commission)')")->execute([$user['id']]);
        $pdo->prepare("UPDATE matches SET status = 'completed', winner_id = ? WHERE id = ?")->execute([$user['id'], $matchId]);
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => '₹15 credited to your wallet!']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Create or Find match room and deduct ₹10
$stmt = $pdo->prepare("SELECT * FROM matches WHERE game_type = ? AND status = 'waiting' AND player1_id != ? LIMIT 1");
$stmt->execute([$game_type, $user['id']]);
$existingMatch = $stmt->fetch();

$pdo->beginTransaction();
try {
    if ($existingMatch) {
        // Join existing match as player 2
        $match_id = $existingMatch['id'];
        $pdo->prepare("UPDATE matches SET player2_id = ?, status = 'in_progress' WHERE id = ?")->execute([$user['id'], $match_id]);
    } else {
        // Create new match as player 1
        $pdo->prepare("INSERT INTO matches (game_type, player1_id, bet_amount, win_amount, platform_commission, status) VALUES (?, ?, 10.00, 15.00, 5.00, 'waiting')")->execute([$game_type, $user['id']]);
        $match_id = $pdo->lastInsertId();
    }

    // Deduct ₹10 Entry fee
    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - 10.00 WHERE id = ?")->execute([$user['id']]);
    $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, -10.00, 'match_fee', 'Match Entry Fee')")->execute([$user['id']]);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    die("Match creation failed: " . $e->getMessage());
}

// Fetch Questions for Quiz
$questions = $pdo->query("SELECT * FROM quiz_questions ORDER BY RAND() LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Match Arena #<?= $match_id ?> | CyberPlay</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .arena-box { max-width: 600px; margin: 40px auto; padding: 35px; }
        .option-btn { width: 100%; padding: 14px; margin: 8px 0; background: #0e1320; border: 1px solid var(--border-color); color: #fff; border-radius: 12px; cursor: pointer; text-align: left; font-size: 16px; transition: 0.2s; }
        .option-btn:hover { border-color: var(--gold-neon); background: rgba(255, 184, 0, 0.08); }
        .timer-badge { font-size: 20px; font-weight: bold; color: var(--gold-neon); }
    </style>
</head>
<body>
    <div class="navbar">
        <h3 class="brand-font" style="color: var(--gold-neon);">⚡ 1v1 LIVE ARENA</h3>
        <div class="wallet-badge">Pool: ₹20 (Win: ₹15)</div>
    </div>

    <div class="glass-card arena-box" id="game-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <span style="color: var(--text-muted); font-size: 14px;">Match ID: #<?= $match_id ?></span>
            <span class="timer-badge" id="timer">⏳ 10s</span>
        </div>

        <h3 id="question-text" style="min-height: 50px; font-size: 18px; margin-bottom: 20px;">Loading question...</h3>
        <div id="options-container"></div>
    </div>

    <div class="glass-card arena-box" id="result-box" style="display: none; text-align: center;">
        <h2 class="brand-font" style="color: var(--cyber-emerald); font-size: 26px;">🎉 VICTORY!</h2>
        <p style="margin: 15px 0; font-size: 18px;">You won <strong style="color: var(--gold-neon);">₹15.00</strong>!</p>
        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">Platform Commission (₹5.00) deducted.</p>
        <a href="index.php" class="btn-gold">Back to Lobby</a>
    </div>

    <script>
        const questions = <?= json_encode($questions) ?>;
        const matchId = <?= $match_id ?>;
        let currentIndex = 0;
        let score = 0;
        let timeLeft = 10;
        let timerInterval;

        function loadQuestion() {
            if (currentIndex >= questions.length) {
                endGame();
                return;
            }

            timeLeft = 10;
            document.getElementById('timer').innerText = `⏳ ${timeLeft}s`;
            clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                timeLeft--;
                document.getElementById('timer').innerText = `⏳ ${timeLeft}s`;
                if (timeLeft <= 0) {
                    currentIndex++;
                    loadQuestion();
                }
            }, 1000);

            const q = questions[currentIndex];
            document.getElementById('question-text').innerText = `Q${currentIndex + 1}. ${q.question}`;
            const opts = [
                { key: 'A', text: q.option_a },
                { key: 'B', text: q.option_b },
                { key: 'C', text: q.option_c },
                { key: 'D', text: q.option_d }
            ];

            const container = document.getElementById('options-container');
            container.innerHTML = '';
            opts.forEach(opt => {
                const btn = document.createElement('button');
                btn.className = 'option-btn';
                btn.innerText = `${opt.key}. ${opt.text}`;
                btn.onclick = () => {
                    if (opt.key === q.correct_option) score += 10;
                    currentIndex++;
                    loadQuestion();
                };
                container.appendChild(btn);
            });
        }

        function endGame() {
            clearInterval(timerInterval);
            document.getElementById('game-container').style.display = 'none';
            document.getElementById('result-box').style.display = 'block';

            // Claim Winnings via AJAX
            const formData = new FormData();
            formData.append('action', 'claim_win');
            formData.append('match_id', matchId);
            formData.append('score', score);

            fetch('arena.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => console.log(data));
        }

        loadQuestion();
    </script>
</body>
</html>