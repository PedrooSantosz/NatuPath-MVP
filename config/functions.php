<?php
// ============================================
// FUNÇÕES AUXILIARES DO SISTEMA
// ============================================

/**
 * Verifica se o usuário é Super Admin
 */
function isSuperAdmin() {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'super_admin';
}

/**
 * Verifica se o usuário é Gestor
 */
function isGestor() {
    return isset($_SESSION['tipo']) && $_SESSION['tipo'] === 'gestor';
}

/**
 * Verifica se o usuário é Gestor OU Super Admin
 */
function isGestorOrAdmin() {
    return isset($_SESSION['tipo']) && 
           ($_SESSION['tipo'] === 'gestor' || $_SESSION['tipo'] === 'super_admin');
}

/**
 * Redireciona se o usuário não tiver permissão
 * CORRIGIDO: Agora aceita string OU array de tipos permitidos
 */
function requirePermission($requiredType) {
    if (!isset($_SESSION['tipo'])) {
        $_SESSION['error'] = "Você precisa estar logado para acessar esta página!";
        header('Location: dashboard.php');
        exit;
    }

    // ✅ CORREÇÃO: Suporta array de tipos permitidos
    if (is_array($requiredType)) {
        // Se for array, verifica se o tipo do usuário está na lista
        if (!in_array($_SESSION['tipo'], $requiredType)) {
            $_SESSION['error'] = "Acesso negado! Você não tem permissão para acessar esta página.";
            header('Location: dashboard.php');
            exit;
        }
        return; // Tem permissão, sai da função
    }

    // Comportamento original para strings
    if ($requiredType === 'super_admin' && !isSuperAdmin()) {
        $_SESSION['error'] = "Acesso negado! Apenas Super Admins podem acessar esta página.";
        header('Location: dashboard.php');
        exit;
    }

    if ($requiredType === 'gestor' && !isGestorOrAdmin()) {
        $_SESSION['error'] = "Acesso negado! Apenas Gestores podem acessar esta página.";
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Busca estatísticas do dashboard
 */
function getDashboardStats($conn, $user_id, $tipo, $setor_id = null) {
    $stats = [
        'total_boas_praticas' => 0,
        'total_nao_conformidades' => 0,
        'boas_praticas_pendentes' => 0,
        'nao_conformidades_abertas' => 0,
        'total_usuarios' => 0,
        'total_setores' => 0
    ];

    try {
        // Se for super admin, vê tudo
        if ($tipo === 'super_admin') {
            // Total de Boas Práticas
            $stmt = $conn->query("SELECT COUNT(*) as total FROM boas_praticas");
            $stats['total_boas_praticas'] = $stmt->fetch()['total'];

            // Total de Não Conformidades
            $stmt = $conn->query("SELECT COUNT(*) as total FROM nao_conformidades");
            $stats['total_nao_conformidades'] = $stmt->fetch()['total'];

            // Boas Práticas Pendentes
            $stmt = $conn->query("SELECT COUNT(*) as total FROM boas_praticas WHERE status = 'pendente'");
            $stats['boas_praticas_pendentes'] = $stmt->fetch()['total'];

            // Não Conformidades Abertas
            $stmt = $conn->query("SELECT COUNT(*) as total FROM nao_conformidades WHERE status IN ('aberto', 'em_analise')");
            $stats['nao_conformidades_abertas'] = $stmt->fetch()['total'];

            // Total de Usuários
            $stmt = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE status = 'ativo'");
            $stats['total_usuarios'] = $stmt->fetch()['total'];

            // Total de Setores
            $stmt = $conn->query("SELECT COUNT(*) as total FROM setores WHERE status = 'ativo'");
            $stats['total_setores'] = $stmt->fetch()['total'];

        } else if ($tipo === 'gestor' && $setor_id) {
            // Gestor vê apenas do seu setor
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE setor_id = :setor_id");
            $stmt->execute(['setor_id' => $setor_id]);
            $stats['total_boas_praticas'] = $stmt->fetch()['total'];

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nao_conformidades WHERE setor_id = :setor_id");
            $stmt->execute(['setor_id' => $setor_id]);
            $stats['total_nao_conformidades'] = $stmt->fetch()['total'];

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE setor_id = :setor_id AND status = 'pendente'");
            $stmt->execute(['setor_id' => $setor_id]);
            $stats['boas_praticas_pendentes'] = $stmt->fetch()['total'];

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nao_conformidades WHERE setor_id = :setor_id AND status IN ('aberto', 'em_analise')");
            $stmt->execute(['setor_id' => $setor_id]);
            $stats['nao_conformidades_abertas'] = $stmt->fetch()['total'];

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM usuarios WHERE setor_id = :setor_id AND status = 'ativo'");
            $stmt->execute(['setor_id' => $setor_id]);
            $stats['total_usuarios'] = $stmt->fetch()['total'];

        } else {
            // Usuário comum vê apenas seus registros
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE usuario_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $stats['total_boas_praticas'] = $stmt->fetch()['total'];

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nao_conformidades WHERE usuario_id = :user_id");
            $stmt->execute(['user_id' => $user_id]);
            $stats['total_nao_conformidades'] = $stmt->fetch()['total'];

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE usuario_id = :user_id AND status = 'pendente'");
            $stmt->execute(['user_id' => $user_id]);
            $stats['boas_praticas_pendentes'] = $stmt->fetch()['total'];

            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nao_conformidades WHERE usuario_id = :user_id AND status IN ('aberto', 'em_analise')");
            $stmt->execute(['user_id' => $user_id]);
            $stats['nao_conformidades_abertas'] = $stmt->fetch()['total'];
        }

    } catch(PDOException $e) {
        error_log("Erro ao buscar estatísticas: " . $e->getMessage());
    }

    return $stats;
}

/**
 * Busca atividades recentes
 */
function getRecentActivities($conn, $user_id, $tipo, $setor_id = null, $limit = 10) {
    $activities = [];

    try {
        if ($tipo === 'super_admin') {
            // Super Admin vê tudo
            $sql = "
                (SELECT 
                    'boa_pratica' as tipo,
                    bp.id,
                    bp.titulo,
                    bp.status,
                    bp.criado_em,
                    u.nome as usuario_nome,
                    s.nome as setor_nome
                FROM boas_praticas bp
                LEFT JOIN usuarios u ON bp.usuario_id = u.id
                LEFT JOIN setores s ON bp.setor_id = s.id)
                
                UNION ALL
                
                (SELECT 
                    'nao_conformidade' as tipo,
                    nc.id,
                    nc.titulo,
                    nc.status,
                    nc.criado_em,
                    u.nome as usuario_nome,
                    s.nome as setor_nome
                FROM nao_conformidades nc
                LEFT JOIN usuarios u ON nc.usuario_id = u.id
                LEFT JOIN setores s ON nc.setor_id = s.id)
                
                ORDER BY criado_em DESC
                LIMIT :limit
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
        } else if ($tipo === 'gestor' && $setor_id) {
            // Gestor vê apenas do seu setor
            $sql = "
                (SELECT 
                    'boa_pratica' as tipo,
                    bp.id,
                    bp.titulo,
                    bp.status,
                    bp.criado_em,
                    u.nome as usuario_nome,
                    s.nome as setor_nome
                FROM boas_praticas bp
                LEFT JOIN usuarios u ON bp.usuario_id = u.id
                LEFT JOIN setores s ON bp.setor_id = s.id
                WHERE bp.setor_id = :setor_id)
                
                UNION ALL
                
                (SELECT 
                    'nao_conformidade' as tipo,
                    nc.id,
                    nc.titulo,
                    nc.status,
                    nc.criado_em,
                    u.nome as usuario_nome,
                    s.nome as setor_nome
                FROM nao_conformidades nc
                LEFT JOIN usuarios u ON nc.usuario_id = u.id
                LEFT JOIN setores s ON nc.setor_id = s.id
                WHERE nc.setor_id = :setor_id)
                
                ORDER BY criado_em DESC
                LIMIT :limit
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':setor_id', $setor_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            
        } else {
            // Usuário comum vê apenas seus registros
            $sql = "
                (SELECT 
                    'boa_pratica' as tipo,
                    bp.id,
                    bp.titulo,
                    bp.status,
                    bp.criado_em,
                    u.nome as usuario_nome,
                    s.nome as setor_nome
                FROM boas_praticas bp
                LEFT JOIN usuarios u ON bp.usuario_id = u.id
                LEFT JOIN setores s ON bp.setor_id = s.id
                WHERE bp.usuario_id = :user_id)
                
                UNION ALL
                
                (SELECT 
                    'nao_conformidade' as tipo,
                    nc.id,
                    nc.titulo,
                    nc.status,
                    nc.criado_em,
                    u.nome as usuario_nome,
                    s.nome as setor_nome
                FROM nao_conformidades nc
                LEFT JOIN usuarios u ON nc.usuario_id = u.id
                LEFT JOIN setores s ON nc.setor_id = s.id
                WHERE nc.usuario_id = :user_id)
                
                ORDER BY criado_em DESC
                LIMIT :limit
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        }

        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch(PDOException $e) {
        error_log("Erro ao buscar atividades recentes: " . $e->getMessage());
    }

    return $activities;
}

/**
 * Formata data/hora para exibição
 */
function formatDateTime($datetime) {
    if (!$datetime) return '-';
    
    $date = new DateTime($datetime);
    return $date->format('d/m/Y H:i');
}

/**
 * Formata apenas data
 */
function formatDate($date) {
    if (!$date) return '-';
    
    $d = new DateTime($date);
    return $d->format('d/m/Y');
}

/**
 * Retorna badge HTML para status
 */
function getStatusBadge($status, $tipo) {
    $badges = [
        'boa_pratica' => [
            'pendente' => '<span class="badge badge-warning">Pendente</span>',
            'aprovado' => '<span class="badge badge-success">Aprovado</span>',
            'rejeitado' => '<span class="badge badge-danger">Rejeitado</span>'
        ],
        'nao_conformidade' => [
            'aberto' => '<span class="badge badge-danger">Aberto</span>',
            'em_analise' => '<span class="badge badge-warning">Em Análise</span>',
            'resolvido' => '<span class="badge badge-success">Resolvido</span>',
            'fechado' => '<span class="badge badge-secondary">Fechado</span>'
        ]
    ];

    return $badges[$tipo][$status] ?? '<span class="badge badge-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Sanitiza string para evitar XSS
 */
function sanitize($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>