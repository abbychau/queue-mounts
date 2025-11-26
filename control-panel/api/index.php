<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
?>
<?php
if(!isset($_GET['action'])) {
    echo json_encode(array('error' => 'No action provided'));
    exit;
}

$action = $_GET['action'];
if($action == 'stats') {
    $stat = file_get_contents("http://localhost:18080");
    echo $stat;
    exit;
} else if ($action == 'connlogs') {
    $clientId = isset($_GET['client_id']) ? urlencode($_GET['client_id']) : '';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
    if ($clientId === '') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'client_id is required']);
        exit;
    }
    // The conn-logs API runs on the aux port (http+1)
    $url = "http://localhost:18081/api/v1/mqtt/conn-logs?client_id={$clientId}&limit={$limit}";
    $resp = @file_get_contents($url);
    if ($resp === FALSE) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Upstream unavailable']);
        exit;
    }
    echo $resp;
    exit;
} else if ($action == 'clients') {
    $url = "http://localhost:18081/api/v1/mqtt/clients";
    $resp = @file_get_contents($url);
    if ($resp === FALSE) {
        http_response_code(502);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Upstream unavailable']);
        exit;
    }
    echo $resp;
    exit;
} else if($action == 'post') {
    echo json_encode(array('message' => 'You requested the post action'));
} else {
    echo json_encode(array('error' => 'Invalid action provided'));
}