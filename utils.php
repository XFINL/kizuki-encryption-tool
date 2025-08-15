<?php

function encryptFileLogic(string $filePath, string $password, string $savePath, ?string $newFilename = null)
{
    try {
        $key = str_pad(substr($password, 0, 16), 16, "\0");
        $plaintext = file_get_contents($filePath);

        $cipher = 'aes-128-eax';
        $nonce = openssl_random_pseudo_bytes(16);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

        if ($ciphertext === false) {
            throw new Exception("加密失败：openssl_encrypt 错误。");
        }

        $originalFilename = basename($filePath);
        $originalFilenameBytes = $originalFilename;
        $filenameLenBytes = pack('N', strlen($originalFilenameBytes));

        if ($newFilename) {
            $baseName = pathinfo($newFilename, PATHINFO_FILENAME);
            $finalFilename = $baseName . ".kz";
            $encryptedFilepath = $savePath . DIRECTORY_SEPARATOR . $finalFilename;
        } else {
            $encryptedFilepath = $savePath . DIRECTORY_SEPARATOR . $originalFilename . ".kz";
        }
        
        if (file_exists($encryptedFilepath)) {
            return [null, "文件 '" . basename($encryptedFilepath) . "' 已存在，请先处理旧文件。"];
        }

        $data = $filenameLenBytes . $originalFilenameBytes . $nonce . $tag . $ciphertext;
        file_put_contents($encryptedFilepath, $data);

        return [$encryptedFilepath, "文件加密成功！"];

    } catch (Exception $e) {
        return [null, "加密失败：" . $e->getMessage()];
    }
}

function decryptFileLogic(string $filePath, string $password, string $savePath)
{
    try {
        $key = str_pad(substr($password, 0, 16), 16, "\0");
        $data = file_get_contents($filePath);

        $filenameLen = unpack('N', substr($data, 0, 4))[1];
        $originalFilename = substr($data, 4, $filenameLen);
        $nonce = substr($data, 4 + $filenameLen, 16);
        $tag = substr($data, 4 + $filenameLen + 16, 16);
        $ciphertext = substr($data, 4 + $filenameLen + 32);

        $cipher = 'aes-128-eax';
        $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $nonce, $tag);

        if ($plaintext === false) {
            throw new Exception("解密失败：密码错误或文件已损坏。");
        }

        $decryptedFilepath = $savePath . DIRECTORY_SEPARATOR . $originalFilename;

        if (file_exists($decryptedFilepath)) {
            return [null, "文件 '" . basename($decryptedFilepath) . "' 已存在，为避免覆盖，请先处理旧文件。"];
        }

        file_put_contents($decryptedFilepath, $plaintext);
        
        return [$decryptedFilepath, "文件解密成功！"];

    } catch (Exception $e) {
        return [null, $e->getMessage()];
    }
}

function generateRandomKey()
{
    try {
        $key = bin2hex(openssl_random_pseudo_bytes(16));
        return ['success' => true, 'key' => $key];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

?>