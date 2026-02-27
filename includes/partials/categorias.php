<?php
$pageTitle = "Gerenciamento de Categorias";
require_once 'header.php';

// Verifica se é Super Admin ou Gestor (ambos podem acessar)
requirePermission('gestor');

$user_tipo = $_SESSION['tipo'];
$user_setor_id = null;

// Se for gestor, busca o setor
if ($user_tipo === 'gestor') {
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user_data = $stmt->fetch();
    $user_setor_id = $user_data['setor_id'] ?? null;
}

// Busca Categorias de Boas Práticas COM INDICADORES DE USO
if ($user_tipo === 'super_admin') {
    // Super admin vê todas
    $stmt = $conn->query("
        SELECT 
            cbp.*,
            s.nome as setor_nome,
            u.nome as criador_nome,
            (SELECT COUNT(*) FROM boas_praticas WHERE categoria_id = cbp.id) as total_registros,
            (SELECT COUNT(*) FROM boas_praticas WHERE categoria_id = cbp.id AND status = 'aprovado') as registros_aprovados
        FROM categorias_boas_praticas cbp
        LEFT JOIN setores s ON cbp.setor_id = s.id
        LEFT JOIN usuarios u ON cbp.criado_por = u.id
        ORDER BY cbp.status DESC, cbp.nome ASC
    ");
    $categorias_bp = $stmt->fetchAll();
} else {
    // Gestor vê apenas do seu setor e globais
    $stmt = $conn->prepare("
        SELECT 
            cbp.*,
            s.nome as setor_nome,
            u.nome as criador_nome,
            (SELECT COUNT(*) FROM boas_praticas WHERE categoria_id = cbp.id) as total_registros,
            (SELECT COUNT(*) FROM boas_praticas WHERE categoria_id = cbp.id AND status = 'aprovado') as registros_aprovados
        FROM categorias_boas_praticas cbp
        LEFT JOIN setores s ON cbp.setor_id = s.id
        LEFT JOIN usuarios u ON cbp.criado_por = u.id
        WHERE cbp.setor_id IS NULL OR cbp.setor_id = :setor_id
        ORDER BY cbp.status DESC, cbp.nome ASC
    ");
    $stmt->execute(['setor_id' => $user_setor_id]);
    $categorias_bp = $stmt->fetchAll();
}

// Busca Categorias de Não Conformidades COM INDICADORES DE USO
if ($user_tipo === 'super_admin') {
    $stmt = $conn->query("
        SELECT 
            cnc.*,
            s.nome as setor_nome,
            u.nome as criador_nome,
            (SELECT COUNT(*) FROM nao_conformidades WHERE categoria_id = cnc.id) as total_registros,
            (SELECT COUNT(*) FROM nao_conformidades WHERE categoria_id = cnc.id AND status = 'resolvido') as registros_resolvidos
        FROM categorias_nao_conformidades cnc
        LEFT JOIN setores s ON cnc.setor_id = s.id
        LEFT JOIN usuarios u ON cnc.criado_por = u.id
        ORDER BY cnc.status DESC, cnc.nome ASC
    ");
    $categorias_nc = $stmt->fetchAll();
} else {
    $stmt = $conn->prepare("
        SELECT 
            cnc.*,
            s.nome as setor_nome,
            u.nome as criador_nome,
            (SELECT COUNT(*) FROM nao_conformidades WHERE categoria_id = cnc.id) as total_registros,
            (SELECT COUNT(*) FROM nao_conformidades WHERE categoria_id = cnc.id AND status = 'resolvido') as registros_resolvidos
        FROM categorias_nao_conformidades cnc
        LEFT JOIN setores s ON cnc.setor_id = s.id
        LEFT JOIN usuarios u ON cnc.criado_por = u.id
        WHERE cnc.setor_id IS NULL OR cnc.setor_id = :setor_id
        ORDER BY cnc.status DESC, cnc.nome ASC
    ");
    $stmt->execute(['setor_id' => $user_setor_id]);
    $categorias_nc = $stmt->fetchAll();
}

// Busca setores para o select (apenas super admin)
if ($user_tipo === 'super_admin') {
    $stmt = $conn->query("SELECT id, nome FROM setores WHERE status = 'ativo' ORDER BY nome");
    $setores = $stmt->fetchAll();
} else {
    $setores = [];
}

// Calcula estatísticas
$total_bp = array_sum(array_column($categorias_bp, 'total_registros'));
$total_nc = array_sum(array_column($categorias_nc, 'total_registros'));
$ativas_bp = count(array_filter($categorias_bp, fn($c) => $c['status'] === 'ativo'));
$ativas_nc = count(array_filter($categorias_nc, fn($c) => $c['status'] === 'ativo'));

// Categoria mais usada BP
$cat_mais_usada_bp = null;
$max_uso_bp = 0;
foreach ($categorias_bp as $cat) {
    if ($cat['total_registros'] > $max_uso_bp) {
        $max_uso_bp = $cat['total_registros'];
        $cat_mais_usada_bp = $cat;
    }
}

// Categoria mais usada NC
$cat_mais_usada_nc = null;
$max_uso_nc = 0;
foreach ($categorias_nc as $cat) {
    if ($cat['total_registros'] > $max_uso_nc) {
        $max_uso_nc = $cat['total_registros'];
        $cat_mais_usada_nc = $cat;
    }
}
?>

<div class="overview">
    <div class="title">
        <i class="fas fa-tags"></i>
        <span class="text">Gerenciamento de Categorias</span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <p style="color: #666;">
            <?php if ($user_tipo === 'super_admin'): ?>
                Gerencie categorias globais e específicas de setores
            <?php else: ?>
                Gerencie categorias do seu setor
            <?php endif; ?>
        </p>
    </div>

    <!-- Cards de Resumo COM INDICADORES -->
    <div class="boxes" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
        <div class="box box1">
            <i class="fas fa-leaf"></i>
            <span class="text">Cat. Boas Práticas</span>
            <span class="number"><?php echo count($categorias_bp); ?></span>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span>Ativas: <?php echo $ativas_bp; ?> | Total de usos: <?php echo $total_bp; ?></span>
            </div>
        </div>

        <div class="box box2">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="text">Cat. Não Conformidades</span>
            <span class="number"><?php echo count($categorias_nc); ?></span>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span>Ativas: <?php echo $ativas_nc; ?> | Total de usos: <?php echo $total_nc; ?></span>
            </div>
        </div>

        <?php if ($cat_mais_usada_bp): ?>
        <div class="box box3">
            <i class="fas fa-star"></i>
            <span class="text">BP Mais Popular</span>
            <div style="margin-top: 5px; font-size: 14px; color: #059669;">
                <strong><?php echo sanitize($cat_mais_usada_bp['nome']); ?></strong>
            </div>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span><?php echo $max_uso_bp; ?> usos | Taxa: <?php echo $cat_mais_usada_bp['total_registros'] > 0 ? round(($cat_mais_usada_bp['registros_aprovados'] / $cat_mais_usada_bp['total_registros']) * 100, 1) : 0; ?>% aprovação</span>
            </div>
        </div>
        <?php else: ?>
        <div class="box box3">
            <i class="fas fa-check-circle"></i>
            <span class="text">Categorias Ativas</span>
            <span class="number">
                <?php echo $ativas_bp + $ativas_nc; ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if ($cat_mais_usada_nc): ?>
        <div class="box box4">
            <i class="fas fa-chart-line"></i>
            <span class="text">NC Mais Comum</span>
            <div style="margin-top: 5px; font-size: 14px; color: #dc2626;">
                <strong><?php echo sanitize($cat_mais_usada_nc['nome']); ?></strong>
            </div>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span><?php echo $max_uso_nc; ?> usos | Taxa: <?php echo $cat_mais_usada_nc['total_registros'] > 0 ? round(($cat_mais_usada_nc['registros_resolvidos'] / $cat_mais_usada_nc['total_registros']) * 100, 1) : 0; ?>% resolução</span>
            </div>
        </div>
        <?php else: ?>
        <div class="box box4">
            <i class="fas fa-clipboard-list"></i>
            <span class="text">Total de Registros</span>
            <span class="number">
                <?php echo $total_bp + $total_nc; ?>
            </span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Abas -->
<div class="activity">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="tabs" style="display: flex; gap: 10px;">
            <button class="tab-btn active" onclick="switchTab('boas-praticas')" id="tab-bp">
                <i class="fas fa-leaf"></i> Boas Práticas
            </button>
            <button class="tab-btn" onclick="switchTab('nao-conformidades')" id="tab-nc">
                <i class="fas fa-exclamation-triangle"></i> Não Conformidades
            </button>
        </div>
    </div>

    <!-- TAB: BOAS PRÁTICAS -->
    <div id="tab-content-boas-praticas" class="tab-content active">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Categorias de Boas Práticas</h3>
            <button onclick="openModalCategoria('create', 'boas_praticas')" class="btn btn-success">
                <i class="fas fa-plus"></i> Nova Categoria
            </button>
        </div>

        <div class="activity-data">
            <?php if (empty($categorias_bp)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-tags" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>Nenhuma categoria de Boas Práticas cadastrada.</p>
                    <button onclick="openModalCategoria('create', 'boas_praticas')" class="btn btn-success" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Criar Primeira Categoria
                    </button>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ícone</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <?php if ($user_tipo === 'super_admin'): ?>
                                <th>Setor</th>
                            <?php endif; ?>
                            <th>Total de Usos</th>
                            <th>Taxa de Aprovação</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias_bp as $cat): ?>
                            <?php 
                            $taxa_aprovacao = $cat['total_registros'] > 0 
                                ? round(($cat['registros_aprovados'] / $cat['total_registros']) * 100, 1) 
                                : 0;
                            ?>
                            <tr>
                                <td>
                                    <div style="width: 40px; height: 40px; border-radius: 8px; background-color: <?php echo $cat['cor']; ?>; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas <?php echo $cat['icone']; ?>" style="font-size: 20px;"></i>
                                    </div>
                                </td>
                                <td><strong><?php echo sanitize($cat['nome']); ?></strong></td>
                                <td><?php echo sanitize($cat['descricao'] ?? '-'); ?></td>
                                <?php if ($user_tipo === 'super_admin'): ?>
                                    <td>
                                        <?php if ($cat['setor_nome']): ?>
                                            <span class="badge badge-info"><?php echo sanitize($cat['setor_nome']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Global</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge badge-primary">
                                        <?php echo $cat['total_registros']; ?> registros
                                    </span>
                                </td>
                                <td>
                                    <?php if ($cat['total_registros'] > 0): ?>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="flex: 1; background: #e5e7eb; border-radius: 10px; height: 8px; overflow: hidden;">
                                                <div style="width: <?php echo $taxa_aprovacao; ?>%; background: #059669; height: 100%;"></div>
                                            </div>
                                            <span style="font-size: 12px; font-weight: 600; color: #059669;">
                                                <?php echo $taxa_aprovacao; ?>%
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">Sem dados</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cat['status'] === 'ativo'): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick='editarCategoria(<?php echo json_encode($cat); ?>, "boas_praticas")' 
                                            class="btn btn-sm btn-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($cat['total_registros'] == 0): ?>
                                        <button onclick="deletarCategoria(<?php echo $cat['id']; ?>, '<?php echo sanitize($cat['nome']); ?>', 'boas_praticas')" 
                                                class="btn btn-sm btn-danger" title="Deletar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- TAB: NÃO CONFORMIDADES -->
    <div id="tab-content-nao-conformidades" class="tab-content" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0;">Categorias de Não Conformidades</h3>
            <button onclick="openModalCategoria('create', 'nao_conformidades')" class="btn btn-danger">
                <i class="fas fa-plus"></i> Nova Categoria
            </button>
        </div>

        <div class="activity-data">
            <?php if (empty($categorias_nc)): ?>
                <div style="text-align: center; padding: 40px; color: #999;">
                    <i class="fas fa-tags" style="font-size: 48px; margin-bottom: 10px;"></i>
                    <p>Nenhuma categoria de Não Conformidades cadastrada.</p>
                    <button onclick="openModalCategoria('create', 'nao_conformidades')" class="btn btn-danger" style="margin-top: 15px;">
                        <i class="fas fa-plus"></i> Criar Primeira Categoria
                    </button>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ícone</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <?php if ($user_tipo === 'super_admin'): ?>
                                <th>Setor</th>
                            <?php endif; ?>
                            <th>Total de Usos</th>
                            <th>Taxa de Resolução</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias_nc as $cat): ?>
                            <?php 
                            $taxa_resolucao = $cat['total_registros'] > 0 
                                ? round(($cat['registros_resolvidos'] / $cat['total_registros']) * 100, 1) 
                                : 0;
                            ?>
                            <tr>
                                <td>
                                    <div style="width: 40px; height: 40px; border-radius: 8px; background-color: <?php echo $cat['cor']; ?>; display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas <?php echo $cat['icone']; ?>" style="font-size: 20px;"></i>
                                    </div>
                                </td>
                                <td><strong><?php echo sanitize($cat['nome']); ?></strong></td>
                                <td><?php echo sanitize($cat['descricao'] ?? '-'); ?></td>
                                <?php if ($user_tipo === 'super_admin'): ?>
                                    <td>
                                        <?php if ($cat['setor_nome']): ?>
                                            <span class="badge badge-info"><?php echo sanitize($cat['setor_nome']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Global</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge badge-danger">
                                        <?php echo $cat['total_registros']; ?> registros
                                    </span>
                                </td>
                                <td>
                                    <?php if ($cat['total_registros'] > 0): ?>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="flex: 1; background: #e5e7eb; border-radius: 10px; height: 8px; overflow: hidden;">
                                                <div style="width: <?php echo $taxa_resolucao; ?>%; background: #10b981; height: 100%;"></div>
                                            </div>
                                            <span style="font-size: 12px; font-weight: 600; color: #10b981;">
                                                <?php echo $taxa_resolucao; ?>%
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999; font-size: 12px;">Sem dados</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cat['status'] === 'ativo'): ?>
                                        <span class="badge badge-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button onclick='editarCategoria(<?php echo json_encode($cat); ?>, "nao_conformidades")' 
                                            class="btn btn-sm btn-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($cat['total_registros'] == 0): ?>
                                        <button onclick="deletarCategoria(<?php echo $cat['id']; ?>, '<?php echo sanitize($cat['nome']); ?>', 'nao_conformidades')" 
                                                class="btn btn-sm btn-danger" title="Deletar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Trocar abas
function switchTab(tab) {
    // Remove active de todos
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.style.display = 'none');
    
    // Adiciona active no selecionado
    if (tab === 'boas-praticas') {
        document.getElementById('tab-bp').classList.add('active');
        document.getElementById('tab-content-boas-praticas').style.display = 'block';
    } else {
        document.getElementById('tab-nc').classList.add('active');
        document.getElementById('tab-content-nao-conformidades').style.display = 'block';
    }
}

// Abrir modal - CORRIGIDO COM IDS DO MODAIS_GLOBAIS.PHP
function openModalCategoria(mode, tipo, data = null) {
    const modal = document.getElementById('modalCategoria');
    const form = document.getElementById('formCategoria');
    const title = document.getElementById('modalCategoriaTitleText');
    const btnText = document.getElementById('btnSubmitTextCategoria');
    
    form.reset();
    document.getElementById('tipo_categoria').value = tipo;
    
    if (mode === 'create') {
        document.getElementById('action_categoria').value = 'create';
        document.getElementById('categoria_id').value = '';
        title.textContent = tipo === 'boas_praticas' ? 'Nova Categoria de Boas Práticas' : 'Nova Categoria de Não Conformidades';
        btnText.textContent = 'Criar Categoria';
        
        // Cores padrão
        if (tipo === 'boas_praticas') {
            document.getElementById('cor_categoria').value = '#10b981';
            document.getElementById('icone').value = 'fa-leaf';
        } else {
            document.getElementById('cor_categoria').value = '#ef4444';
            document.getElementById('icone').value = 'fa-exclamation-triangle';
        }
    } else if (mode === 'edit' && data) {
        document.getElementById('action_categoria').value = 'edit';
        document.getElementById('categoria_id').value = data.id;
        document.getElementById('nome_categoria').value = data.nome;
        document.getElementById('descricao_categoria').value = data.descricao || '';
        document.getElementById('icone').value = data.icone;
        document.getElementById('cor_categoria').value = data.cor;
        <?php if ($user_tipo === 'super_admin'): ?>
        document.getElementById('setor_id_categoria').value = data.setor_id || '';
        <?php endif; ?>
        document.getElementById('status_categoria').value = data.status;
        title.textContent = 'Editar Categoria';
        btnText.textContent = 'Salvar Alterações';
    }
    
    // Inicializa o seletor de ícones visual
    inicializarSeletorIcone();
    
    modal.style.display = 'block';
}

// Editar categoria
function editarCategoria(categoria, tipo) {
    openModalCategoria('edit', tipo, categoria);
}

// Deletar categoria
function deletarCategoria(id, nome, tipo) {
    if (confirm(`Tem certeza que deseja DELETAR a categoria "${nome}"?\n\nEsta ação não pode ser desfeita!`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../config/validacao/process_categoria.php';
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'delete';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'categoria_id';
        inputId.value = id;
        
        const inputTipo = document.createElement('input');
        inputTipo.type = 'hidden';
        inputTipo.name = 'tipo_categoria';
        inputTipo.value = tipo;
        
        form.appendChild(inputAction);
        form.appendChild(inputId);
        form.appendChild(inputTipo);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'footer.php'; ?>