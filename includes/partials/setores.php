<?php
$pageTitle = "Gerenciamento de Setores";
require_once 'header.php';

// Verifica se é Super Admin (APENAS SUPER ADMIN PODE ACESSAR)
requirePermission('super_admin');

// Busca todos os setores COM INDICADORES
$stmt = $conn->query("
    SELECT 
        s.*,
        u.nome as gestor_nome,
        (SELECT COUNT(*) FROM usuarios WHERE setor_id = s.id AND status = 'ativo') as total_colaboradores,
        (SELECT COUNT(*) FROM boas_praticas WHERE setor_id = s.id) as total_boas_praticas,
        (SELECT COUNT(*) FROM boas_praticas WHERE setor_id = s.id AND status = 'aprovado') as bp_aprovadas,
        (SELECT COUNT(*) FROM nao_conformidades WHERE setor_id = s.id) as total_nao_conformidades,
        (SELECT COUNT(*) FROM nao_conformidades WHERE setor_id = s.id AND status = 'resolvido') as nc_resolvidas
    FROM setores s
    LEFT JOIN usuarios u ON s.gestor_id = u.id
    ORDER BY s.status DESC, s.nome ASC
");
$setores = $stmt->fetchAll();

// Calcula métricas gerais
$total_setores = count($setores);
$setores_ativos = count(array_filter($setores, fn($s) => $s['status'] === 'ativo'));
$setores_com_gestor = count(array_filter($setores, fn($s) => $s['gestor_id'] !== null));
$total_colaboradores_geral = array_sum(array_column($setores, 'total_colaboradores'));
$total_registros_geral = array_sum(array_column($setores, 'total_boas_praticas')) +
    array_sum(array_column($setores, 'total_nao_conformidades'));

// Setor mais produtivo
$setor_mais_produtivo = null;
$max_registros = 0;
foreach ($setores as $setor) {
    $registros = $setor['total_boas_praticas'] + $setor['total_nao_conformidades'];
    if ($registros > $max_registros) {
        $max_registros = $registros;
        $setor_mais_produtivo = $setor;
    }
}
?>

<div class="overview">
    <div class="title">
        <i class="fas fa-building"></i>
        <span class="text">Gerenciamento de Setores</span>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <p style="color: #666;">
            Gerencie os setores da empresa e vincule gestores responsáveis
        </p>
        <button onclick="openModalSetor('create')" class="btn btn-success">
            <i class="fas fa-plus"></i> Novo Setor
        </button>
    </div>

    <!-- Cards de Resumo COM INDICADORES -->
    <div class="boxes" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 30px;">
        <div class="box box1">
            <i class="fas fa-building"></i>
            <span class="text">Total de Setores</span>
            <span class="number"><?php echo $total_setores; ?></span>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span>Ativos: <?php echo $setores_ativos; ?> | Inativos: <?php echo $total_setores - $setores_ativos; ?></span>
            </div>
        </div>

        <div class="box box2">
            <i class="fas fa-user-tie"></i>
            <span class="text">Setores com Gestor</span>
            <span class="number"><?php echo $setores_com_gestor; ?></span>

            <div style="margin-top: 10px; font-size: 11px; color: #f59e0b;">

                <div style="display: flex; justify-content: space-between; margin-bottom: 8px; column-gap: 6px;">
                    <span>Cobertura</span>

                    <strong style="display: inline-flex; align-items: center; line-height: 1;">
                        <?php echo $total_setores > 0 ? round(($setores_com_gestor / $total_setores) * 100, 1) : 0; ?>%
                    </strong>
                </div>

                <div style="width: 100%; background: #fef3c7; border-radius: 10px; height: 6px; overflow: hidden;">
                    <div style="width: <?php echo $total_setores > 0 ? round(($setores_com_gestor / $total_setores) * 100, 1) : 0; ?>%; background: #f59e0b; height: 100%;"></div>
                </div>
            </div>
        </div>

        <div class="box box3">
            <i class="fas fa-users"></i>
            <span class="text">Total Colaboradores</span>
            <span class="number"><?php echo $total_colaboradores_geral; ?></span>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span>Distribuídos em todos os setores</span>
            </div>
        </div>

        <div class="box box4">
            <i class="fas fa-chart-bar"></i>
            <span class="text">Total de Registros</span>
            <span class="number"><?php echo $total_registros_geral; ?></span>
            <div style="margin-top: 8px; font-size: 11px; color: #666;">
                <span>Boas práticas e não conformidades</span>
            </div>
        </div>

        <?php if ($setor_mais_produtivo): ?>
            <div class="box" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <i class="fas fa-trophy" style="color: white;"></i>
                <span class="text" style="color: white;">Setor Mais Produtivo</span>
                <span class="number" style="color: white; font-size: 16px;">
                    <?php echo sanitize($setor_mais_produtivo['nome']); ?>
                </span>
                <div style="margin-top: 8px; font-size: 11px; color: white;">
                    <span><?php echo $max_registros; ?> registros criados</span>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tabela de Setores -->
<div class="activity">
    <div class="title">
        <i class="fas fa-list"></i>
        <span class="text">Lista de Setores</span>
    </div>

    <div class="activity-data">
        <?php if (empty($setores)): ?>
            <div style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-building" style="font-size: 48px; margin-bottom: 10px;"></i>
                <p>Nenhum setor cadastrado ainda.</p>
                <button onclick="openModalSetor('create')" class="btn btn-success" style="margin-top: 15px;">
                    <i class="fas fa-plus"></i> Criar Primeiro Setor
                </button>
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome do Setor</th>
                        <th>Descrição</th>
                        <th>Gestor</th>
                        <th>Colaboradores</th>
                        <th>Registros</th>
                        <th>Desempenho</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($setores as $setor): ?>
                        <?php
                        // Calcula taxa de aprovação do setor
                        $taxa_aprovacao_setor = $setor['total_boas_praticas'] > 0
                            ? round(($setor['bp_aprovadas'] / $setor['total_boas_praticas']) * 100, 1)
                            : 0;

                        // Calcula taxa de resolução do setor
                        $taxa_resolucao_setor = $setor['total_nao_conformidades'] > 0
                            ? round(($setor['nc_resolvidas'] / $setor['total_nao_conformidades']) * 100, 1)
                            : 0;

                        $total_registros_setor = $setor['total_boas_praticas'] + $setor['total_nao_conformidades'];
                        ?>
                        <tr>
                            <td><?php echo $setor['id']; ?></td>
                            <td>
                                <strong><?php echo sanitize($setor['nome']); ?></strong>
                            </td>
                            <td>
                                <?php
                                $desc = $setor['descricao'] ?? '-';
                                echo sanitize(strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc);
                                ?>
                            </td>
                            <td>
                                <?php if ($setor['gestor_nome']): ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-user-tie"></i> <?php echo sanitize($setor['gestor_nome']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">Sem gestor</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-primary">
                                    <i class="fas fa-users"></i> <?php echo $setor['total_colaboradores']; ?>
                                </span>
                            </td>
                            <td>
                                <small>
                                    <span style="color: #10b981;" title="Boas Práticas">
                                        <i class="fas fa-leaf"></i> <?php echo $setor['total_boas_praticas']; ?>
                                    </span>
                                    |
                                    <span style="color: #ef4444;" title="Não Conformidades">
                                        <i class="fas fa-exclamation-triangle"></i> <?php echo $setor['total_nao_conformidades']; ?>
                                    </span>
                                </small>
                            </td>
                            <td>
                                <?php if ($total_registros_setor > 0): ?>
                                    <div style="font-size: 11px;">
                                        <div style="color: #059669; margin-bottom: 2px;">
                                            ✓ <?php echo $taxa_aprovacao_setor; ?>% aprovação
                                        </div>
                                        <div style="color: #ea580c;">
                                            ✓ <?php echo $taxa_resolucao_setor; ?>% resolução
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 11px;">Sem dados</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($setor['status'] === 'ativo'): ?>
                                    <span class="badge badge-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px;">
                                    <!-- Botão Ver Colaboradores -->
                                    <button
                                        onclick="verColaboradores(<?php echo $setor['id']; ?>, '<?php echo sanitize($setor['nome']); ?>')"
                                        class="btn-sm btn-info"
                                        title="Ver colaboradores">
                                        <i class="fas fa-users"></i>
                                    </button>

                                    <!-- Botão Editar -->
                                    <button
                                        onclick="editarSetor(<?php echo htmlspecialchars(json_encode($setor)); ?>)"
                                        class="btn-sm btn-warning"
                                        title="Editar setor">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <!-- Botão Deletar -->
                                    <?php if ($setor['total_colaboradores'] == 0): ?>
                                        <button
                                            onclick="deletarSetor(<?php echo $setor['id']; ?>, '<?php echo sanitize($setor['nome']); ?>')"
                                            class="btn-sm btn-danger"
                                            title="Deletar setor">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php else: ?>
                                        <button
                                            class="btn-sm btn-secondary"
                                            title="Não é possível deletar setor com colaboradores"
                                            disabled>
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    // Abrir modal para criar ou editar
    function openModalSetor(mode, data = null) {
        const modal = document.getElementById('modalSetor');
        const form = document.getElementById('formSetor');
        const title = document.getElementById('modalSetorTitleText');
        const btnText = document.getElementById('btnSubmitTextSetor');

        form.reset();

        if (mode === 'create') {
            document.getElementById('action').value = 'create';
            document.getElementById('setor_id').value = '';
            title.textContent = 'Novo Setor';
            btnText.textContent = 'Criar Setor';
        } else if (mode === 'edit' && data) {
            document.getElementById('action').value = 'edit';
            document.getElementById('setor_id').value = data.id;
            document.getElementById('nome_setor').value = data.nome;
            document.getElementById('descricao_setor').value = data.descricao || '';
            document.getElementById('gestor_id').value = data.gestor_id || '';
            document.getElementById('status_setor').value = data.status;
            title.textContent = 'Editar Setor';
            btnText.textContent = 'Salvar Alterações';
        }

        modal.style.display = 'block';
    }

    // Editar setor
    function editarSetor(setor) {
        openModalSetor('edit', setor);
    }

    // Deletar setor
    function deletarSetor(id, nome) {
        if (confirm(`Tem certeza que deseja DELETAR o setor "${nome}"?\n\nEsta ação não pode ser desfeita!`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../../config/validacao/process_setor.php';

            const inputAction = document.createElement('input');
            inputAction.type = 'hidden';
            inputAction.name = 'action';
            inputAction.value = 'delete';

            const inputId = document.createElement('input');
            inputId.type = 'hidden';
            inputId.name = 'setor_id';
            inputId.value = id;

            form.appendChild(inputAction);
            form.appendChild(inputId);
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Ver colaboradores do setor
    async function verColaboradores(setorId, setorNome) {
        document.getElementById('setorNomeColaboradores').textContent = setorNome;

        const lista = document.getElementById('listaColaboradores');
        lista.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Carregando...</div>';

        // Abre o modal
        document.getElementById('modalColaboradores').style.display = 'block';

        try {
            const response = await fetch(`../../config/validacao/process_setor.php?action=get_colaboradores&setor_id=${setorId}`);
            const data = await response.json();

            if (data.success && data.colaboradores.length > 0) {
                let html = '<table class="table"><thead><tr><th>Nome</th><th>Email</th><th>Tipo</th><th>Status</th></tr></thead><tbody>';

                data.colaboradores.forEach(colab => {
                    const tipoLabels = {
                        'super_admin': 'Super Admin',
                        'gestor': 'Gestor',
                        'usuario': 'Usuário'
                    };

                    html += `
                    <tr>
                        <td><strong>${colab.nome}</strong></td>
                        <td>${colab.email || '-'}</td>
                        <td><span class="badge badge-info">${tipoLabels[colab.tipo] || colab.tipo}</span></td>
                        <td><span class="badge badge-${colab.status === 'ativo' ? 'success' : 'secondary'}">${colab.status}</span></td>
                    </tr>
                `;
                });

                html += '</tbody></table>';
                lista.innerHTML = html;
            } else {
                lista.innerHTML = '<div style="text-align: center; padding: 20px; color: #999;"><i class="fas fa-users-slash" style="font-size: 36px; margin-bottom: 10px;"></i><p>Nenhum colaborador neste setor ainda.</p></div>';
            }
        } catch (error) {
            lista.innerHTML = '<div style="text-align: center; padding: 20px; color: #ef4444;"><i class="fas fa-exclamation-triangle"></i> Erro ao carregar colaboradores</div>';
        }
    }
</script>

<?php require_once 'footer.php'; ?>