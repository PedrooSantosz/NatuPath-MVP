<?php
$pageTitle = "Detalhes do Registro";
require_once 'header.php';

// Recebe parâmetros
$id = intval($_GET['id'] ?? 0);
$tipo = $_GET['tipo'] ?? '';

if (!$id || !in_array($tipo, ['boa_pratica', 'nao_conformidade'])) {
    $_SESSION['error'] = "Registro não encontrado!";
    header('Location: relatorios.php');
    exit;
}

$user_tipo = $_SESSION['tipo'];
$user_id = $_SESSION['user_id'];
$user_setor_id = null;

// Busca setor do usuário (se gestor)
if ($user_tipo === 'gestor') {
    $stmt = $conn->prepare("SELECT setor_id FROM usuarios WHERE id = :id");
    $stmt->execute(['id' => $user_id]);
    $user_data = $stmt->fetch();
    $user_setor_id = $user_data['setor_id'] ?? null;
}

// ============================================
// BUSCA DADOS DO REGISTRO
// ============================================

if ($tipo === 'boa_pratica') {
    $sql = "
        SELECT 
            bp.*,
            c.nome as categoria_nome,
            c.icone as categoria_icone,
            c.cor as categoria_cor,
            u.nome as usuario_nome,
            u.email as usuario_email,
            s.nome as setor_nome,
            ap.nome as aprovador_nome
        FROM boas_praticas bp
        LEFT JOIN categorias_boas_praticas c ON bp.categoria_id = c.id
        LEFT JOIN usuarios u ON bp.usuario_id = u.id
        LEFT JOIN setores s ON bp.setor_id = s.id
        LEFT JOIN usuarios ap ON bp.aprovado_por = ap.id
        WHERE bp.id = :id
    ";
    
    // Verifica permissões
    if ($user_tipo === 'usuario') {
        $sql .= " AND bp.usuario_id = :user_id";
    } else if ($user_tipo === 'gestor' && $user_setor_id) {
        $sql .= " AND bp.setor_id = :setor_id";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue('id', $id, PDO::PARAM_INT);
    
    if ($user_tipo === 'usuario') {
        $stmt->bindValue('user_id', $user_id, PDO::PARAM_INT);
    } else if ($user_tipo === 'gestor' && $user_setor_id) {
        $stmt->bindValue('setor_id', $user_setor_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $registro = $stmt->fetch();
    
} else { // nao_conformidade
    $sql = "
        SELECT 
            nc.*,
            c.nome as categoria_nome,
            c.icone as categoria_icone,
            c.cor as categoria_cor,
            u.nome as usuario_nome,
            u.email as usuario_email,
            s.nome as setor_nome,
            r.nome as resolvedor_nome
        FROM nao_conformidades nc
        LEFT JOIN categorias_nao_conformidades c ON nc.categoria_id = c.id
        LEFT JOIN usuarios u ON nc.usuario_id = u.id
        LEFT JOIN setores s ON nc.setor_id = s.id
        LEFT JOIN usuarios r ON nc.resolvido_por = r.id
        WHERE nc.id = :id
    ";
    
    // Verifica permissões
    if ($user_tipo === 'usuario') {
        $sql .= " AND nc.usuario_id = :user_id";
    } else if ($user_tipo === 'gestor' && $user_setor_id) {
        $sql .= " AND nc.setor_id = :setor_id";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue('id', $id, PDO::PARAM_INT);
    
    if ($user_tipo === 'usuario') {
        $stmt->bindValue('user_id', $user_id, PDO::PARAM_INT);
    } else if ($user_tipo === 'gestor' && $user_setor_id) {
        $stmt->bindValue('setor_id', $user_setor_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $registro = $stmt->fetch();
}

// Se não encontrou ou sem permissão
if (!$registro) {
    $_SESSION['error'] = "Registro não encontrado ou você não tem permissão para visualizá-lo!";
    header('Location: relatorios.php');
    exit;
}

// Define variáveis para facilitar o uso
$is_boa_pratica = ($tipo === 'boa_pratica');
$titulo_tipo = $is_boa_pratica ? 'Boa Prática' : 'Não Conformidade';
$icone_tipo = $is_boa_pratica ? 'fa-leaf' : 'fa-exclamation-triangle';
$cor_tipo = $is_boa_pratica ? '#10b981' : '#ef4444';
?>

<div class="detalhes-container">
    <!-- Botão Voltar -->
    <div class="mb-20">
        <a href="relatorios.php" class="btn-voltar">
            <i class="fas fa-arrow-left"></i> Voltar para Relatórios
        </a>
    </div>

    <!-- Header com Gradiente Dinâmico -->
    <div class="detalhes-header" style="background: linear-gradient(135deg, <?php echo $cor_tipo; ?> 0%, <?php echo $cor_tipo; ?>dd 100%);">
        <h1>
            <i class="fas <?php echo $icone_tipo; ?>"></i>
            <?php echo sanitize($registro['titulo']); ?>
        </h1>
        <div class="d-flex gap-10 align-center" style="flex-wrap: wrap;">
            <span class="badge" style="background: rgba(255,255,255,0.2);">
                <i class="fas <?php echo $registro['categoria_icone']; ?>"></i>
                <?php echo sanitize($registro['categoria_nome']); ?>
            </span>
            <?php echo getStatusBadge($registro['status'], $tipo); ?>
        </div>
    </div>

    <!-- Conteúdo em Grid -->
    <div class="detalhes-content">
        <!-- Coluna Principal -->
        <div>
            <!-- Descrição -->
            <div class="detalhes-card">
                <h3><i class="fas fa-align-left"></i> Descrição</h3>
                <p style="line-height: 1.8; white-space: pre-wrap;"><?php echo nl2br(sanitize($registro['descricao'])); ?></p>
            </div>

            <?php if (!empty($registro['observacao']) || !empty($registro['solucao'])): ?>
            <!-- Observações/Solução -->
            <div class="detalhes-card mt-30">
                <h3>
                    <i class="fas fa-comment-alt"></i> 
                    <?php echo $is_boa_pratica ? 'Observações' : 'Solução'; ?>
                </h3>
                <p style="line-height: 1.8; white-space: pre-wrap;">
                    <?php echo nl2br(sanitize($is_boa_pratica ? $registro['observacao'] : $registro['solucao'])); ?>
                </p>
            </div>
            <?php endif; ?>

            <?php if (!empty($registro['foto'])): ?>
            <!-- Foto -->
            <div class="detalhes-card mt-30">
                <h3><i class="fas fa-image"></i> Evidência Fotográfica</h3>
                <div class="foto-container">
                    <?php 
                    $foto_path = $is_boa_pratica 
                        ? '../../uploads/boas_praticas/' . $registro['foto']
                        : '../../uploads/nao_conformidades/' . $registro['foto'];
                    ?>
                    <img src="<?php echo $foto_path; ?>" 
                         alt="Foto do registro" 
                         onclick="window.open(this.src, '_blank');" 
                         style="cursor: pointer;" 
                         title="Clique para ampliar">
                    <p class="mt-10 text-center" style="font-size: 12px; color: #999;">
                        <i class="fas fa-info-circle"></i> Clique na imagem para ampliar
                    </p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Coluna Lateral - Informações -->
        <div>
            <!-- Informações Gerais -->
            <div class="detalhes-card">
                <h3><i class="fas fa-info-circle"></i> Informações</h3>

                <div class="detalhes-row">
                    <label>Data <?php echo $is_boa_pratica ? 'da Prática' : 'da Ocorrência'; ?></label>
                    <p><?php echo formatDate($is_boa_pratica ? $registro['data_pratica'] : $registro['data_ocorrencia']); ?></p>
                </div>

                <?php if (!$is_boa_pratica): ?>
                <div class="detalhes-row">
                    <label>Local</label>
                    <p><?php echo sanitize($registro['local']); ?></p>
                </div>
                <?php endif; ?>

                <div class="detalhes-row">
                    <label><?php echo $is_boa_pratica ? 'Impacto' : 'Gravidade'; ?></label>
                    <p>
                        <?php 
                        if ($is_boa_pratica) {
                            $impacto_badges = [
                                'baixo' => '<span class="badge badge-info">Baixo</span>',
                                'medio' => '<span class="badge badge-warning">Médio</span>',
                                'alto' => '<span class="badge badge-success">Alto</span>'
                            ];
                            echo $impacto_badges[$registro['impacto']] ?? $registro['impacto'];
                        } else {
                            $gravidade_badges = [
                                'baixa' => '<span class="badge badge-info">Baixa</span>',
                                'media' => '<span class="badge badge-warning">Média</span>',
                                'alta' => '<span class="badge badge-danger">Alta</span>',
                                'critica' => '<span class="badge" style="background: #7f1d1d; color: white;">Crítica</span>'
                            ];
                            echo $gravidade_badges[$registro['gravidade']] ?? $registro['gravidade'];
                        }
                        ?>
                    </p>
                </div>

                <div class="detalhes-row">
                    <label>Registrado por</label>
                    <p>
                        <i class="fas fa-user"></i> <?php echo sanitize($registro['usuario_nome']); ?>
                        <?php if ($user_tipo !== 'usuario'): ?>
                            <br><small style="color: #999;"><?php echo sanitize($registro['usuario_email']); ?></small>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="detalhes-row">
                    <label>Setor</label>
                    <p><i class="fas fa-building"></i> <?php echo sanitize($registro['setor_nome']); ?></p>
                </div>

                <div class="detalhes-row">
                    <label>Registrado em</label>
                    <p><?php echo formatDateTime($registro['criado_em']); ?></p>
                </div>

                <?php if ($registro['atualizado_em'] != $registro['criado_em']): ?>
                <div class="detalhes-row">
                    <label>Última atualização</label>
                    <p><?php echo formatDateTime($registro['atualizado_em']); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Status e Aprovação/Resolução -->
            <?php if ($is_boa_pratica && !empty($registro['aprovado_por'])): ?>
            <div class="detalhes-card mt-20">
                <h3><i class="fas fa-check-circle"></i> Aprovação</h3>
                
                <div class="info-box">
                    <div>
                        <small>Aprovado por</small><br>
                        <strong><?php echo sanitize($registro['aprovador_nome']); ?></strong>
                    </div>
                </div>

                <div class="info-box">
                    <div>
                        <small>Data da aprovação</small><br>
                        <strong><?php echo formatDateTime($registro['aprovado_em']); ?></strong>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$is_boa_pratica && !empty($registro['resolvido_por'])): ?>
            <div class="detalhes-card mt-20">
                <h3><i class="fas fa-check-circle"></i> Resolução</h3>
                
                <div class="info-box">
                    <div>
                        <small>Resolvido por</small><br>
                        <strong><?php echo sanitize($registro['resolvedor_nome']); ?></strong>
                    </div>
                </div>

                <div class="info-box">
                    <div>
                        <small>Data da resolução</small><br>
                        <strong><?php echo formatDateTime($registro['resolvido_em']); ?></strong>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ações (para gestores/admins) -->
            <?php if (isGestorOrAdmin() && ($registro['status'] === 'pendente' || $registro['status'] === 'aberto' || $registro['status'] === 'em_analise')): ?>
            <div class="detalhes-card detalhes-card-acao mt-20">
                <h3><i class="fas fa-cog"></i> Ações</h3>
                <p class="mb-20" style="font-size: 14px;">
                    <i class="fas fa-info-circle"></i> Este registro pode ser gerenciado
                </p>
                
                <?php if ($is_boa_pratica): ?>
                    <button onclick="abrirModalAprovar(<?php echo $id; ?>, '<?php echo $tipo; ?>')" class="btn btn-success btn-block mb-10">
                        <i class="fas fa-check"></i> Aprovar Prática
                    </button>
                    <button onclick="abrirModalRejeitar(<?php echo $id; ?>, '<?php echo $tipo; ?>')" class="btn btn-danger btn-block">
                        <i class="fas fa-times"></i> Rejeitar Prática
                    </button>
                <?php else: ?>
                    <?php if ($registro['status'] === 'aberto'): ?>
                    <button onclick="marcarEmAnalise(<?php echo $id; ?>, '<?php echo $tipo; ?>')" class="btn btn-warning btn-block mb-10">
                        <i class="fas fa-search"></i> Marcar em Análise
                    </button>
                    <?php endif; ?>
                    <button onclick="abrirModalResolver(<?php echo $id; ?>, '<?php echo $tipo; ?>')" class="btn btn-success btn-block">
                        <i class="fas fa-check"></i> Marcar como Resolvido
                    </button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Aprovar Boa Prática -->
<div id="modalAprovar" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header modal-header-bp">
            <div class="modal-header-content">
                <i class="fas fa-check-circle"></i>
                <h2>Aprovar Boa Prática</h2>
            </div>
            <span class="close" onclick="closeModal('modalAprovar')">&times;</span>
        </div>
        
        <form method="POST" action="../../config/validacao/process_gerenciar_registro.php">
            <input type="hidden" name="id" id="aprovar_id">
            <input type="hidden" name="tipo" id="aprovar_tipo">
            <input type="hidden" name="acao" value="aprovar">
            
            <div class="form-group">
                <label for="aprovar_observacao">Observações (opcional)</label>
                <textarea name="observacao" id="aprovar_observacao" rows="4" 
                          placeholder="Adicione um comentário sobre a aprovação..."></textarea>
                <small>Deixe uma mensagem de feedback para o colaborador (opcional)</small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('modalAprovar')" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirmar Aprovação
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Rejeitar Boa Prática -->
<div id="modalRejeitar" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header modal-header-nc">
            <div class="modal-header-content">
                <i class="fas fa-times-circle"></i>
                <h2>Rejeitar Boa Prática</h2>
            </div>
            <span class="close" onclick="closeModal('modalRejeitar')">&times;</span>
        </div>
        
        <form method="POST" action="../../config/validacao/process_gerenciar_registro.php" onsubmit="return validarRejeicao()">
            <input type="hidden" name="id" id="rejeitar_id">
            <input type="hidden" name="tipo" id="rejeitar_tipo">
            <input type="hidden" name="acao" value="rejeitar">
            
            <div class="form-group">
                <label for="rejeitar_observacao">Motivo da Rejeição *</label>
                <textarea name="observacao" id="rejeitar_observacao" rows="4" 
                          placeholder="Explique o motivo da rejeição (mínimo 10 caracteres)..." required></textarea>
                <small style="color: #dc2626;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Campo obrigatório - O colaborador receberá este feedback
                </small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('modalRejeitar')" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-times"></i> Confirmar Rejeição
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Resolver Não Conformidade -->
<div id="modalResolver" class="modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header modal-header-bp">
            <div class="modal-header-content">
                <i class="fas fa-check-circle"></i>
                <h2>Resolver Não Conformidade</h2>
            </div>
            <span class="close" onclick="closeModal('modalResolver')">&times;</span>
        </div>
        
        <form method="POST" action="../../config/validacao/process_gerenciar_registro.php" onsubmit="return validarResolucao()">
            <input type="hidden" name="id" id="resolver_id">
            <input type="hidden" name="tipo" id="resolver_tipo">
            <input type="hidden" name="acao" value="resolver">
            
            <div class="form-group">
                <label for="resolver_observacao">Descrição da Solução *</label>
                <textarea name="observacao" id="resolver_observacao" rows="5" 
                          placeholder="Descreva a solução implementada (mínimo 20 caracteres)..." required></textarea>
                <small>
                    <i class="fas fa-info-circle"></i> 
                    Explique como o problema foi resolvido
                </small>
            </div>
            
            <div class="form-actions">
                <button type="button" onclick="closeModal('modalResolver')" class="btn btn-secondary">
                    Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirmar Resolução
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Abrir modal de aprovar
function abrirModalAprovar(id, tipo) {
    document.getElementById('aprovar_id').value = id;
    document.getElementById('aprovar_tipo').value = tipo;
    document.getElementById('aprovar_observacao').value = '';
    openModal('modalAprovar');
}

// Abrir modal de rejeitar
function abrirModalRejeitar(id, tipo) {
    document.getElementById('rejeitar_id').value = id;
    document.getElementById('rejeitar_tipo').value = tipo;
    document.getElementById('rejeitar_observacao').value = '';
    openModal('modalRejeitar');
}

// Abrir modal de resolver
function abrirModalResolver(id, tipo) {
    document.getElementById('resolver_id').value = id;
    document.getElementById('resolver_tipo').value = tipo;
    document.getElementById('resolver_observacao').value = '';
    openModal('modalResolver');
}

// Marcar em análise (sem modal - ação direta)
function marcarEmAnalise(id, tipo) {
    if (confirm('Deseja marcar esta não conformidade como "Em Análise"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '../../config/validacao/process_gerenciar_registro.php';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = id;
        
        const inputTipo = document.createElement('input');
        inputTipo.type = 'hidden';
        inputTipo.name = 'tipo';
        inputTipo.value = tipo;
        
        const inputAcao = document.createElement('input');
        inputAcao.type = 'hidden';
        inputAcao.name = 'acao';
        inputAcao.value = 'analisar';
        
        form.appendChild(inputId);
        form.appendChild(inputTipo);
        form.appendChild(inputAcao);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Validação do formulário de rejeição
function validarRejeicao() {
    const motivo = document.getElementById('rejeitar_observacao').value.trim();
    
    if (motivo.length < 10) {
        alert('O motivo da rejeição deve ter pelo menos 10 caracteres!');
        document.getElementById('rejeitar_observacao').focus();
        return false;
    }
    
    return confirm('Confirma a rejeição desta boa prática?\n\nO colaborador será notificado.');
}

// Validação do formulário de resolução
function validarResolucao() {
    const solucao = document.getElementById('resolver_observacao').value.trim();
    
    if (solucao.length < 20) {
        alert('A descrição da solução deve ter pelo menos 20 caracteres!');
        document.getElementById('resolver_observacao').focus();
        return false;
    }
    
    return confirm('Confirma que esta não conformidade foi resolvida?');
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php require_once 'footer.php'; ?>