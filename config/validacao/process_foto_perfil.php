<?php
// ============================================
// PROCESSA UPLOAD E REMOÇÃO DE FOTO DE PERFIL
// ============================================

session_start();
require_once __DIR__ . '/../config.php';

// Verifica autenticação
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Você precisa estar logado.';
    header('Location: ../../includes/partials/configuracoes.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// ====================
// UPLOAD DE FOTO
// ====================
if ($action === 'upload_foto') {
    try {
        // Verifica se há arquivo
        if (!isset($_FILES['foto']) || $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE) {
            throw new Exception('Nenhum arquivo foi enviado.');
        }

        $file = $_FILES['foto'];

        // Verifica erros
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo. Código: ' . $file['error']);
        }

        // Valida tipo do arquivo
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception('Tipo de arquivo inválido. Use apenas JPG, PNG, GIF ou WEBP.');
        }

        // Valida tamanho (máx 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB em bytes
        if ($file['size'] > $max_size) {
            throw new Exception('Arquivo muito grande. Tamanho máximo: 5MB.');
        }

        // Busca foto atual
        $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();
        $foto_antiga = $user['foto'] ?? null;

        // Define pasta de upload
        $upload_dir = __DIR__ . '/../../uploads/perfil/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Gera nome único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $new_filename = 'perfil_' . $user_id . '_' . time() . '.' . $extension;
        $upload_path = $upload_dir . $new_filename;

        // Move o arquivo
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Erro ao salvar o arquivo no servidor.');
        }

        // Atualiza banco de dados
        $stmt = $conn->prepare("UPDATE usuarios SET foto = :foto WHERE id = :id");
        $stmt->execute([
            'foto' => $new_filename,
            'id' => $user_id
        ]);

        // Remove foto antiga se existir
        if ($foto_antiga && file_exists($upload_dir . $foto_antiga)) {
            unlink($upload_dir . $foto_antiga);
        }

        $_SESSION['success'] = 'Foto de perfil atualizada com sucesso!';
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: ../../includes/partials/configuracoes.php');
    exit;
}

// ====================
// REMOVER FOTO
// ====================
if ($action === 'remover_foto') {
    try {
        // Busca foto atual
        $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE id = :id");
        $stmt->execute(['id' => $user_id]);
        $user = $stmt->fetch();
        $foto_atual = $user['foto'] ?? null;

        if (!$foto_atual) {
            throw new Exception('Você não possui foto de perfil para remover.');
        }

        // Remove do banco
        $stmt = $conn->prepare("UPDATE usuarios SET foto = NULL WHERE id = :id");
        $stmt->execute(['id' => $user_id]);

        // Remove arquivo físico
        $file_path = __DIR__ . '/../../uploads/perfil/' . $foto_atual;
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        $_SESSION['success'] = 'Foto de perfil removida com sucesso!';
        
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: ../../includes/partials/configuracoes.php');
    exit;
}

// Se chegou aqui, ação inválida
$_SESSION['error'] = 'Ação inválida.';
header('Location: ../../includes/partials/configuracoes.php');
exit;