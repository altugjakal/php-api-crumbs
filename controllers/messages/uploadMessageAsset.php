<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

include('connector.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function sendJSONResponse($state, $message, $extraData = []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'state' => $state,
        'message' => $message
    ], $extraData));
    exit;
}

if (empty($_FILES['asset']['name'][0])) {
    sendJSONResponse('error', 'No file was uploaded.');
}

$directory = $_SERVER["DOCUMENT_ROOT"] . "/message-assets/";

if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
    sendJSONResponse('error', 'Upload directory initialization failed.');
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'docx', 'zip', 'txt'];
$maxFileSize = 10 * 1024 * 1024;
$uploadedFiles = [];

foreach ($_FILES['asset']['name'] as $key => $name) {
    $error = $_FILES['asset']['error'][$key];
    $size = $_FILES['asset']['size'][$key];
    $tmpName = $_FILES['asset']['tmp_name'][$key];

    if ($error !== UPLOAD_ERR_OK) {
        sendJSONResponse('error', "File upload error code: {$error}.");
    }

    if ($size > $maxFileSize) {
        sendJSONResponse('error', 'Each file must be smaller than 10MB.');
    }

    $fileExtension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($fileExtension, $allowedExtensions, true)) {
        sendJSONResponse('error', "File format '.{$fileExtension}' is not supported.");
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpName);
    if (strpos($mimeType, 'text/php') !== false || strpos($mimeType, 'application/x-php') !== false) {
        sendJSONResponse('error', 'Invalid file content signature detected.');
    }

    $secureName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
    
    $uploadedFiles[] = [
        'tmp' => $tmpName,
        'dest' => $directory . $secureName,
        'original_name' => basename($name),
        'secure_name' => $secureName
    ];
}

$processedFiles = [];
foreach ($uploadedFiles as $file) {
    if (move_uploaded_file($file['tmp'], $file['dest'])) {
        $processedFiles[] = [
            'original' => $file['original_name'],
            'saved_as' => $file['secure_name']
        ];
    } else {
        foreach ($processedFiles as $failedFallback) {
            @unlink($directory . $failedFallback['saved_as']);
        }
        sendJSONResponse('error', 'File transfer failed mid-process.');
    }
}

sendJSONResponse('success', 'All files transferred successfully.', ['files' => $processedFiles]);
