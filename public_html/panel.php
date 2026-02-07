<?php

declare(strict_types=1);

$panelId = trim((string)($_GET['panel_id'] ?? ''));
$theme = trim((string)($_GET['theme'] ?? 'default'));

if ($panelId === '') {
    http_response_code(400);
    echo 'Missing panel_id.';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Score Panel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="panel-body theme-<?php echo htmlspecialchars($theme, ENT_QUOTES); ?>" data-panel-id="<?php echo htmlspecialchars($panelId, ENT_QUOTES); ?>">
    <div class="score-panel">
        <div class="panel-header">
            <div class="team-block" id="team-a">
                <div class="team-flag"></div>
                <div class="team-info">
                    <span class="team-name">Team A</span>
                    <span class="team-score">0/0</span>
                    <span class="team-overs">0.0</span>
                </div>
            </div>
            <div class="status-box" id="match-status">Updating…</div>
            <div class="team-block" id="team-b">
                <div class="team-flag"></div>
                <div class="team-info">
                    <span class="team-name">Team B</span>
                    <span class="team-score">0/0</span>
                    <span class="team-overs">0.0</span>
                </div>
            </div>
        </div>

        <div class="metrics-strip">
            <div class="metric">
                <span class="metric-label">CRR</span>
                <span class="metric-value" id="crr">0.00</span>
            </div>
            <div class="metric">
                <span class="metric-label">RRR</span>
                <span class="metric-value" id="rrr">0.00</span>
            </div>
            <div class="metric">
                <span class="metric-label">Partnership</span>
                <span class="metric-value" id="partnership">0 (0)</span>
            </div>
            <div class="metric trophy">
                <span class="metric-label">Required</span>
                <span class="metric-value" id="required">0 runs (0 balls)</span>
            </div>
        </div>

        <div class="panel-center">
            <div class="center-overlay"></div>
            <div class="player-cards">
                <div class="player-card" id="batsman-1">
                    <span class="card-label">BATSMAN</span>
                    <div class="player-row">
                        <img class="player-avatar" src="assets/images/player.svg" alt="Batsman">
                        <div>
                            <div class="player-name">Batsman 1</div>
                            <div class="player-score">0 (0)</div>
                        </div>
                    </div>
                    <div class="player-stats">
                        <span>4s: <strong class="stat-fours">0</strong></span>
                        <span>6s: <strong class="stat-sixes">0</strong></span>
                        <span>SR: <strong class="stat-sr">0.0</strong></span>
                    </div>
                </div>
                <div class="player-card" id="batsman-2">
                    <span class="card-label">BATSMAN</span>
                    <div class="player-row">
                        <img class="player-avatar" src="assets/images/player.svg" alt="Batsman">
                        <div>
                            <div class="player-name">Batsman 2</div>
                            <div class="player-score">0 (0)</div>
                        </div>
                    </div>
                    <div class="player-stats">
                        <span>4s: <strong class="stat-fours">0</strong></span>
                        <span>6s: <strong class="stat-sixes">0</strong></span>
                        <span>SR: <strong class="stat-sr">0.0</strong></span>
                    </div>
                </div>
                <div class="player-card" id="bowler">
                    <span class="card-label">BOWLER</span>
                    <div class="player-row">
                        <img class="player-avatar" src="assets/images/player.svg" alt="Bowler">
                        <div>
                            <div class="player-name">Bowler</div>
                            <div class="player-score">0-0 (0.0)</div>
                        </div>
                    </div>
                    <div class="player-stats">
                        <span>ECO: <strong class="stat-economy">0.0</strong></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="balls-strip" id="balls-strip">
            <span class="ball">0</span>
            <span class="ball">0</span>
            <span class="ball">0</span>
        </div>

        <footer class="panel-footer">
            Live data sourced from publicly available sources. Not affiliated with Crex.
        </footer>
    </div>

    <script src="assets/js/panel.js"></script>
</body>
</html>
