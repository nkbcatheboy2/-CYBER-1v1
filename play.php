<?php
// play.php
require_once 'config.php';
$user = get_user($pdo);
if (!$user) { header("Location: auth.php"); exit; }

$code = $_GET['code'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM matches WHERE room_code = ?");
$stmt->execute([$code]);
$match = $stmt->fetch();

if (!$match) { die("Room not found! <a href='index.php'>Go Back</a>"); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Room #<?= $code ?> | Live Game</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .board-box { max-width: 650px; margin: 30px auto; text-align: center; }
        .track { display: flex; gap: 4px; justify-content: center; margin: 20px 0; }
        .step { width: 18px; height: 35px; background: #161e31; border: 1px solid var(--border); border-radius: 4px; }
        .step.p1-active { background: var(--gold); box-shadow: 0 0 10px var(--gold); }
        .step.p2-active { background: var(--emerald); box-shadow: 0 0 10px var(--emerald); }
        .dice-display { font-size: 55px; margin: 15px 0; cursor: pointer; user-select: none; }
    </style>
</head>
<body>
    <div class="navbar">
        <h3 class="brand-font" style="color: var(--gold);">ROOM CODE: <?= $code ?></h3>
        <div class="wallet-badge">Prize Pool: ₹<?= $match['bet_amount'] * 2 ?> (Winner Gets: ₹<?= $match['win_amount'] ?>)</div>
    </div>

    <div class="glass-card board-box">
        <!-- Waiting Screen -->
        <div id="waiting-screen" style="display: none;">
            <h2 class="brand-font" style="color: var(--gold);">Waiting for Player 2...</h2>
            <p style="color: var(--text-muted); margin: 15px 0;">Share this 4-digit code with your friend:</p>
            <div style="font-size: 40px; font-weight: 900; letter-spacing: 8px; color: var(--emerald); background: rgba(0,0,0,0.4); padding: 15px; border-radius: 12px; display: inline-block;"><?= $code ?></div>
        </div>

        <!-- 1v1 Ludo Game Screen -->
        <div id="ludo-screen" style="display: none;">
            <h3 class="brand-font" id="turn-indicator">Starting...</h3>
            <div style="margin: 20px 0; text-align: left;">
                <p>🟡 <strong>Player 1 (<?= htmlspecialchars($match['player1_id'] == $user['id'] ? 'You' : 'Opponent') ?>):</strong> <span id="p1-score">0</span> / 30 Steps</p>
                <div class="track" id="p1-track"></div>

                <p style="margin-top: 15px;">🟢 <strong>Player 2 (<?= htmlspecialchars($match['player2_id'] == $user['id'] ? 'You' : 'Opponent') ?>):</strong> <span id="p2-score">0</span> / 30 Steps</p>
                <div class="track" id="p2-track"></div>
            </div>

            <div class="dice-display" id="dice-btn" onclick="rollDice()">🎲</div>
            <p id="dice-hint" style="color: var(--text-muted); font-size: 14px;">Click Dice to Roll</p>
        </div>

        <!-- 1v1 Q&A Challenge Screen -->
        <div id="qa-screen" style="display: none;">
            <h3 class="brand-font" style="color: var(--gold); margin-bottom: 15px;">❓ Q&A 1v1 Challenge</h3>
            <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <p style="font-size: 18px; font-weight: bold;"><?= htmlspecialchars($match['custom_question'] ?? '') ?></p>
            </div>
            
            <?php if ($match['player2_id'] == $user['id']): ?>
                <!-- Challenger Answer Input -->
                <input type="text" id="challenger-answer" class="input-field" placeholder="Enter your answer here">
                <button onclick="submitQA()" class="btn-gold" style="width: 100%;">Submit Answer (Win ₹<?= $match['win_amount'] ?>)</button>
            <?php else: ?>
                <p style="color: var(--text-muted);">Opponent is answering your question. If they fail, you win ₹<?= $match['win_amount'] ?>!</p>
            <?php endif; ?>
        </div>

        <!-- Victory Modal -->
        <div id="winner-modal" style="display: none;">
            <h1 style="font-size: 50px;">🏆</h1>
            <h2 class="brand-font" id="winner-text" style="color: var(--gold); margin: 15px 0;">Game Finished</h2>
            <a href="index.php" class="btn-gold">Back to Lobby</a>
        </div>
    </div>

    <script>
        const roomCode = '<?= $code ?>';
        const gameType = '<?= $match['game_type'] ?>';
        let myId = <?= $user['id'] ?>;
        let isMyTurn = false;

        // Render Ludo Tracks (30 steps)
        function renderTracks() {
            const p1 = document.getElementById('p1-track');
            const p2 = document.getElementById('p2-track');
            p1.innerHTML = ''; p2.innerHTML = '';
            for(let i=1; i<=30; i++) {
                p1.innerHTML += `<div class="step" id="p1-s${i}"></div>`;
                p2.innerHTML += `<div class="step" id="p2-s${i}"></div>`;
            }
        }
        renderTracks();

        function sync() {
            fetch(`game_sync.php?code=${roomCode}&action=get_state`)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'waiting') {
                        document.getElementById('waiting-screen').style.display = 'block';
                        document.getElementById('ludo-screen').style.display = 'none';
                        document.getElementById('qa-screen').style.display = 'none';
                    } else if (data.status === 'in_progress') {
                        document.getElementById('waiting-screen').style.display = 'none';
                        
                        if (gameType === 'ludo') {
                            document.getElementById('ludo-screen').style.display = 'block';
                            isMyTurn = (data.current_turn == myId);
                            document.getElementById('turn-indicator').innerText = isMyTurn ? "🟢 YOUR TURN TO ROLL!" : "⏳ Opponent's Turn...";
                            document.getElementById('dice-hint').innerText = isMyTurn ? "Click the Dice!" : "Waiting for opponent move...";
                            
                            // Update positions
                            document.getElementById('p1-score').innerText = data.game_state.p1_pos;
                            document.getElementById('p2-score').innerText = data.game_state.p2_pos;

                            // Highlight tracks
                            for(let i=1; i<=30; i++) {
                                document.getElementById(`p1-s${i}`).className = 'step ' + (i <= data.game_state.p1_pos ? 'p1-active' : '');
                                document.getElementById(`p2-s${i}`).className = 'step ' + (i <= data.game_state.p2_pos ? 'p2-active' : '');
                            }
                        } else if (gameType === 'qa_challenge') {
                            document.getElementById('qa-screen').style.display = 'block';
                        }
                    } else if (data.status === 'completed') {
                        document.getElementById('waiting-screen').style.display = 'none';
                        document.getElementById('ludo-screen').style.display = 'none';
                        document.getElementById('qa-screen').style.display = 'none';
                        document.getElementById('winner-modal').style.display = 'block';
                        
                        if (data.winner_id == myId) {
                            document.getElementById('winner-text').innerText = `🎉 YOU WON ₹${data.win_amount}!`;
                        } else {
                            document.getElementById('winner-text').innerText = `💔 Opponent Won! Better luck next time.`;
                        }
                    }
                });
        }

        function rollDice() {
            if (!isMyTurn) return;
            fetch(`game_sync.php?code=${roomCode}&action=ludo_roll`, { method: 'POST' })
                .then(r => r.json())
                .then(d => {
                    if (d.dice) document.getElementById('dice-btn').innerText = ['⚀','⚁','⚂','⚃','⚄','⚅'][d.dice - 1];
                    sync();
                });
        }

        function submitQA() {
            const ans = document.getElementById('challenger-answer').value;
            const fd = new FormData();
            fd.append('action', 'submit_qa_answer');
            fd.append('answer', ans);
            fetch(`game_sync.php?code=${roomCode}`, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(() => sync());
        }

        setInterval(sync, 1000); // 1-second auto sync for live multiplayer
        sync();
    </script>
</body>
</html>