<?php
$pageTitle = "Dashboard";
require_once 'header.php';
require_once __DIR__ . '/../../config/functions.php';

// Busca informações do usuário
$user_id = $_SESSION['user_id'];
$tipo = $_SESSION['tipo'];
$setor_id = null;

// Busca setor do usuário se não for super admin
if ($tipo !== 'super_admin') {
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    $setor_id = $user_data['setor_id'] ?? null;
}

// Busca estatísticas
$stats = getDashboardStats($conn, $user_id, $tipo, $setor_id);

// Busca atividades recentes
$activities = getRecentActivities($conn, $user_id, $tipo, $setor_id, 10);

// ✅ LINHA DUPLICADA REMOVIDA - modais_globais.php já está incluído no header.php
?>

<div class="overview">
    <div class="title">
        <i class="fas fa-chart-line"></i>
        <span class="text">Visão Geral</span>
    </div>

    <div class="boxes">
        <!-- Card: Boas Práticas (CLICÁVEL) -->
        <div class="box box1" onclick="openModal('boasPraticasModal')" style="cursor: pointer;" title="Clique para registrar uma boa prática">
            <i class="fas fa-leaf"></i>
            <span class="text">Boas Práticas</span>
            <span class="number"><?php echo $stats['total_boas_praticas']; ?></span>
            <small style="font-size: 12px; color: #059669; margin-top: 5px;">Clique para adicionar</small>
        </div>

        <!-- Card: Não Conformidades (CLICÁVEL) -->
        <div class="box box2" onclick="openModal('naoConformidadesModal')" style="cursor: pointer;" title="Clique para reportar uma não conformidade">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="text">Não Conformidades</span>
            <span class="number"><?php echo $stats['total_nao_conformidades']; ?></span>
            <small style="font-size: 12px; color: #dc2626; margin-top: 5px;">Clique para reportar</small>
        </div>

        <?php if (isSuperAdmin()): ?>
            <!-- Card: Usuários (apenas super admin) - CLICÁVEL -->
            <div class="box box3" onclick="window.location.href='usuarios.php'" style="cursor: pointer;" title="Clique para gerenciar usuários">
                <i class="fas fa-users"></i>
                <span class="text">Usuários Ativos</span>
                <span class="number"><?php echo $stats['total_usuarios']; ?></span>
                <small style="font-size: 12px; color: #3b82f6; margin-top: 5px;">Clique para gerenciar</small>
            </div>

            <!-- Card: Setores (apenas super admin) - CLICÁVEL -->
            <div class="box box4" onclick="window.location.href='setores.php'" style="cursor: pointer;" title="Clique para gerenciar setores">
                <i class="fas fa-building"></i>
                <span class="text">Setores Ativos</span>
                <span class="number"><?php echo $stats['total_setores']; ?></span>
                <small style="font-size: 12px; color: #f59e0b; margin-top: 5px;">Clique para gerenciar</small>
            </div>
        <?php elseif (isGestor()): ?>
            <!-- Card: Colaboradores do Setor (gestor) - CLICÁVEL -->
            <div class="box box3" onclick="window.location.href='usuarios.php'" style="cursor: pointer;" title="Clique para gerenciar colaboradores">
                <i class="fas fa-users"></i>
                <span class="text">Colaboradores</span>
                <span class="number"><?php echo $stats['total_usuarios']; ?></span>
                <small style="font-size: 12px; color: #3b82f6; margin-top: 5px;">Clique para gerenciar</small>
            </div>

            <!-- Card: Relatórios (gestor) - CLICÁVEL -->
            <div class="box box4" onclick="window.location.href='relatorios.php'" style="cursor: pointer;" title="Clique para ver relatórios">
                <i class="fas fa-file-alt"></i>
                <span class="text">Ver Relatórios</span>
                <span class="number"><?php echo $stats['total_boas_praticas'] + $stats['total_nao_conformidades']; ?></span>
                <small style="font-size: 12px; color: #8b5cf6; margin-top: 5px;">Clique para acessar</small>
            </div>
        <?php else: ?>
            <!-- Cards para usuário comum -->
            <div class="box box3">
                <i class="fas fa-check-circle"></i>
                <span class="text">Práticas Aprovadas</span>
                <span class="number">
                    <?php
                    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE usuario_id = :user_id AND status = 'aprovado'");
                    $stmt->execute(['user_id' => $user_id]);
                    echo $stmt->fetch()['total'];
                    ?>
                </span>
            </div>

            <div class="box box4" onclick="window.location.href='relatorios.php'" style="cursor: pointer;" title="Clique para ver seus relatórios">
                <i class="fas fa-file-alt"></i>
                <span class="text">Meus Relatórios</span>
                <span class="number"><?php echo $stats['total_boas_praticas'] + $stats['total_nao_conformidades']; ?></span>
                <small style="font-size: 12px; color: #8b5cf6; margin-top: 5px;">Clique para acessar</small>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- Atividades Recentes -->
<div class="activity" style="margin-top: 30px;">
    <div class="title">
        <i class="fas fa-history"></i>
        <span class="text">Atividades Recentes</span>
    </div>

    <div class="activity-data">
        <?php if (empty($activities)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 10px;"></i>
                <p>Nenhuma atividade registrada ainda.</p>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Título</th>
                        <th>Status</th>
                        <?php if ($tipo !== 'usuario'): ?>
                            <th>Usuário</th>
                            <th>Setor</th>
                        <?php endif; ?>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $activity): ?>
                        <tr>
                            <td>
                                <?php if ($activity['tipo'] === 'boa_pratica'): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-leaf"></i> Boa Prática
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-danger">
                                        <i class="fas fa-exclamation-triangle"></i> Não Conformidade
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo sanitize($activity['titulo']); ?></td>
                            <td><?php echo getStatusBadge($activity['status'], $activity['tipo']); ?></td>
                            <?php if ($tipo !== 'usuario'): ?>
                                <td><?php echo sanitize($activity['usuario_nome'] ?? '-'); ?></td>
                                <td><?php echo sanitize($activity['setor_nome'] ?? '-'); ?></td>
                            <?php endif; ?>
                            <td><?php echo formatDateTime($activity['criado_em']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'footer.php'; ?>