<?php


require_once __DIR__ . '/../utils.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$file = $_FILES['file'] ?? null;
$password = $_POST['password'] ?? '';
$action = basename($_SERVER['REQUEST_URI']); // 根据 URL 路径判断是加密还是解密

if (!$file || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '缺少必要的参数: file, password']);
    exit;
}

$tempPath = $file['tmp_name'];
$savePath = __DIR__ . '/../';

if ($action === 'encrypt') {
    $newFilename = $_POST['new_filename'] ?? null;
    list($resultPath, $message) = encryptFileLogic($tempPath, $password, $savePath, $newFilename);
} elseif ($action === 'decrypt') {
    list($resultPath, $message) = decryptFileLogic($tempPath, $password, $savePath);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'API Endpoint Not Found']);
    exit;
}

if ($resultPath) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($resultPath) . '"');
    header('Content-Length: ' . filesize($resultPath));
    readfile($resultPath);
    unlink($resultPath); 
    exit;
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

?>
