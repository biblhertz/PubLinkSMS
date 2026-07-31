<?php
require 'vendor/autoload.php';

use Biblhertz\Manifest_Server\api\ApiController;
use Biblhertz\Manifest_Server\Config;
use Biblhertz\Manifest_Server\om\ApiKey;
use Biblhertz\Manifest_Server\utilities\PDODatabase;

Config::setup();

$resource = $_REQUEST['resource'] ?? null;

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Route validation
if (!$resource || !in_array($resource, ApiController::$allowedEndPoints)) {
    header("HTTP/1.1 404 Not Found");
    echo json_encode(['error' => 'Resource not found']);
    exit;
}

// ── Authentication ─────────────────────────────────────────────────────────────
// Prefer X-API-Key header; fall back to HTTP Basic Auth for compatibility.

$authenticated = false;

$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($providedKey !== '') {
    $authDb        = new PDODatabase();
    $authenticated = ApiKey::validate($authDb, $providedKey, $resource);
}

if (!$authenticated) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        header('WWW-Authenticate: Basic realm="API Authentication"');
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }

    $authenticated = $_SERVER['PHP_AUTH_USER'] === Config::$API_USERNAME
                  && $_SERVER['PHP_AUTH_PW']   === Config::$API_PASSWORD;

    if (!$authenticated) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode(['error' => 'Invalid credentials']);
        exit;
    }
}

// ── Dispatch ──────────────────────────────────────────────────────────────────
$controller = new ApiController();
$controller->{$resource . 'Action'}();
exit;
?>
