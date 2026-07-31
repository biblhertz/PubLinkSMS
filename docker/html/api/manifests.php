<?php
/**
 * IIIF Generator publish endpoint.
 *
 * Accepts the format produced by the browser-based IIIF Manifest Generator:
 *   POST /api/manifests
 *   Content-Type: application/json
 *   Authorization: Bearer {api_key}
 *   Body: raw IIIF Presentation 3 manifest JSON
 *
 * Derives series/volume/filename by stripping the configured FILE_STORE_URL
 * prefix from the manifest's `id` field, then delegates to
 * ApiController::storeAsManifest() and returns { "url": "..." }.
 *
 * Authentication uses the same api_key table as the rest of the API,
 * validated against the putManifest endpoint.
 */

require 'vendor/autoload.php';

use Biblhertz\Manifest_Server\api\ApiController;
use Biblhertz\Manifest_Server\Config;
use Biblhertz\Manifest_Server\om\ApiKey;
use Biblhertz\Manifest_Server\utilities\PDODatabase;

Config::setup();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authenticate via Bearer token
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
    http_response_code(401);
    echo json_encode(['error' => 'Bearer token required']);
    exit;
}

$rawKey = $m[1];
$authDb = new PDODatabase();
if (!ApiKey::validate($authDb, $rawKey, 'putManifest')) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or inactive API key']);
    exit;
}

// Read and parse manifest body
$body = file_get_contents('php://input');
if (!$body) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    exit;
}

$manifest = json_decode($body, true);
if (!$manifest || !isset($manifest['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid manifest JSON or missing id field']);
    exit;
}

// Derive series/volume/filename from manifest id.
// Expected: {FILE_STORE_URL}/{series}/{volume}/{filename}
// Strip protocol for comparison so http:// and https:// both match.
$fileStoreBase = rtrim(Config::$FILE_STORE_URL, '/');
$manifestId    = rtrim($manifest['id'], '/');

$baseNorm = preg_replace('#^https?://#', '', $fileStoreBase);
$idNorm   = preg_replace('#^https?://#', '', $manifestId);

if (!str_starts_with($idNorm, $baseNorm . '/')) {
    http_response_code(400);
    echo json_encode(['error' => 'Manifest id does not match configured FILE_STORE_URL: ' . $fileStoreBase]);
    exit;
}

$relativePath = ltrim(substr($idNorm, strlen($baseNorm)), '/');
$parts = explode('/', $relativePath, 3);

if (count($parts) < 3) {
    http_response_code(400);
    echo json_encode(['error' => 'Manifest id must follow {base}/series/volume/filename.json']);
    exit;
}

[$series, $volume, $manifestName] = $parts;

$decoded = [
    'series'           => $series,
    'volume'           => $volume,
    'manifest_name'    => $manifestName,
    'manifest'         => $body,
    'ignore_overwrite' => true,
    'ignore_mismatch'  => true,
];

try {
    $result = ApiController::storeAsManifest($decoded);
    $url    = str_replace('http://', 'https://', $result['url']);
    echo json_encode(['url' => $url, 'message' => $result['message']]);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
