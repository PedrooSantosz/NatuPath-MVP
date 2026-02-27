<?php
$pageTitle = "Relatórios";
require_once 'header.php';

$user_tipo = $_SESSION['tipo'];
$user_id = $_SESSION['user_id'];
$user_setor_id = null;

// Se for gestor, busca o setor
if ($user_tipo === 'gestor') {
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    $user_setor_id = $user_data['setor_id'] ?? null;
}

// ============================================
// PROCESSA FILTROS
// ============================================
$filtro_tipo = $_GET['tipo'] ?? 'todos'; // todos, boas_praticas, nao_conformidades
$filtro_status = $_GET['status'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_setor = $_GET['setor'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';
$filtro_usuario = $_GET['usuario'] ?? '';

// ============================================
// BUSCA DADOS PARA FILTROS
// ============================================

// Setores (super admin vê todos, gestor apenas o seu)
if ($user_tipo === 'super_admin') {
    $stmt = $conn->query("SELECT id, nome FROM setores WHERE status = 'ativo' ORDER BY nome");
    $setores_filtro = $stmt->fetchAll();
} else if ($user_tipo === 'gestor' && $user_setor_id) {
    $stmt = $conn->prepare("SELECT id, nome FROM setores WHERE id = :id");
    $stmt->execute(['id' => $user_setor_id]);
    $setores_filtro = $stmt->fetchAll();
} else {
    $setores_filtro = [];
}

// Categorias BP
$stmt = $conn->query("SELECT id, nome FROM categorias_boas_praticas WHERE status = 'ativo' ORDER BY nome");
$categorias_bp = $stmt->fetchAll();

// Categorias NC
$stmt = $conn->query("SELECT id, nome FROM categorias_nao_conformidades WHERE status = 'ativo' ORDER BY nome");
$categorias_nc = $stmt->fetchAll();

// Usuários (apenas super admin e gestor veem)
if ($user_tipo === 'super_admin') {
    $stmt = $conn->query("SELECT id, nome FROM usuarios WHERE status = 'ativo' ORDER BY nome");
    $usuarios_filtro = $stmt->fetchAll();
} else if ($user_tipo === 'gestor' && $user_setor_id) {
    $stmt = $conn->prepare("SELECT id, nome FROM usuarios WHERE setor_id = :setor_id AND status = 'ativo' ORDER BY nome");
    $stmt->execute(['setor_id' => $user_setor_id]);
    $usuarios_filtro = $stmt->fetchAll();
} else {
    $usuarios_filtro = [];
}

// ============================================
// BUSCA BOAS PRÁTICAS
// ============================================
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
        bp.*,
        c.nome as categoria_nome,
        u.nome as usuario_nome,
        s.nome as setor_nome
    FROM boas_praticas bp
    LEFT JOIN categorias_boas_praticas c ON bp.categoria_id = c.id
    LEFT JOIN usuarios u ON bp.usuario_id = u.id
    LEFT JOIN setores s ON bp.setor_id = s.id
    WHERE " . implode(" AND ", $where_bp) . "
    ORDER BY bp.criado_em DESC
    LIMIT 100
";

$stmt = $conn->prepare($sql_bp);
$stmt->execute($params_bp);
$boas_praticas = $stmt->fetchAll();

// ============================================
// BUSCA NÃO CONFORMIDADES
// ============================================
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
        nc.*,
        c.nome as categoria_nome,
        u.nome as usuario_nome,
        s.nome as setor_nome
    FROM nao_conformidades nc
    LEFT JOIN categorias_nao_conformidades c ON nc.categoria_id = c.id
    LEFT JOIN usuarios u ON nc.usuario_id = u.id
    LEFT JOIN setores s ON nc.setor_id = s.id
    WHERE " . implode(" AND ", $where_nc) . "
    ORDER BY nc.criado_em DESC
    LIMIT 100
";

$stmt = $conn->prepare($sql_nc);
$stmt->execute($params_nc);
$nao_conformidades = $stmt->fetchAll();

// ============================================
// ESTATÍSTICAS GERAIS (SEM FILTROS)
// ============================================

// Busca totais gerais de Boas Práticas do banco
$where_bp_total = ["1=1"];
$params_bp_total = [];

if ($user_tipo === 'usuario') {
    $where_bp_total[] = "usuario_id = :user_id";
    $params_bp_total['user_id'] = $user_id;
} else if ($user_tipo === 'gestor' && $user_setor_id) {
    $where_bp_total[] = "setor_id = :setor_id";
    $params_bp_total['setor_id'] = $user_setor_id;
}

$sql_bp_total = "SELECT COUNT(*) as total FROM boas_praticas WHERE " . implode(" AND ", $where_bp_total);
$stmt = $conn->prepare($sql_bp_total);
$stmt->execute($params_bp_total);
$total_bp_geral = $stmt->fetch()['total'];

$sql_bp_aprovadas = "SELECT COUNT(*) as total FROM boas_praticas WHERE status = 'aprovado' AND " . implode(" AND ", $where_bp_total);
$stmt = $conn->prepare($sql_bp_aprovadas);
$stmt->execute($params_bp_total);
$bp_aprovadas_geral = $stmt->fetch()['total'];

$sql_bp_rejeitadas = "SELECT COUNT(*) as total FROM boas_praticas WHERE status = 'rejeitado' AND " . implode(" AND ", $where_bp_total);
$stmt = $conn->prepare($sql_bp_rejeitadas);
$stmt->execute($params_bp_total);
$bp_rejeitadas_geral = $stmt->fetch()['total'];

$sql_bp_pendentes = "SELECT COUNT(*) as total FROM boas_praticas WHERE status = 'pendente' AND " . implode(" AND ", $where_bp_total);
$stmt = $conn->prepare($sql_bp_pendentes);
$stmt->execute($params_bp_total);
$bp_pendentes_geral = $stmt->fetch()['total'];

// Calcula taxas de Boas Práticas
$taxa_aprovacao_bp = $total_bp_geral > 0 ? round(($bp_aprovadas_geral / $total_bp_geral) * 100, 1) : 0;
$taxa_rejeicao_bp = $total_bp_geral > 0 ? round(($bp_rejeitadas_geral / $total_bp_geral) * 100, 1) : 0;

// Busca totais gerais de Não Conformidades do banco
$where_nc_total = ["1=1"];
$params_nc_total = [];

if ($user_tipo === 'usuario') {
    $where_nc_total[] = "usuario_id = :user_id";
    $params_nc_total['user_id'] = $user_id;
} else if ($user_tipo === 'gestor' && $user_setor_id) {
    $where_nc_total[] = "setor_id = :setor_id";
    $params_nc_total['setor_id'] = $user_setor_id;
}

$sql_nc_total = "SELECT COUNT(*) as total FROM nao_conformidades WHERE " . implode(" AND ", $where_nc_total);
$stmt = $conn->prepare($sql_nc_total);
$stmt->execute($params_nc_total);
$total_nc_geral = $stmt->fetch()['total'];

$sql_nc_resolvidas = "SELECT COUNT(*) as total FROM nao_conformidades WHERE status = 'resolvido' AND " . implode(" AND ", $where_nc_total);
$stmt = $conn->prepare($sql_nc_resolvidas);
$stmt->execute($params_nc_total);
$nc_resolvidas_geral = $stmt->fetch()['total'];

$sql_nc_abertas = "SELECT COUNT(*) as total FROM nao_conformidades WHERE status IN ('aberto', 'em_analise') AND " . implode(" AND ", $where_nc_total);
$stmt = $conn->prepare($sql_nc_abertas);
$stmt->execute($params_nc_total);
$nc_abertas_geral = $stmt->fetch()['total'];

// Calcula taxas de Não Conformidades
$taxa_resolucao_nc = $total_nc_geral > 0 ? round(($nc_resolvidas_geral / $total_nc_geral) * 100, 1) : 0;

// Estatísticas dos dados filtrados (para exibição na tabela)
$total_bp = count($boas_praticas);
$total_nc = count($nao_conformidades);

$bp_aprovadas = count(array_filter($boas_praticas, fn($bp) => $bp['status'] === 'aprovado'));
$bp_pendentes = count(array_filter($boas_praticas, fn($bp) => $bp['status'] === 'pendente'));

$nc_abertas = count(array_filter($nao_conformidades, fn($nc) => in_array($nc['status'], ['aberto', 'em_analise'])));
$nc_resolvidas = count(array_filter($nao_conformidades, fn($nc) => $nc['status'] === 'resolvido'));
?>

<div class="overview">
    <div class="title">
        <i class="fas fa-file-alt"></i>
        <span class="text">Relatórios</span>
    </div>

    <!-- Cards de Resumo COM INDICADORES DE TAXA -->
    <div class="boxes" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); margin-bottom: 30px;">
        <!-- Card: Total de Boas Práticas -->
        <div class="box box1">
            <i class="fas fa-leaf"></i>
            <span class="text">Total Boas Práticas</span>
            <span class="number"><?php echo $total_bp_geral; ?></span>

            <div style="margin-top: 15px; font-size: 11px; color: #059669; line-height: 1.8;">

                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; padding: 3px 0; column-gap: 6px;">
                    <span>✓ Aprovadas: <?php echo $bp_aprovadas_geral; ?></span>

                    <!-- Correção do alinhamento + espaço -->
                    <strong style="display: inline-flex; align-items: center; line-height: 1;">
                        <?php echo $taxa_aprovacao_bp; ?>%
                    </strong>
                </div>

                <div style="width: 100%; background: #d1fae5; border-radius: 10px; height: 6px; overflow: hidden;">
                    <div style="width: <?php echo $taxa_aprovacao_bp; ?>%; background: #059669; height: 100%;"></div>
                </div>
            </div>
        </div>

        <!-- Card: Taxa de Rejeição -->
        <div class="box box2">
            <i class="fas fa-times-circle"></i>
            <span class="text">Taxa de Rejeição</span>
            <span class="number"><?php echo $taxa_rejeicao_bp; ?>%</span>
            <div style="margin-top: 15px; font-size: 11px; color: #dc2626; line-height: 1.8;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; padding: 3px 0;">
                    <span>✗ Rejeitadas: <?php echo $bp_rejeitadas_geral; ?></span>&nbsp;
                    <span>de <?php echo $total_bp_geral; ?></span>
                </div>
                <div style="width: 100%; background: #fee2e2; border-radius: 10px; height: 6px; overflow: hidden;">
                    <div style="width: <?php echo $taxa_rejeicao_bp; ?>%; background: #dc2626; height: 100%;"></div>
                </div>
            </div>
        </div>

        <!-- Card: Não Conformidades -->
        <div class="box box3">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="text">Total Não Conformidades</span>
            <span class="number"><?php echo $total_nc_geral; ?></span>
            <div style="margin-top: 15px; font-size: 11px; color: #ea580c; line-height: 1.8;">
                <div style="display: flex; justify-content: space-between; padding: 3px 0;">
                    <span>Abertas: <?php echo $nc_abertas_geral; ?></span>&nbsp;
                    <span>Resolvidas: <?php echo $nc_resolvidas_geral; ?></span>
                </div>
            </div>
        </div>

        <!-- Card: Taxa de Resolução -->
        <div class="box box4">
            <i class="fas fa-check-double"></i>
            <span class="text">Taxa de Resolução</span>
            <span class="number"><?php echo $taxa_resolucao_nc; ?>%</span>
            <div style="margin-top: 15px; font-size: 11px; color: #10b981; line-height: 1.8;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; padding: 3px 0;">
                    <span>Resolvidas: <?php echo $nc_resolvidas_geral; ?></span>&nbsp;
                    <span> de <?php echo $total_nc_geral; ?></span>
                </div>
                <div style="width: 100%; background: #d1fae5; border-radius: 10px; height: 6px; overflow: hidden;">
                    <div style="width: <?php echo $taxa_resolucao_nc; ?>%; background: #10b981; height: 100%;"></div>
                </div>
            </div>
        </div>

        <!-- Card: Pendentes de Aprovação -->
        <div class="box" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
            <i class="fas fa-clock" style="color: white;"></i>
            <span class="text" style="color: white;">Aguardando Aprovação</span>
            <span class="number" style="color: white;"><?php echo $bp_pendentes_geral; ?></span>
            <div style="margin-top: 10px; font-size: 11px; color: white;">
                <span>Boas práticas pendentes de análise</span>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="activity" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <div class="title">
            <i class="fas fa-filter"></i>
            <span class="text">Filtros</span>
        </div>
        <button onclick="toggleFiltros()" class="btn btn-secondary">
            <i class="fas fa-sliders-h"></i> <span id="btnFiltroText">Mostrar Filtros</span>
        </button>
    </div>

    <form id="formFiltros" method="GET" style="display: none;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">

            <!-- Status -->
            <div>
                <label style="display: block; margin-bottom: 5px; color: #666; font-size: 14px;">Status</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <optgroup label="Boas Práticas">
                        <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="aprovado" <?php echo $filtro_status === 'aprovado' ? 'selected' : ''; ?>>Aprovado</option>
                        <option value="rejeitado" <?php echo $filtro_status === 'rejeitado' ? 'selected' : ''; ?>>Rejeitado</option>
                    </optgroup>
                    <optgroup label="Não Conformidades">
                        <option value="aberto" <?php echo $filtro_status === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                        <option value="em_analise" <?php echo $filtro_status === 'em_analise' ? 'selected' : ''; ?>>Em Análise</option>
                        <option value="resolvido" <?php echo $filtro_status === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                        <option value="fechado" <?php echo $filtro_status === 'fechado' ? 'selected' : ''; ?>>Fechado</option>
                    </optgroup>
                </select>
            </div>

            <?php if (!empty($setores_filtro) && $user_tipo !== 'usuario'): ?>
                <!-- Setor -->
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #666; font-size: 14px;">Setor</label>
                    <select name="setor" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($setores_filtro as $setor): ?>
                            <option value="<?php echo $setor['id']; ?>" <?php echo $filtro_setor == $setor['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($setor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (!empty($usuarios_filtro)): ?>
                <!-- Usuário -->
                <div>
                    <label style="display: block; margin-bottom: 5px; color: #666; font-size: 14px;">Usuário</label>
                    <select name="usuario" class="form-control">
                        <option value="">Todos</option>
                        <?php foreach ($usuarios_filtro as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>" <?php echo $filtro_usuario == $usuario['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($usuario['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Data Início -->
            <div>
                <label style="display: block; margin-bottom: 5px; color: #666; font-size: 14px;">Data Início</label>
                <input type="date" name="data_inicio" value="<?php echo $filtro_data_inicio; ?>" class="form-control">
            </div>

            <!-- Data Fim -->
            <div>
                <label style="display: block; margin-bottom: 5px; color: #666; font-size: 14px;">Data Fim</label>
                <input type="date" name="data_fim" value="<?php echo $filtro_data_fim; ?>" class="form-control">
            </div>
        </div>

        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <button type="submit" name="tipo" value="boas_praticas" class="btn btn-success">
                <i class="fas fa-leaf"></i> Filtrar Boas Práticas
            </button>
            <button type="submit" name="tipo" value="nao_conformidades" class="btn btn-danger">
                <i class="fas fa-exclamation-triangle"></i> Filtrar Não Conformidades
            </button>
            <button type="submit" name="tipo" value="todos" class="btn btn-primary">
                <i class="fas fa-th-list"></i> Mostrar Todos
            </button>
            <a href="relatorios.php" class="btn btn-secondary">
                <i class="fas fa-times"></i> Limpar Filtros
            </a>
        </div>
    </form>
</div>

<!-- Abas de Conteúdo -->
<div class="activity">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="tabs" style="display: flex; gap: 10px;">
            <button class="tab-btn <?php echo $filtro_tipo === 'boas_praticas' ? 'active' : ''; ?>"
                onclick="switchTabRelatorio('boas-praticas')" id="tab-bp">
                <i class="fas fa-leaf"></i> Boas Práticas (<?php echo $total_bp; ?>)
            </button>
            <button class="tab-btn <?php echo $filtro_tipo === 'nao_conformidades' ? 'active' : ''; ?>"
                onclick="switchTabRelatorio('nao-conformidades')" id="tab-nc">
                <i class="fas fa-exclamation-triangle"></i> Não Conformidades (<?php echo $total_nc; ?>)
            </button>
        </div>

        <button onclick="openModal('modalExportar')" class="btn btn-success">
            <i class="fas fa-download"></i> Exportar
        </button>
    </div>

    <!-- TAB: BOAS PRÁTICAS -->
    <div id="tab-content-boas-praticas" class="tab-content <?php echo $filtro_tipo === 'boas_praticas' || $filtro_tipo === 'todos' ? 'active' : ''; ?>"
        style="<?php echo $filtro_tipo !== 'boas_praticas' && $filtro_tipo !== 'todos' ? 'display: none;' : ''; ?>">

        <div class="activity-data">
            <?php if (isGestorOrAdmin() && !empty($boas_praticas)): ?>
                <!-- Painel de Aprovação em Massa -->
                <div id="painelAprovacaoMassa" class="painel-aprovacao-massa">
                    <div class="painel-header">
                        <div class="painel-info">
                            <i class="fas fa-check-double"></i>
                            <span class="contador-badge" id="contadorSelecionados">0</span>
                            <span>registro(s) selecionado(s)</span>
                        </div>
                        <div class="painel-acoes">
                            <button type="button" onclick="aprovarEmMassa()" class="btn btn-success">
                                <i class="fas fa-check"></i> Aprovar Selecionados
                            </button>
                            <button type="button" onclick="rejeitarEmMassa()" class="btn btn-danger">
                                <i class="fas fa-times"></i> Rejeitar Selecionados
                            </button>
                            <button type="button" onclick="limparSelecao()" class="btn btn-secondary">
                                <i class="fas fa-eraser"></i> Limpar Seleção
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($boas_praticas)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>Nenhuma boa prática encontrada com os filtros aplicados.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <?php if (isGestorOrAdmin()): ?>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selecionarTodosBP"
                                        onchange="selecionarTodos(this, 'boa_pratica')"
                                        title="Selecionar todos">
                                </th>
                            <?php endif; ?>
                            <th>Data</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <?php if ($user_tipo !== 'usuario'): ?>
                                <th>Usuário</th>
                                <th>Setor</th>
                            <?php endif; ?>
                            <th>Impacto</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($boas_praticas as $bp): ?>
                            <tr>
                                <?php if (isGestorOrAdmin()): ?>
                                    <td>
                                        <?php if ($bp['status'] === 'pendente'): ?>
                                            <input type="checkbox" class="checkbox-bp"
                                                value="<?php echo $bp['id']; ?>"
                                                onchange="atualizarContador()">
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?php echo formatDate($bp['data_pratica']); ?></td>
                                <td><strong><?php echo sanitize($bp['titulo']); ?></strong></td>
                                <td>
                                    <span class="badge badge-success">
                                        <?php echo sanitize($bp['categoria_nome']); ?>
                                    </span>
                                </td>
                                <?php if ($user_tipo !== 'usuario'): ?>
                                    <td><?php echo sanitize($bp['usuario_nome']); ?></td>
                                    <td><?php echo sanitize($bp['setor_nome']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php
                                    $impacto_badges = [
                                        'baixo' => '<span class="badge badge-info">Baixo</span>',
                                        'medio' => '<span class="badge badge-warning">Médio</span>',
                                        'alto' => '<span class="badge badge-success">Alto</span>'
                                    ];
                                    echo $impacto_badges[$bp['impacto']] ?? $bp['impacto'];
                                    ?>
                                </td>
                                <td><?php echo getStatusBadge($bp['status'], 'boa_pratica'); ?></td>
                                <td>
                                    <button onclick="verDetalhes(<?php echo $bp['id']; ?>, 'boa_pratica')"
                                        class="btn-sm btn-info" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: NÃO CONFORMIDADES -->
    <div id="tab-content-nao-conformidades" class="tab-content <?php echo $filtro_tipo === 'nao_conformidades' ? 'active' : ''; ?>"
        style="<?php echo $filtro_tipo !== 'nao_conformidades' ? 'display: none;' : ''; ?>">

        <div class="activity-data">
            <?php if (isGestorOrAdmin() && !empty($nao_conformidades)): ?>
                <!-- Painel de Aprovação em Massa - Não Conformidades -->
                <div id="painelAprovacaoMassaNC" class="painel-aprovacao-massa nao-conformidade">
                    <div class="painel-header">
                        <div class="painel-info">
                            <i class="fas fa-check-double"></i>
                            <span class="contador-badge" id="contadorSelecionadosNC">0</span>
                            <span>registro(s) selecionado(s)</span>
                        </div>
                        <div class="painel-acoes">
                            <button type="button" onclick="analisarEmMassa()" class="btn btn-warning">
                                <i class="fas fa-search"></i> Marcar como "Em Análise"
                            </button>
                            <button type="button" onclick="limparSelecaoNC()" class="btn btn-secondary">
                                <i class="fas fa-eraser"></i> Limpar Seleção
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($nao_conformidades)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>Nenhuma não conformidade encontrada com os filtros aplicados.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <?php if (isGestorOrAdmin()): ?>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selecionarTodosNC"
                                        onchange="selecionarTodos(this, 'nao_conformidade')"
                                        title="Selecionar todos">
                                </th>
                            <?php endif; ?>
                            <th>Data</th>
                            <th>Título</th>
                            <th>Categoria</th>
                            <?php if ($user_tipo !== 'usuario'): ?>
                                <th>Usuário</th>
                                <th>Setor</th>
                            <?php endif; ?>
                            <th>Local</th>
                            <th>Gravidade</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($nao_conformidades as $nc): ?>
                            <tr>
                                <?php if (isGestorOrAdmin()): ?>
                                    <td>
                                        <?php if ($nc['status'] === 'aberto'): ?>
                                            <input type="checkbox" class="checkbox-nc"
                                                value="<?php echo $nc['id']; ?>"
                                                onchange="atualizarContadorNC()">
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td><?php echo formatDate($nc['data_ocorrencia']); ?></td>
                                <td><strong><?php echo sanitize($nc['titulo']); ?></strong></td>
                                <td>
                                    <span class="badge badge-danger">
                                        <?php echo sanitize($nc['categoria_nome']); ?>
                                    </span>
                                </td>
                                <?php if ($user_tipo !== 'usuario'): ?>
                                    <td><?php echo sanitize($nc['usuario_nome']); ?></td>
                                    <td><?php echo sanitize($nc['setor_nome']); ?></td>
                                <?php endif; ?>
                                <td><?php echo sanitize($nc['local']); ?></td>
                                <td>
                                    <?php
                                    $gravidade_badges = [
                                        'baixa' => '<span class="badge badge-info">Baixa</span>',
                                        'media' => '<span class="badge badge-warning">Média</span>',
                                        'alta' => '<span class="badge badge-danger">Alta</span>',
                                        'critica' => '<span class="badge" style="background: #7f1d1d; color: white;">Crítica</span>'
                                    ];
                                    echo $gravidade_badges[$nc['gravidade']] ?? $nc['gravidade'];
                                    ?>
                                </td>
                                <td><?php echo getStatusBadge($nc['status'], 'nao_conformidade'); ?></td>
                                <td>
                                    <button onclick="verDetalhes(<?php echo $nc['id']; ?>, 'nao_conformidade')"
                                        class="btn-sm btn-info" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Ver Detalhes (será preenchido via JS) -->
<div id="modalDetalhes" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <span class="close" onclick="closeModal('modalDetalhes')">&times;</span>
        <div id="conteudoDetalhes">
            <div style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 48px;"></i>
                <p>Carregando...</p>
            </div>
        </div>
    </div>
</div>

<style>
    .tabs {
        border-bottom: 2px solid #e5e7eb;
    }

    .tab-btn {
        padding: 10px 20px;
        border: none;
        background: none;
        cursor: pointer;
        font-size: 16px;
        color: #666;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }

    .tab-btn:hover {
        color: #10b981;
    }

    .tab-btn.active {
        color: #10b981;
        border-bottom: 3px solid #10b981;
        font-weight: 600;
    }

    .tab-content {
        margin-top: 20px;
    }
</style>

<script>
    // Toggle filtros
    function toggleFiltros() {
        const form = document.getElementById('formFiltros');
        const btn = document.getElementById('btnFiltroText');

        if (form.style.display === 'none') {
            form.style.display = 'block';
            btn.textContent = 'Ocultar Filtros';
        } else {
            form.style.display = 'none';
            btn.textContent = 'Mostrar Filtros';
        }
    }

    // Trocar abas
    function switchTabRelatorio(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => {
            content.style.display = 'none';
            content.classList.remove('active');
        });

        if (tab === 'boas-praticas') {
            document.getElementById('tab-bp').classList.add('active');
            document.getElementById('tab-content-boas-praticas').style.display = 'block';
            document.getElementById('tab-content-boas-praticas').classList.add('active');
        } else {
            document.getElementById('tab-nc').classList.add('active');
            document.getElementById('tab-content-nao-conformidades').style.display = 'block';
            document.getElementById('tab-content-nao-conformidades').classList.add('active');
        }
    }

    // Ver detalhes 
    function verDetalhes(id, tipo) {
        window.location.href = `ver_detalhes.php?id=${id}&tipo=${tipo}`;
    }

    // Exportar relatório (placeholder)
    function exportarRelatorio() {
        alert('Funcionalidade de exportação será implementada em breve!\n\nFormatos disponíveis:\n- PDF\n- Excel\n- CSV');
    }
</script>

<!-- Modal: Exportar Relatório -->
<div id="modalExportar" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <div class="modal-header-content">
                <i class="fas fa-download"></i>
                <h2>Exportar Relatório</h2>
            </div>
            <span class="close" onclick="closeModal('modalExportar')">&times;</span>
        </div>

        <div style="padding: 20px;">
            <p style="margin-bottom: 20px; color: var(--text-light);">
                Escolha o formato de exportação para baixar o relatório com os filtros aplicados:
            </p>

            <div style="display: flex; flex-direction: column; gap: 15px;">
                <!-- CSV -->
                <button onclick="exportarRelatorio('csv')" class="btn-export" style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid var(--border-light); border-radius: 8px; background: var(--panel-color); cursor: pointer; transition: var(--tran-02);">
                    <i class="fas fa-file-csv" style="font-size: 32px; color: #10b981;"></i>
                    <div style="text-align: left; flex: 1;">
                        <strong style="display: block; font-size: 16px; color: var(--text-color);">CSV (Excel)</strong>
                        <small style="color: var(--text-light);">Formato compatível com Excel e planilhas</small>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--text-light);"></i>
                </button>

                <!-- Excel -->
                <button onclick="exportarRelatorio('xlsx')" class="btn-export" style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid var(--border-light); border-radius: 8px; background: var(--panel-color); cursor: pointer; transition: var(--tran-02);">
                    <i class="fas fa-file-excel" style="font-size: 32px; color: #059669;"></i>
                    <div style="text-align: left; flex: 1;">
                        <strong style="display: block; font-size: 16px; color: var(--text-color);">Excel (XLSX)</strong>
                        <small style="color: var(--text-light);">Formato nativo do Microsoft Excel</small>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--text-light);"></i>
                </button>

                <!-- PDF -->
                <button onclick="exportarRelatorio('pdf')" class="btn-export" style="display: flex; align-items: center; gap: 15px; padding: 15px; border: 2px solid var(--border-light); border-radius: 8px; background: var(--panel-color); cursor: pointer; transition: var(--tran-02);">
                    <i class="fas fa-file-pdf" style="font-size: 32px; color: #ef4444;"></i>
                    <div style="text-align: left; flex: 1;">
                        <strong style="display: block; font-size: 16px; color: var(--text-color);">PDF</strong>
                        <small style="color: var(--text-light);">Documento portátil para visualização</small>
                    </div>
                    <i class="fas fa-chevron-right" style="color: var(--text-light);"></i>
                </button>
            </div>

            <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 6px; border: 1px solid #fbbf24;">
                <i class="fas fa-info-circle" style="color: #f59e0b; margin-right: 8px;"></i>
                <small style="color: #92400e;">
                    <strong>Atenção:</strong> O relatório será exportado com os filtros atualmente aplicados.
                </small>
            </div>
        </div>
    </div>
</div>

<script>
    // Função para exportar relatório
    function exportarRelatorio(formato) {
        // Pega todos os parâmetros da URL atual (filtros)
        const urlParams = new URLSearchParams(window.location.search);

        // Adiciona o formato
        urlParams.set('formato', formato);

        // Monta a URL de exportação
        const url = '../../config/validacao/process_exportar_relatorio.php?' + urlParams.toString();

        // Fecha o modal
        closeModal('modalExportar');

        // Abre em nova aba para download
        window.open(url, '_blank');
    }
</script>

<style>
    /* Estilos do modal de exportação */
    .btn-export:hover {
        border-color: var(--primary-color) !important;
        transform: translateX(5px);
        box-shadow: var(--shadow-md);
    }

    .btn-export:active {
        transform: translateX(5px) scale(0.98);
    }
</style>

<?php require_once 'footer.php'; ?>