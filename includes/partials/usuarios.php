<?php
$pageTitle = "Gerenciamento de Usuários";
require_once 'header.php';

// Verifica se é Super Admin OU Gestor
requirePermission(['super_admin', 'gestor']);

// Define se é gestor para filtrar por setor
$isGestor = $_SESSION['tipo'] === 'gestor';
$userSetorId = null;

// Busca o setor do usuário logado se for gestor
if ($isGestor) {
    $stmt_setor = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt_setor->execute(['id' => $_SESSION['user_id']]);
    $userData = $stmt_setor->fetch();
    $userSetorId = $userData['setor_id'] ?? null;
}

// Query base para buscar usuários COM INDICADORES
$query = "
    SELECT 
        u.*,
        s.nome as setor_nome,
        (SELECT COUNT(*) FROM boas_praticas WHERE usuario_id = u.id) as total_boas_praticas,
        (SELECT COUNT(*) FROM boas_praticas WHERE usuario_id = u.id AND status = 'aprovado') as bp_aprovadas,
        (SELECT COUNT(*) FROM nao_conformidades WHERE usuario_id = u.id) as total_nao_conformidades,
        (SELECT COUNT(*) FROM nao_conformidades WHERE usuario_id = u.id AND status = 'resolvido') as nc_resolvidas
    FROM usuarios u
    LEFT JOIN setores s ON u.setor_id = s.id
";

// Se for gestor, filtra apenas usuários do seu setor (exceto outros gestores e admins)
if ($isGestor) {
    $query .= " WHERE u.setor_id = :setor_id AND u.tipo = 'usuario'";
    $query .= " ORDER BY u.status DESC, u.nome ASC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute(['setor_id' => $userSetorId]);
} else {
    // Super Admin vê todos
    $query .= " ORDER BY u.status DESC, u.tipo, u.nome ASC";
    $stmt = $conn->query($query);
}

$usuarios = $stmt->fetchAll();

// Calcula métricas gerais
$total_usuarios = count($usuarios);
$usuarios_ativos = count(array_filter($usuarios, fn($u) => $u['status'] === 'ativo'));
$total_admins = count(array_filter($usuarios, fn($u) => $u['tipo'] === 'super_admin'));
$total_gestores = count(array_filter($usuarios, fn($u) => $u['tipo'] === 'gestor'));
$total_comuns = count(array_filter($usuarios, fn($u) => $u['tipo'] === 'usuario'));

// Calcula engajamento (usuários com pelo menos 1 registro)
$usuarios_engajados = count(array_filter($usuarios, fn($u) => 
    ($u['total_boas_praticas'] + $u['total_nao_conformidades']) > 0
));
$taxa_engajamento = $total_usuarios > 0 ? round(($usuarios_engajados / $total_usuarios) * 100, 1) : 0;

// Total de registros de todos os usuários
$total_registros_geral = array_sum(array_column($usuarios, 'total_boas_praticas')) + 
                         array_sum(array_column($usuarios, 'total_nao_conformidades'));
?>

<div class="overview">
    <div class="title">
        <i class="fas fa-users"></i>
        <span class="text">
            <?php echo $isGestor ? 'Colaboradores do Meu Setor' : 'Gerenciamento de Usuários'; ?>
        </span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <p style="color: #666;">
            <?php if ($isGestor): ?>
                Gerencie os colaboradores do seu setor
            <?php else: ?>
                Gerencie todos os usuários do sistema, permissões e vínculos com setores
            <?php endif; ?>
        </p>
        <?php if (!$isGestor): ?>
            <button onclick="openModalUsuario('create')" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Novo Usuário
            </button>
        <?php else: ?>
            <button onclick="openModalUsuario('create')" class="btn btn-success">
                <i class="fas fa-user-plus"></i> Novo Colaborador
            </button>
        <?php endif; ?>
    </div>

    <!-- Cards de Resumo COM INDICADORES -->
    <div class="boxes" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
        <div class="box box1">
            <i class="fas fa-users"></i>
            <span class="text"><?php echo $isGestor ? 'Total de Colaboradores' : 'Total de Usuários'; ?></span>
            <span class="number"><?php echo $total_usuarios; ?></span>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span>Ativos: <?php echo $usuarios_ativos; ?> | Inativos: <?php echo $total_usuarios - $usuarios_ativos; ?></span>
            </div>
        </div>

        <div class="box box2">
            <i class="fas fa-chart-line"></i>
            <span class="text">Taxa de Engajamento</span>
            <span class="number"><?php echo $taxa_engajamento; ?>%</span>
            <div style="margin-top: 10px; font-size: 11px; color: #10b981;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                    <span>Ativos: <?php echo $usuarios_engajados; ?></span>&nbsp;
                    <span>de <?php echo $total_usuarios; ?></span>
                </div>
                <div style="width: 100%; background: #d1fae5; border-radius: 10px; height: 6px; overflow: hidden;">
                    <div style="width: <?php echo $taxa_engajamento; ?>%; background: #10b981; height: 100%;"></div>
                </div>
            </div>
        </div>

        <div class="box box3">
            <i class="fas fa-clipboard-list"></i>
            <span class="text">Total de Registros</span>
            <span class="number"><?php echo $total_registros_geral; ?></span>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span>Registros criados <?php echo $isGestor ? 'pelos colaboradores' : 'por todos os usuários'; ?></span>
            </div>
        </div>

        <?php if (!$isGestor): ?>
        <div class="box box4">
            <i class="fas fa-user-shield"></i>
            <span class="text">Por Tipo</span>
            <div style="margin-top: 8px; font-size: 11px; color: #666; line-height: 1.6;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Admins:</span>
                    <strong><?php echo $total_admins; ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Gestores:</span> &nbsp;
                    <strong><?php echo $total_gestores; ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Usuários:</span>
                    <strong><?php echo $total_comuns; ?></strong>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="box box4">
            <i class="fas fa-building"></i>
            <span class="text">Meu Setor</span>
            <div style="margin-top: 8px; font-size: 11px; color: #666; line-height: 1.6;">
                <?php
                $stmt = $conn->prepare("SELECT nome FROM setores WHERE id = :id");
                $stmt->execute(['id' => $userSetorId]);
                $setor = $stmt->fetch();
                ?>
                <strong style="font-size: 13px; color: #2563eb;">
                    <?php echo sanitize($setor['nome'] ?? 'Não definido'); ?>
                </strong>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela de Usuários -->
<div class="activity">
    <div class="title">
        <i class="fas fa-list"></i>
        <span class="text"><?php echo $isGestor ? 'Lista de Colaboradores' : 'Lista de Usuários'; ?></span>
    </div>

    <div class="activity-data">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Username</th>
                    <th>Email</th>
                    <?php if (!$isGestor): ?>
                        <th>Tipo</th>
                        <th>Setor</th>
                    <?php endif; ?>
                    <th>Registros</th>
                    <th>Desempenho</th>
                    <th>Status</th>
                    <th>Último Login</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <?php 
                    // Calcula taxa de aprovação do usuário
                    $taxa_aprovacao_usuario = $usuario['total_boas_praticas'] > 0 
                        ? round(($usuario['bp_aprovadas'] / $usuario['total_boas_praticas']) * 100, 1) 
                        : 0;
                    
                    // Calcula taxa de resolução do usuário
                    $taxa_resolucao_usuario = $usuario['total_nao_conformidades'] > 0 
                        ? round(($usuario['nc_resolvidas'] / $usuario['total_nao_conformidades']) * 100, 1) 
                        : 0;
                    ?>
                    <tr>
                        <td><?php echo $usuario['id']; ?></td>
                        <td>
                            <strong><?php echo sanitize($usuario['nome']); ?></strong>
                        </td>
                        <td>
                            <span style="font-family: monospace; color: #666;">
                                <?php echo sanitize($usuario['username']); ?>
                            </span>
                        </td>
                        <td><?php echo sanitize($usuario['email'] ?? '-'); ?></td>
                        <?php if (!$isGestor): ?>
                            <td>
                                <?php
                                $tipo_badges = [
                                    'super_admin' => '<span class="badge badge-danger"><i class="fas fa-crown"></i> Super Admin</span>',
                                    'gestor' => '<span class="badge badge-warning"><i class="fas fa-user-tie"></i> Gestor</span>',
                                    'usuario' => '<span class="badge badge-info"><i class="fas fa-user"></i> Usuário</span>'
                                ];
                                echo $tipo_badges[$usuario['tipo']] ?? '<span class="badge badge-secondary">' . $usuario['tipo'] . '</span>';
                                ?>
                            </td>
                            <td>
                                <?php if ($usuario['setor_nome']): ?>
                                    <span class="badge badge-primary">
                                        <i class="fas fa-building"></i> <?php echo sanitize($usuario['setor_nome']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">Sem setor</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td>
                            <small>
                                <span style="color: #10b981;" title="Boas Práticas">
                                    <i class="fas fa-leaf"></i> <?php echo $usuario['total_boas_praticas']; ?>
                                </span>
                                |
                                <span style="color: #ef4444;" title="Não Conformidades">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $usuario['total_nao_conformidades']; ?>
                                </span>
                            </small>
                        </td>
                        <td>
                            <?php if ($usuario['total_boas_praticas'] > 0 || $usuario['total_nao_conformidades'] > 0): ?>
                                <div style="font-size: 11px;">
                                    <div style="color: #059669; margin-bottom: 2px;">
                                        ✓ <?php echo $taxa_aprovacao_usuario; ?>% aprovação
                                    </div>
                                    <div style="color: #ea580c;">
                                        ✓ <?php echo $taxa_resolucao_usuario; ?>% resolução
                                    </div>
                                </div>
                            <?php else: ?>
                                <span style="color: #999; font-size: 11px;">Sem dados</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($usuario['status'] === 'ativo'): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small><?php echo $usuario['ultimo_login'] ? formatDateTime($usuario['ultimo_login']) : 'Nunca'; ?></small>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <!-- Botão Editar -->
                                <button 
                                    onclick="editarUsuario(<?php echo htmlspecialchars(json_encode($usuario)); ?>)" 
                                    class="btn-sm btn-warning" 
                                    title="Editar <?php echo $isGestor ? 'colaborador' : 'usuário'; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Botão Resetar Senha -->
                                <button 
                                    onclick="resetarSenha(<?php echo $usuario['id']; ?>, '<?php echo sanitize($usuario['nome']); ?>')" 
                                    class="btn-sm btn-info" 
                                    title="Resetar senha">
                                    <i class="fas fa-key"></i>
                                </button>

                                <!-- Botão Deletar (apenas para Super Admin) -->
                                <?php if (!$isGestor): ?>
                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                        <button 
                                            onclick="deletarUsuario(<?php echo $usuario['id']; ?>, '<?php echo sanitize($usuario['nome']); ?>')" 
                                            class="btn-sm btn-danger" 
                                            title="Deletar usuário">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button 
                                            class="btn-sm btn-secondary" 
                                            title="Você não pode deletar sua própria conta"
                                            disabled>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Variável global para saber se é gestor
const isGestor = <?php echo $isGestor ? 'true' : 'false'; ?>;
const userSetorId = <?php echo $userSetorId ?? 'null'; ?>;

// Abrir modal para criar ou editar
function openModalUsuario(mode, data = null) {
    const modal = document.getElementById('modalUsuario');
    const form = document.getElementById('formUsuario');
    const title = document.getElementById('modalUsuarioTitleText');
    const btnText = document.getElementById('btnSubmitTextUsuario');
    const passwordInput = document.getElementById('password_usuario');
    const passwordHint = document.getElementById('password-hint');
    const tipoSelect = document.getElementById('tipo_usuario');
    const setorSelect = document.getElementById('setor_id_usuario');
    
    form.reset();
    
    if (mode === 'create') {
        document.getElementById('action_usuario').value = 'create';
        document.getElementById('usuario_id').value = '';
        title.textContent = isGestor ? 'Novo Colaborador' : 'Novo Usuário';
        btnText.textContent = isGestor ? 'Criar Colaborador' : 'Criar Usuário';
        passwordInput.required = true;
        passwordHint.textContent = 'Obrigatório ao criar novo usuário';
        
        // Se for gestor, define o tipo como 'usuario' e o setor automaticamente
        if (isGestor) {
            tipoSelect.value = 'usuario';
            tipoSelect.disabled = true;
            setorSelect.value = userSetorId;
            setorSelect.disabled = true;
        } else {
            tipoSelect.disabled = false;
            setorSelect.disabled = false;
        }
    } else if (mode === 'edit' && data) {
        document.getElementById('action_usuario').value = 'edit';
        document.getElementById('usuario_id').value = data.id;
        document.getElementById('nome_usuario').value = data.nome;
        document.getElementById('username').value = data.username;
        document.getElementById('email_usuario').value = data.email || '';
        document.getElementById('tipo_usuario').value = data.tipo;
        document.getElementById('setor_id_usuario').value = data.setor_id || '';
        document.getElementById('status_usuario').value = data.status;
        title.textContent = isGestor ? 'Editar Colaborador' : 'Editar Usuário';
        btnText.textContent = 'Salvar Alterações';
        passwordInput.required = false;
        passwordInput.value = '';
        passwordHint.textContent = 'Deixe em branco para manter a senha atual';
        
        // Se for gestor, não permite alterar tipo e setor
        if (isGestor) {
            tipoSelect.disabled = true;
            setorSelect.disabled = true;
        } else {
            tipoSelect.disabled = false;
            setorSelect.disabled = false;
        }
    }
    
    modal.style.display = 'block';
}

// Editar usuário
function editarUsuario(usuario) {
    openModalUsuario('edit', usuario);
}

// Resetar senha
function resetarSenha(id, nome) {
    document.getElementById('reset_usuario_id').value = id;
    document.getElementById('usuarioNomeReset').textContent = nome;
    document.getElementById('formResetSenha').reset();
    document.getElementById('modalResetSenha').style.display = 'block';
}

// Validar confirmação de senha
document.getElementById('formResetSenha').addEventListener('submit', function(e) {
    const senha = document.getElementById('nova_senha').value;
    const confirma = document.getElementById('confirma_senha').value;
    
    if (senha !== confirma) {
        e.preventDefault();
        alert('As senhas não conferem!');
        return false;
    }
});

// Deletar usuário (apenas Super Admin)
function deletarUsuario(id, nome) {
    if (confirm(`Tem certeza que deseja DELETAR o usuário "${nome}"?\n\nTodos os registros deste usuário serão mantidos, mas ele não poderá mais acessar o sistema.\n\nEsta ação não pode ser desfeita!`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../config/validacao/process_usuario.php';
        
        const inputAction = document.createElement('input');
        inputAction.type = 'hidden';
        inputAction.name = 'action';
        inputAction.value = 'delete';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'usuario_id';
        inputId.value = id;
        
        form.appendChild(inputAction);
        form.appendChild(inputId);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'footer.php'; ?>