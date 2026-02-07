<?php

declare(strict_types=1);

$config = require __DIR__ . '/config.php';
$panelId = null;
$error = null;
$matchUrl = '';
$theme = 'default';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matchUrl = trim((string)($_POST['match_url'] ?? ''));
    $theme = trim((string)($_POST['theme'] ?? 'default'));

    if ($matchUrl === '' || !filter_var($matchUrl, FILTER_VALIDATE_URL)) {
        $error = 'Please enter a valid Crex live match URL.';
    } else {
        $panelId = bin2hex(random_bytes(4));
        $cacheFile = $config['cache_dir'] . '/' . $panelId . '.json';
        $payload = [
            'panel_id' => $panelId,
            'match_url' => $matchUrl,
            'theme' => $theme,
            'updated' => 0,
            'data' => null,
        ];
        file_put_contents($cacheFile, json_encode($payload, JSON_PRETTY_PRINT));
    }
}

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http');
$baseUrl .= '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$panelUrl = $panelId ? $baseUrl . dirname($_SERVER['REQUEST_URI']) . '/panel.php?panel_id=' . urlencode($panelId) . '&theme=' . urlencode($theme) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cricket Live Panel Generator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>Live Cricket Panel Generator</h1>
            <p>Create broadcast-ready OBS score panels by pasting a Crex live match link.</p>
        </header>

        <form class="dashboard-form" method="post" action="">
            <label for="match_url">Crex Live Match URL</label>
            <input type="url" id="match_url" name="match_url" placeholder="https://crex.live/scorecard/" value="<?php echo htmlspecialchars($matchUrl, ENT_QUOTES); ?>" required>

            <label for="theme">Theme</label>
            <select id="theme" name="theme">
                <option value="default" <?php echo $theme === 'default' ? 'selected' : ''; ?>>Default Broadcast</option>
            </select>

            <?php if ($error): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
            <?php endif; ?>

            <button type="submit" class="primary-btn">Generate Panel</button>
        </form>

        <?php if ($panelId): ?>
            <section class="result-card">
                <h2>Your Panel is Ready</h2>
                <div class="result-row">
                    <span>Panel ID</span>
                    <strong><?php echo htmlspecialchars($panelId, ENT_QUOTES); ?></strong>
                </div>
                <div class="result-row">
                    <span>OBS Browser Source URL</span>
                    <input type="text" readonly value="<?php echo htmlspecialchars($panelUrl, ENT_QUOTES); ?>" onclick="this.select();">
                </div>
                <div class="result-actions">
                    <button type="button" class="secondary-btn" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($panelUrl, ENT_QUOTES); ?>');">Copy Panel Link</button>
                    <a class="secondary-btn" href="<?php echo htmlspecialchars($panelUrl, ENT_QUOTES); ?>" target="_blank" rel="noopener">Open Panel</a>
                </div>
            </section>
        <?php endif; ?>

        <footer class="dashboard-footer">
            Live data sourced from publicly available sources. Not affiliated with Crex.
        </footer>
    </div>
</body>
</html>
