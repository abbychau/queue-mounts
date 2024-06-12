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
} else if($action == 'post') {
    echo json_encode(array('message' => 'You requested the post action'));
} else {
    echo json_encode(array('error' => 'Invalid action provided'));
}