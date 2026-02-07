<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$config = require __DIR__ . '/config.php';
$panelId = trim((string)($_GET['panel_id'] ?? ''));

if ($panelId === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing panel_id']);
    exit;
}

$cacheFile = $config['cache_dir'] . '/' . $panelId . '.json';
if (!is_file($cacheFile)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Panel not found']);
    exit;
}

$cache = json_decode((string)file_get_contents($cacheFile), true);
$now = time();
$ttl = (int)$config['cache_ttl'];

if (!is_array($cache)) {
    $cache = ['updated' => 0, 'data' => null];
}

$lastUpdated = (int)($cache['updated'] ?? 0);
if ($now - $lastUpdated < $ttl && isset($cache['data'])) {
    echo json_encode(['status' => 'ok', 'cached' => true, 'data' => $cache['data'], 'updated_at' => $lastUpdated]);
    exit;
}

$matchUrl = (string)($cache['match_url'] ?? '');
if ($matchUrl === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Match URL missing']);
    exit;
}

$ch = curl_init($matchUrl);
if ($ch === false) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to initialize cURL']);
    exit;
}

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 12,
    CURLOPT_USERAGENT => $config['user_agent'],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
]);

$html = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($html === false || $httpCode >= 400) {
    $fallbackData = $cache['data'] ?? null;
    echo json_encode([
        'status' => 'ok',
        'cached' => true,
        'stale' => true,
        'message' => $curlError ?: 'Failed to fetch live data',
        'data' => $fallbackData,
        'updated_at' => $lastUpdated,
    ]);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html);
$xpath = new DOMXPath($dom);

$xpathText = function (array $queries) use ($xpath): string {
    foreach ($queries as $query) {
        $nodes = $xpath->query($query);
        if ($nodes && $nodes->length > 0) {
            $text = trim($nodes->item(0)->textContent);
            if ($text !== '') {
                return $text;
            }
        }
    }
    return '';
};

$xpathAll = function (array $queries) use ($xpath): array {
    foreach ($queries as $query) {
        $nodes = $xpath->query($query);
        if ($nodes && $nodes->length > 0) {
            $items = [];
            foreach ($nodes as $node) {
                $text = trim($node->textContent);
                if ($text !== '') {
                    $items[] = $text;
                }
            }
            if ($items) {
                return $items;
            }
        }
    }
    return [];
};

$teamNames = $xpathAll([
    "//div[contains(@class,'team-name')]/text()",
    "//span[contains(@class,'team-name')]/text()",
]);

$scores = $xpathAll([
    "//div[contains(@class,'score') and contains(@class,'team')]//text()",
    "//span[contains(@class,'score') and contains(@class,'team')]//text()",
]);

$overs = $xpathAll([
    "//span[contains(@class,'overs')]/text()",
    "//div[contains(@class,'overs')]/text()",
]);

$statusText = $xpathText([
    "//div[contains(@class,'match-status')]/text()",
    "//span[contains(@class,'match-status')]/text()",
    "//div[contains(@class,'status')]/text()",
]);

$crr = $xpathText([
    "//span[contains(text(),'CRR')]/following-sibling::span[1]",
    "//div[contains(@class,'crr')]/text()",
]);

$rrr = $xpathText([
    "//span[contains(text(),'RRR')]/following-sibling::span[1]",
    "//div[contains(@class,'rrr')]/text()",
]);

$partnership = $xpathText([
    "//span[contains(text(),'Partnership')]/following-sibling::span[1]",
    "//div[contains(@class,'partnership')]/text()",
]);

$required = $xpathText([
    "//span[contains(text(),'Need')]/text()",
    "//div[contains(@class,'required')]/text()",
]);

$batsmen = [];
$batsmanNodes = $xpath->query("//table[contains(@class,'batsman')]//tr");
if ($batsmanNodes && $batsmanNodes->length > 0) {
    foreach ($batsmanNodes as $row) {
        $cells = $row->getElementsByTagName('td');
        if ($cells->length >= 6) {
            $batsmen[] = [
                'name' => trim($cells->item(0)->textContent),
                'runs' => trim($cells->item(2)->textContent),
                'balls' => trim($cells->item(3)->textContent),
                'fours' => trim($cells->item(4)->textContent),
                'sixes' => trim($cells->item(5)->textContent),
                'sr' => trim($cells->item(6)->textContent),
            ];
        }
        if (count($batsmen) >= 2) {
            break;
        }
    }
}

$bowler = [
    'name' => $xpathText(["//div[contains(@class,'bowler-name')]/text()", "//span[contains(@class,'bowler-name')]/text()"]) ?: 'Bowler',
    'figures' => $xpathText(["//div[contains(@class,'bowler-fig')]/text()", "//span[contains(@class,'bowler-fig')]/text()"]) ?: '0-0 (0.0)',
    'economy' => $xpathText(["//div[contains(@class,'bowler-eco')]/text()", "//span[contains(@class,'bowler-eco')]/text()"]) ?: '0.0',
];

$balls = $xpathAll([
    "//div[contains(@class,'ball-by-ball')]//span",
    "//ul[contains(@class,'balls')]//li",
]);

$data = [
    'teams' => [
        [
            'name' => $teamNames[0] ?? 'Team A',
            'score' => $scores[0] ?? '0/0',
            'overs' => $overs[0] ?? '0.0',
        ],
        [
            'name' => $teamNames[1] ?? 'Team B',
            'score' => $scores[1] ?? '0/0',
            'overs' => $overs[1] ?? '0.0',
        ],
    ],
    'status' => $statusText ?: 'Updating…',
    'crr' => $crr ?: '0.00',
    'rrr' => $rrr ?: '0.00',
    'partnership' => $partnership ?: '0 (0)',
    'required' => $required ?: '0 runs (0 balls)',
    'batsmen' => $batsmen,
    'bowler' => $bowler,
    'balls' => array_slice($balls, 0, 12),
];

$cache['data'] = $data;
$cache['updated'] = $now;
file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));

echo json_encode(['status' => 'ok', 'cached' => false, 'data' => $data, 'updated_at' => $now]);
