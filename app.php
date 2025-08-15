<?php

// 导入核心逻辑
require_once 'utils.php';

// -----------------------------------------------------------------------------
// 网页端路由
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // 显示主页面
    require_once 'templates/index.html';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 处理文件上传表单
    header('Content-Type: application/json');
    $file = $_FILES['file'] ?? null;
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'];
    $newFilename = $_POST['new_filename'] ?? null;

    if (!$file || empty($password)) {
        echo json_encode(['success' => false, 'message' => '请选择文件并输入密码']);
        exit;
    }

    $tempPath = $file['tmp_name'];
    $originalFileName = $file['name'];

    // 为了模拟 Python 的文件保存和处理流程，这里可以直接使用临时文件
    $savePath = __DIR__;

    if ($action === 'encrypt') {
        list($resultPath, $message) = encryptFileLogic($tempPath, $password, $savePath, $newFilename);
    } elseif ($action === 'decrypt') {
        list($resultPath, $message) = decryptFileLogic($tempPath, $password, $savePath);
    } else {
        echo json_encode(['success' => false, 'message' => '未知操作类型']);
        exit;
    }

    if ($resultPath) {
        $downloadUrl = 'download.php?file=' . urlencode(basename($resultPath));
        echo json_encode(['success' => true, 'message' => $message, 'download_url' => $downloadUrl]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}

// -----------------------------------------------------------------------------
// 网页端下载路由
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['file'])) {
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . basename($_GET['file']);
    if (file_exists($filePath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        // 下载完成后，删除临时文件以保持环境整洁
        unlink($filePath);
        exit;
    } else {
        http_response_code(404);
        echo "文件不存在";
        exit;
    }
}

// -----------------------------------------------------------------------------
// 生成密钥路由
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' && !isset($_POST['action'])) {
    header('Content-Type: application/json');
    echo json_encode(generateRandomKey());
    exit;
}

http_response_code(404);
echo 'Not Found';
?>