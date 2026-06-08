<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

const OWNER = 'jlijano';
const REPO = 'playground';
const TARGET_BRANCH = 'main';
const CACHE_TTL_SECONDS = 1800;

error_reporting(0);
ini_set('display_errors', '0');

$forceRefresh = isset($_GET['refresh']) && $_GET['refresh'] === '1';
$cacheFile = sys_get_temp_dir() . '/playground_connection_health.json';

if (!$forceRefresh && is_readable($cacheFile) && (time() - filemtime($cacheFile) < CACHE_TTL_SECONDS)) {
    $cached = file_get_contents($cacheFile);
    if ($cached !== false) {
        $cachedPayload = json_decode($cached, true);
        if (is_array($cachedPayload)) {
            $cachedPayload['source']['cached'] = true;
            $cachedPayload['served_at'] = gmdate('c');
            $cachedJson = json_encode($cachedPayload, JSON_PRETTY_PRINT);
            if ($cachedJson !== false) {
                echo $cachedJson;
                exit;
            }
        }

        echo $cached;
        exit;
    }
}

function github_get(string $path): array
{
    $url = 'https://api.github.com/repos/' . OWNER . '/' . REPO . $path;
    $headers = [
        'http' => [
            'method' => 'GET',
            'header' => implode("\n", [
                'User-Agent: playground-connection-health',
                'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28',
            ]),
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ];

    $context = stream_context_create($headers);
    $body = @file_get_contents($url, false, $context);
    $status = 0;

    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $line) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $line, $matches)) {
                $status = (int) $matches[1];
                break;
            }
        }
    }

    if ($body === false) {
        return [
            'ok' => false,
            'status' => $status,
            'data' => null,
            'error' => 'Unable to reach GitHub API.',
        ];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'status' => $status,
            'data' => null,
            'error' => 'GitHub API returned an invalid response.',
        ];
    }

    return [
        'ok' => $status >= 200 && $status < 300,
        'status' => $status,
        'data' => $data,
        'error' => $status >= 200 && $status < 300 ? null : 'GitHub API request failed.',
    ];
}

function file_check(string $path): array
{
    $response = github_get('/contents/' . rawurlencode($path) . '?ref=' . rawurlencode(TARGET_BRANCH));

    return [
        'exists' => $response['ok'],
        'status' => $response['status'],
        'sha' => $response['ok'] && isset($response['data']['sha']) ? $response['data']['sha'] : null,
        'url' => $response['ok'] && isset($response['data']['html_url']) ? $response['data']['html_url'] : null,
    ];
}

$repo = github_get('');
$branch = github_get('/branches/' . rawurlencode(TARGET_BRANCH));
$root = github_get('/contents?ref=' . rawurlencode(TARGET_BRANCH));
$index = file_check('index.html');
$connection = file_check('connection.html');

$topLevelFiles = [];
if ($root['ok'] && is_array($root['data'])) {
    foreach ($root['data'] as $item) {
        if (isset($item['name'], $item['type'])) {
            $topLevelFiles[] = [
                'name' => $item['name'],
                'type' => $item['type'],
            ];
        }
    }
}

$defaultBranch = $repo['ok'] && isset($repo['data']['default_branch']) ? $repo['data']['default_branch'] : null;
$repoReachable = $repo['ok'];
$branchReachable = $branch['ok'];
$defaultBranchMatches = $defaultBranch === TARGET_BRANCH;
$allCriticalChecksPass = $repoReachable && $branchReachable && $defaultBranchMatches && $index['exists'] && $connection['exists'];

$payload = [
    'ok' => $allCriticalChecksPass,
    'status' => $allCriticalChecksPass ? 'healthy' : 'degraded',
    'generated_at' => gmdate('c'),
    'cache_ttl_seconds' => CACHE_TTL_SECONDS,
    'source' => [
        'provider' => 'GitHub public REST API',
        'repository' => OWNER . '/' . REPO,
        'branch' => TARGET_BRANCH,
        'cached' => false,
    ],
    'repository' => [
        'reachable' => $repoReachable,
        'full_name' => OWNER . '/' . REPO,
        'visibility' => $repo['ok'] && isset($repo['data']['visibility']) ? $repo['data']['visibility'] : null,
        'default_branch' => $defaultBranch,
        'default_branch_matches' => $defaultBranchMatches,
        'html_url' => $repo['ok'] && isset($repo['data']['html_url']) ? $repo['data']['html_url'] : 'https://github.com/' . OWNER . '/' . REPO,
    ],
    'branch' => [
        'reachable' => $branchReachable,
        'name' => TARGET_BRANCH,
        'commit_sha' => $branch['ok'] && isset($branch['data']['commit']['sha']) ? $branch['data']['commit']['sha'] : null,
    ],
    'files' => [
        'index_html' => $index,
        'connection_html' => $connection,
        'top_level_available' => $root['ok'],
        'top_level' => $topLevelFiles,
    ],
    'server' => [
        'domain' => 'playground.optivex.solutions',
        'hosting' => 'Hostinger Web Hosting',
        'web_root' => 'public_html deployment target',
        'backend' => 'PHP REST API',
        'database' => 'Hostinger MySQL or MariaDB via PDO',
    ],
    'checks' => [
        [
            'id' => 'repo_access',
            'label' => 'Repository access',
            'ok' => $repoReachable,
            'detail' => $repoReachable ? 'GitHub repository metadata is reachable.' : 'GitHub repository metadata could not be reached.',
            'status_code' => $repo['status'],
        ],
        [
            'id' => 'default_branch',
            'label' => 'Default branch',
            'ok' => $defaultBranchMatches,
            'detail' => $defaultBranchMatches ? 'Default branch is main.' : 'Default branch does not match main.',
            'status_code' => $repo['status'],
        ],
        [
            'id' => 'branch_access',
            'label' => 'Branch access',
            'ok' => $branchReachable,
            'detail' => $branchReachable ? 'Target branch is reachable.' : 'Target branch could not be reached.',
            'status_code' => $branch['status'],
        ],
        [
            'id' => 'index_file',
            'label' => 'index.html',
            'ok' => $index['exists'],
            'detail' => $index['exists'] ? 'index.html exists on main.' : 'index.html was not found on main.',
            'status_code' => $index['status'],
        ],
        [
            'id' => 'connection_file',
            'label' => 'connection.html',
            'ok' => $connection['exists'],
            'detail' => $connection['exists'] ? 'connection.html exists on main.' : 'connection.html was not found on main.',
            'status_code' => $connection['status'],
        ],
        [
            'id' => 'top_level_listing',
            'label' => 'Top-level listing',
            'ok' => $root['ok'],
            'detail' => $root['ok'] ? 'Top-level repository files were listed.' : 'Top-level repository listing could not be loaded.',
            'status_code' => $root['status'],
        ],
    ],
];

$json = json_encode($payload, JSON_PRETTY_PRINT);
if ($json === false) {
    http_response_code(500);
    echo '{"ok":false,"status":"error","message":"Unable to encode health response."}';
    exit;
}

@file_put_contents($cacheFile, $json);

if (!$allCriticalChecksPass) {
    http_response_code(502);
}

echo $json;
