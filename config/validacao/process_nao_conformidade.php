<?php
// ============================================
// PROCESSA CADASTRO DE NÃO CONFORMIDADES
// ============================================

session_start();
require_once __DIR__ . '/../config.php';

// Verifica se está logado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../../public/index.php');
    exit;
}

// Verifica se foi enviado via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Método de requisição inválido!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Recebe os dados do formulário
$titulo = trim($_POST['titulo'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$categoria_id = intval($_POST['categoria_id'] ?? 0);
$local = trim($_POST['local'] ?? '');
$data_ocorrencia = $_POST['data_ocorrencia'] ?? '';
$gravidade = $_POST['gravidade'] ?? 'media';
$usuario_id = $_SESSION['user_id'];

// Validações básicas
if (empty($titulo) || empty($descricao) || empty($categoria_id) || empty($local) || empty($data_ocorrencia)) {
    $_SESSION['error'] = "Por favor, preencha todos os campos obrigatórios!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

if (strlen($titulo) < 5) {
    $_SESSION['error'] = "O título deve ter pelo menos 5 caracteres!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

if (strlen($descricao) < 20) {
    $_SESSION['error'] = "A descrição deve ter pelo menos 20 caracteres!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Verifica se a data não é futura
if (strtotime($data_ocorrencia) > time()) {
    $_SESSION['error'] = "A data da ocorrência não pode ser futura!";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Busca o setor do usuário
try {
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :user_id");
    $stmt->execute(['user_id' => $usuario_id]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['setor_id']) {
        $_SESSION['error'] = "Você precisa estar vinculado a um setor para reportar não conformidades!";
        header('Location: ../../includes/partials/dashboard.php');
        exit;
    }
    
    $setor_id = $user['setor_id'];
} catch(PDOException $e) {
    error_log("Erro ao buscar setor do usuário: " . $e->getMessage());
    $_SESSION['error'] = "Erro ao processar sua solicitação.";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}

// Processa upload de foto (se houver)
$foto_nome = null;

if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
    $foto = $_FILES['foto'];
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $tamanho_maximo = 5 * 1024 * 1024; // 5MB
    
    // Valida extensão
    $extensao = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));
    if (!in_array($extensao, $extensoes_permitidas)) {
        $_SESSION['error'] = "Formato de imagem não permitido! Use: JPG, PNG, GIF ou WEBP.";
        header('Location: ../../includes/partials/dashboard.php');
        exit;
    }
    
    // Valida tamanho
    if ($foto['size'] > $tamanho_maximo) {
        $_SESSION['error'] = "A imagem não pode ser maior que 5MB!";
        header('Location: ../../includes/partials/dashboard.php');
        exit;
    }
    
    // ✅ VALIDAÇÃO MIME TYPE ADICIONADA
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $foto['tmp_name']);
    finfo_close($finfo);
    
    $mimes_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $mimes_permitidos)) {
        $_SESSION['error'] = "Arquivo não é uma imagem válida!";
        header('Location: ../../includes/partials/dashboard.php');
        exit;
    }
    
    // Gera nome único para a foto
    $foto_nome = 'nc_' . uniqid() . '_' . time() . '.' . $extensao;
    $destino = __DIR__ . '/../../uploads/nao_conformidades/' . $foto_nome;
    
    // Cria diretório se não existir
    if (!is_dir(__DIR__ . '/../../uploads/nao_conformidades/')) {
        mkdir(__DIR__ . '/../../uploads/nao_conformidades/', 0755, true);
    }
    
    // Move o arquivo
    if (!move_uploaded_file($foto['tmp_name'], $destino)) {
        error_log("Erro ao fazer upload da foto");
        $foto_nome = null; // Se falhar, continua sem foto
    }
}

// Insere no banco de dados
try {
    $stmt = $conn->prepare("
        INSERT INTO nao_conformidades 
        (titulo, descricao, categoria_id, usuario_id, setor_id, local, data_ocorrencia, gravidade, foto, status)
        VALUES 
        (:titulo, :descricao, :categoria_id, :usuario_id, :setor_id, :local, :data_ocorrencia, :gravidade, :foto, 'aberto')
    ");
    
    $stmt->execute([
        'titulo' => $titulo,
        'descricao' => $descricao,
        'categoria_id' => $categoria_id,
        'usuario_id' => $usuario_id,
        'setor_id' => $setor_id,
        'local' => $local,
        'data_ocorrencia' => $data_ocorrencia,
        'gravidade' => $gravidade,
        'foto' => $foto_nome
    ]);
    
    $_SESSION['success'] = "Não conformidade reportada com sucesso! Em breve será analisada.";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
    
} catch(PDOException $e) {
    error_log("Erro ao inserir não conformidade: " . $e->getMessage());
    
    // Se deu erro, apaga a foto que foi enviada
    if ($foto_nome && file_exists(__DIR__ . '/../../uploads/nao_conformidades/' . $foto_nome)) {
        unlink(__DIR__ . '/../../uploads/nao_conformidades/' . $foto_nome);
    }
    
    $_SESSION['error'] = "Erro ao reportar não conformidade. Tente novamente.";
    header('Location: ../../includes/partials/dashboard.php');
    exit;
}
?>