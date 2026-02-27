<?php
// ============================================
// EXPORTAÇÃO DE RELATÓRIOS
// Exporta relatórios em CSV, Excel básico (HTML) ou PDF básico (HTML)
// SEM dependências externas - apenas PHP nativo
// ============================================

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Verifica autenticação
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    $_SESSION['error'] = "Você precisa estar logado!";
    header('Location: ../../public/index.php');
    exit;
}

// Recebe parâmetros
$formato = $_GET['formato'] ?? 'csv'; // csv, xlsx, pdf
$tipo = $_GET['tipo'] ?? 'todos'; // todos, boas_praticas, nao_conformidades
$filtro_status = $_GET['status'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_setor = $_GET['setor'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';

$user_id = $_SESSION['user_id'];
$user_tipo = $_SESSION['tipo'];
$user_setor_id = null;

// Busca setor do usuário (se gestor)
if ($user_tipo === 'gestor') {
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    $user_setor_id = $user_data['setor_id'] ?? null;
}

// ============================================
// BUSCA DADOS COM OS MESMOS FILTROS
// ============================================

$dados = [];

// Busca Boas Práticas (se aplicável)
if ($tipo === 'todos' || $tipo === 'boas_praticas') {
    $where_bp = ["1=1"];
    $params_bp = [];
    
    // Permissões
    if ($user_tipo === 'usuario') {
        $where_bp[] = "bp.usuario_id = :user_id";
        $params_bp['user_id'] = $user_id;
    } else if ($user_tipo === 'gestor' && $user_setor_id) {
        $where_bp[] = "bp.setor_id = :setor_id";
        $params_bp['setor_id'] = $user_setor_id;
    }
    
    // Filtros
    if ($filtro_status) {
        $where_bp[] = "bp.status = :status";
        $params_bp['status'] = $filtro_status;
    }
    if ($filtro_categoria) {
        $where_bp[] = "bp.categoria_id = :categoria_id";
        $params_bp['categoria_id'] = $filtro_categoria;
    }
    if ($filtro_setor && $user_tipo === 'super_admin') {
        $where_bp[] = "bp.setor_id = :setor_filtro";
        $params_bp['setor_filtro'] = $filtro_setor;
    }
    if ($filtro_data_inicio) {
        $where_bp[] = "bp.data_pratica >= :data_inicio";
        $params_bp['data_inicio'] = $filtro_data_inicio;
    }
    if ($filtro_data_fim) {
        $where_bp[] = "bp.data_pratica <= :data_fim";
        $params_bp['data_fim'] = $filtro_data_fim;
    }
    if ($filtro_usuario && ($user_tipo !== 'usuario')) {
        $where_bp[] = "bp.usuario_id = :usuario_filtro";
        $params_bp['usuario_filtro'] = $filtro_usuario;
    }
    
    $sql_bp = "
        SELECT 
            'Boa Prática' as tipo,
            bp.titulo,
            DATE_FORMAT(bp.data_pratica, '%d/%m/%Y') as data,
            bp.impacto,
            bp.status,
            c.nome as categoria,
            u.nome as usuario,
            s.nome as setor,
            DATE_FORMAT(bp.criado_em, '%d/%m/%Y %H:%i') as criado_em
        FROM boas_praticas bp
        LEFT JOIN categorias_boas_praticas c ON bp.categoria_id = c.id
        LEFT JOIN usuarios u ON bp.usuario_id = u.id
        LEFT JOIN setores s ON bp.setor_id = s.id
        WHERE " . implode(" AND ", $where_bp) . "
        ORDER BY bp.criado_em DESC
    ";
    
    $stmt = $conn->prepare($sql_bp);
    $stmt->execute($params_bp);
    $dados = array_merge($dados, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Busca Não Conformidades (se aplicável)
if ($tipo === 'todos' || $tipo === 'nao_conformidades') {
    $where_nc = ["1=1"];
    $params_nc = [];
    
    // Permissões
    if ($user_tipo === 'usuario') {
        $where_nc[] = "nc.usuario_id = :user_id";
        $params_nc['user_id'] = $user_id;
    } else if ($user_tipo === 'gestor' && $user_setor_id) {
        $where_nc[] = "nc.setor_id = :setor_id";
        $params_nc['setor_id'] = $user_setor_id;
    }
    
    // Filtros
    if ($filtro_status) {
        $where_nc[] = "nc.status = :status";
        $params_nc['status'] = $filtro_status;
    }
    if ($filtro_categoria) {
        $where_nc[] = "nc.categoria_id = :categoria_id";
        $params_nc['categoria_id'] = $filtro_categoria;
    }
    if ($filtro_setor && $user_tipo === 'super_admin') {
        $where_nc[] = "nc.setor_id = :setor_filtro";
        $params_nc['setor_filtro'] = $filtro_setor;
    }
    if ($filtro_data_inicio) {
        $where_nc[] = "nc.data_ocorrencia >= :data_inicio";
        $params_nc['data_inicio'] = $filtro_data_inicio;
    }
    if ($filtro_data_fim) {
        $where_nc[] = "nc.data_ocorrencia <= :data_fim";
        $params_nc['data_fim'] = $filtro_data_fim;
    }
    if ($filtro_usuario && ($user_tipo !== 'usuario')) {
        $where_nc[] = "nc.usuario_id = :usuario_filtro";
        $params_nc['usuario_filtro'] = $filtro_usuario;
    }
    
    $sql_nc = "
        SELECT 
            'Não Conformidade' as tipo,
            nc.titulo,
            DATE_FORMAT(nc.data_ocorrencia, '%d/%m/%Y') as data,
            nc.gravidade as impacto,
            nc.status,
            c.nome as categoria,
            u.nome as usuario,
            s.nome as setor,
            DATE_FORMAT(nc.criado_em, '%d/%m/%Y %H:%i') as criado_em
        FROM nao_conformidades nc
        LEFT JOIN categorias_nao_conformidades c ON nc.categoria_id = c.id
        LEFT JOIN usuarios u ON nc.usuario_id = u.id
        LEFT JOIN setores s ON nc.setor_id = s.id
        WHERE " . implode(" AND ", $where_nc) . "
        ORDER BY nc.criado_em DESC
    ";
    
    $stmt = $conn->prepare($sql_nc);
    $stmt->execute($params_nc);
    $dados = array_merge($dados, $stmt->fetchAll(PDO::FETCH_ASSOC));
}

// Verifica se há dados
if (empty($dados)) {
    $_SESSION['error'] = "Nenhum dado encontrado para exportar com os filtros aplicados!";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

// ============================================
// EXPORTAÇÃO POR FORMATO
// ============================================

if ($formato === 'csv') {
    exportarCSV($dados, $tipo);
} else if ($formato === 'xlsx') {
    exportarExcel($dados, $tipo);
} else if ($formato === 'pdf') {
    exportarPDF($dados, $tipo);
} else {
    $_SESSION['error'] = "Formato de exportação inválido!";
    header('Location: ../../includes/partials/relatorios.php');
    exit;
}

// ============================================
// FUNÇÃO: EXPORTAR CSV
// ============================================
function exportarCSV($dados, $tipo) {
    $filename = "relatorio_" . $tipo . "_" . date('Y-m-d_His') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8 (Excel compatível)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho padronizado
    $headers = ['Tipo', 'Título', 'Data', 'Impacto/Gravidade', 'Status', 'Categoria', 'Usuário', 'Setor', 'Criado em'];
    fputcsv($output, $headers, ';');
    
    // Dados
    foreach ($dados as $linha) {
        $row = [
            $linha['tipo'] ?? '-',
            $linha['titulo'] ?? '-',
            $linha['data'] ?? '-',
            $linha['impacto'] ?? '-',
            $linha['status'] ?? '-',
            $linha['categoria'] ?? '-',
            $linha['usuario'] ?? '-',
            $linha['setor'] ?? '-',
            $linha['criado_em'] ?? '-'
        ];
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

// ============================================
// FUNÇÃO: EXPORTAR EXCEL (HTML com mime type XLS)
// ============================================
function exportarExcel($dados, $tipo) {
    $filename = "relatorio_" . $tipo . "_" . date('Y-m-d_His') . ".xls";
    
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    echo "\xEF\xBB\xBF"; // BOM UTF-8
    
    echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th { background-color: #10b981; color: white; font-weight: bold; padding: 10px; border: 1px solid #ddd; }';
    echo 'td { padding: 8px; border: 1px solid #ddd; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<h2>Relatório - ' . ucfirst(str_replace('_', ' ', $tipo)) . '</h2>';
    echo '<p>Gerado em: ' . date('d/m/Y H:i:s') . '</p>';
    echo '<br>';
    
    echo '<table>';
    
    // Cabeçalho padronizado
    echo '<thead><tr>';
    echo '<th>Tipo</th>';
    echo '<th>Título</th>';
    echo '<th>Data</th>';
    echo '<th>Impacto/Gravidade</th>';
    echo '<th>Status</th>';
    echo '<th>Categoria</th>';
    echo '<th>Usuário</th>';
    echo '<th>Setor</th>';
    echo '<th>Criado em</th>';
    echo '</tr></thead>';
    
    // Dados
    echo '<tbody>';
    foreach ($dados as $linha) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($linha['tipo'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['titulo'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['data'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['impacto'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['status'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['categoria'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['usuario'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['setor'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['criado_em'] ?? '-') . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
    exit;
}

// ============================================
// FUNÇÃO: EXPORTAR PDF (HTML com Print CSS)
// ============================================
function exportarPDF($dados, $tipo) {
    header('Content-Type: text/html; charset=utf-8');
    
    echo '<!DOCTYPE html>';
    echo '<html>';
    echo '<head>';
    echo '<meta charset="utf-8">';
    echo '<title>Relatório - ' . ucfirst(str_replace('_', ' ', $tipo)) . '</title>';
    echo '<style>';
    echo '@media print { ';
    echo '  @page { size: A4 landscape; margin: 1cm; }';
    echo '  body { font-family: Arial, sans-serif; font-size: 9pt; }';
    echo '}';
    echo 'body { font-family: Arial, sans-serif; padding: 20px; }';
    echo 'h1 { color: #10b981; text-align: center; font-size: 24px; }';
    echo 'table { border-collapse: collapse; width: 100%; margin-top: 20px; font-size: 11px; }';
    echo 'th { background-color: #10b981; color: white; font-weight: bold; padding: 8px; border: 1px solid #ddd; text-align: left; }';
    echo 'td { padding: 6px; border: 1px solid #ddd; }';
    echo 'tr:nth-child(even) { background-color: #f9f9f9; }';
    echo '.info { text-align: center; color: #666; margin-bottom: 20px; }';
    echo '.print-btn { display: block; margin: 20px auto; padding: 15px 30px; background: #10b981; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }';
    echo '.print-btn:hover { background: #059669; }';
    echo '@media print { .print-btn { display: none; } }';
    echo '</style>';
    echo '<script>';
    echo 'function printAndClose() { window.print(); }';
    echo '</script>';
    echo '</head>';
    echo '<body>';
    
    echo '<h1>Relatório - ' . ucfirst(str_replace('_', ' ', $tipo)) . '</h1>';
    echo '<p class="info">Gerado em: ' . date('d/m/Y H:i:s') . '</p>';
    
    echo '<button class="print-btn" onclick="printAndClose()">🖨️ Imprimir / Salvar como PDF</button>';
    
    echo '<table>';
    
    // Cabeçalho padronizado
    echo '<thead><tr>';
    echo '<th>Tipo</th>';
    echo '<th>Título</th>';
    echo '<th>Data</th>';
    echo '<th>Impacto/Gravidade</th>';
    echo '<th>Status</th>';
    echo '<th>Categoria</th>';
    echo '<th>Usuário</th>';
    echo '<th>Setor</th>';
    echo '<th>Criado em</th>';
    echo '</tr></thead>';
    
    // Dados
    echo '<tbody>';
    foreach ($dados as $linha) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($linha['tipo'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['titulo'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['data'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['impacto'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['status'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['categoria'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['usuario'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['setor'] ?? '-') . '</td>';
        echo '<td>' . htmlspecialchars($linha['criado_em'] ?? '-') . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    
    echo '</table>';
    
    echo '<p class="info" style="margin-top: 30px; font-size: 12px;">NatuPath - Sistema de Gestão de Práticas Sustentáveis</p>';
    
    echo '</body>';
    echo '</html>';
    
    exit;
}
?>