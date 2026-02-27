<?php
// ============================================
// MODAIS GLOBAIS - BP, NC, SETORES E USUÁRIOS
// Incluir no header.php para funcionar em todas as páginas
// ============================================

// Define o tipo de usuário logado
$user_tipo = $_SESSION['tipo'] ?? 'usuario';

// Busca categorias de Boas Práticas
$stmt = $conn->query("
    SELECT id, nome, icone, cor 
    FROM categorias_boas_praticas 
    WHERE status = 'ativo' 
    ORDER BY nome
");
$categorias_bp = $stmt->fetchAll();

// Busca categorias de Não Conformidades
$stmt = $conn->query("
    SELECT id, nome, icone, cor 
    FROM categorias_nao_conformidades 
    WHERE status = 'ativo' 
    ORDER BY nome
");
$categorias_nc = $stmt->fetchAll();

// Busca setores (para select)
$stmt = $conn->query("
    SELECT id, nome 
    FROM setores 
    WHERE status = 'ativo' 
    ORDER BY nome
");
$setores_select = $stmt->fetchAll();

// Busca usuários que podem ser gestores (para o select de setores)
$stmt = $conn->query("
    SELECT id, nome, email 
    FROM usuarios 
    WHERE status = 'ativo' 
    ORDER BY nome
");
$usuarios_gestores = $stmt->fetchAll();
?>

<!-- ============================================
     MODAL: BOAS PRÁTICAS
     ============================================ -->
<div id="boasPraticasModal" class="modal">
    <div class="modal-content modal-bp">
        <!-- Header com cor de Boas Práticas (Verde) -->
        <div class="modal-header modal-header-bp">
            <div class="modal-header-content">
                <i class="fas fa-leaf"></i>
                <h2>Registrar Boa Prática</h2>
            </div>
            <span class="close" onclick="closeModal('boasPraticasModal')">&times;</span>
        </div>

        <form id="formBoasPraticas" action="../../config/validacao/process_boa_pratica.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">

            <div class="form-row">
                <div class="form-group">
                    <label for="titulo_bp">Título *</label>
                    <input type="text" id="titulo_bp" name="titulo" required minlength="5" maxlength="200"
                           placeholder="Ex: Economia de energia no setor">
                </div>

                <div class="form-group">
                    <label for="categoria_bp">Categoria *</label>
                    <select id="categoria_bp" name="categoria_id" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias_bp as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo sanitize($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descricao_bp">Descrição *</label>
                <textarea id="descricao_bp" name="descricao" required minlength="20" maxlength="1000" rows="4"
                          placeholder="Descreva a boa prática implementada..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="data_pratica">Data da Prática *</label>
                    <input type="date" id="data_pratica" name="data_pratica" required value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="impacto_bp">Impacto Estimado *</label>
                    <select id="impacto_bp" name="impacto" required>
                        <option value="">Selecione o impacto</option>
                        <option value="baixo">Baixo</option>
                        <option value="medio" selected>Médio</option>
                        <option value="alto">Alto</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="local_bp">Local</label>
                    <input type="text" id="local_bp" name="local" maxlength="100"
                           placeholder="Ex: Sala de reuniões">
                </div>

                <div class="form-group">
                    <label for="setor_bp">Setor</label>
                    <select id="setor_bp" name="setor_id">
                        <option value="">Selecione o setor</option>
                        <?php foreach ($setores_select as $setor): ?>
                            <option value="<?php echo $setor['id']; ?>">
                                <?php echo sanitize($setor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="foto_bp">Foto (opcional)</label>
                <input type="file" id="foto_bp" name="foto" accept="image/*">
                <small>Formatos: JPG, PNG, GIF, WEBP | Tamanho máximo: 5MB</small>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('boasPraticasModal')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Registrar Boa Prática
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     MODAL: NÃO CONFORMIDADES
     ============================================ -->
<div id="naoConformidadesModal" class="modal">
    <div class="modal-content modal-nc">
        <!-- Header com cor de Não Conformidades (Vermelho) -->
        <div class="modal-header modal-header-nc">
            <div class="modal-header-content">
                <i class="fas fa-exclamation-triangle"></i>
                <h2>Registrar Não Conformidade</h2>
            </div>
            <span class="close" onclick="closeModal('naoConformidadesModal')">&times;</span>
        </div>

        <form id="formNaoConformidades" action="../../config/validacao/process_nao_conformidade.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">

            <div class="form-row">
                <div class="form-group">
                    <label for="titulo_nc">Título *</label>
                    <input type="text" id="titulo_nc" name="titulo" required minlength="5" maxlength="200"
                           placeholder="Ex: Vazamento de água no banheiro">
                </div>

                <div class="form-group">
                    <label for="categoria_nc">Categoria *</label>
                    <select id="categoria_nc" name="categoria_id" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias_nc as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>">
                                <?php echo sanitize($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descricao_nc">Descrição do Problema *</label>
                <textarea id="descricao_nc" name="descricao" required minlength="20" maxlength="1000" rows="4"
                          placeholder="Descreva o problema encontrado..."></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="data_ocorrencia">Data da Ocorrência *</label>
                    <input type="date" id="data_ocorrencia" name="data_ocorrencia" required value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="gravidade_nc">Gravidade *</label>
                    <select id="gravidade_nc" name="gravidade" required>
                        <option value="">Selecione a gravidade</option>
                        <option value="baixa">Baixa</option>
                        <option value="media" selected>Média</option>
                        <option value="alta">Alta</option>
                        <option value="critica">Crítica</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="local_nc">Local *</label>
                    <input type="text" id="local_nc" name="local" required maxlength="100"
                           placeholder="Ex: Banheiro do 2º andar">
                </div>

                <div class="form-group">
                    <label for="setor_nc">Setor</label>
                    <select id="setor_nc" name="setor_id">
                        <option value="">Selecione o setor</option>
                        <?php foreach ($setores_select as $setor): ?>
                            <option value="<?php echo $setor['id']; ?>">
                                <?php echo sanitize($setor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="foto_nc">Foto da Ocorrência (opcional)</label>
                <input type="file" id="foto_nc" name="foto" accept="image/*">
                <small>Formatos: JPG, PNG, GIF, WEBP | Tamanho máximo: 5MB</small>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('naoConformidadesModal')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle"></i> Registrar Não Conformidade
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     MODAL: CRIAR/EDITAR SETOR
     ============================================ -->
<div id="modalSetor" class="modal">
    <div class="modal-content modal-setor">
        <!-- Header com cor de Setores (Azul) -->
        <div class="modal-header modal-header-setor">
            <div class="modal-header-content">
                <i class="fas fa-building"></i>
                <h2 id="modalSetorTitleText">Novo Setor</h2>
            </div>
            <span class="close" onclick="closeModal('modalSetor')">&times;</span>
        </div>

        <form id="formSetor" action="../../config/validacao/process_setor.php" method="POST">
            <input type="hidden" id="setor_id" name="setor_id" value="">
            <input type="hidden" id="action" name="action" value="create">

            <div class="form-group">
                <label for="nome_setor">Nome do Setor *</label>
                <input type="text" id="nome_setor" name="nome" required minlength="3" maxlength="100"
                       placeholder="Ex: Administração, Produção, TI...">
            </div>

            <div class="form-group">
                <label for="descricao_setor">Descrição</label>
                <textarea id="descricao_setor" name="descricao" rows="3" maxlength="500"
                          placeholder="Breve descrição sobre o setor e suas responsabilidades..."></textarea>
                <small>Opcional - Máximo 500 caracteres</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="gestor_id">Gestor Responsável</label>
                    <select id="gestor_id" name="gestor_id">
                        <option value="">Sem gestor (definir depois)</option>
                        <?php foreach ($usuarios_gestores as $usuario): ?>
                            <option value="<?php echo $usuario['id']; ?>">
                                <?php echo sanitize($usuario['nome']); ?>
                                <?php if ($usuario['email']): ?>
                                    - <?php echo sanitize($usuario['email']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>O usuário selecionado será automaticamente promovido a Gestor</small>
                </div>

                <div class="form-group">
                    <label for="status_setor">Status *</label>
                    <select id="status_setor" name="status" required>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalSetor')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary" id="btnSubmitSetor">
                    <i class="fas fa-check"></i> <span id="btnSubmitTextSetor">Criar Setor</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     MODAL: VER COLABORADORES DO SETOR
     ============================================ -->
<div id="modalColaboradores" class="modal">
    <div class="modal-content modal-info">
        <div class="modal-header modal-header-info">
            <div class="modal-header-content">
                <i class="fas fa-users"></i>
                <h2>Colaboradores do Setor: <span id="setorNomeColaboradores"></span></h2>
            </div>
            <span class="close" onclick="closeModal('modalColaboradores')">&times;</span>
        </div>

        <div id="listaColaboradores" style="margin: 20px 0;">
            <!-- Será preenchido via JavaScript -->
        </div>

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('modalColaboradores')">
                <i class="fas fa-times"></i> Fechar
            </button>
        </div>
    </div>
</div>

<!-- ============================================
     MODAL: CRIAR/EDITAR USUÁRIO
     ============================================ -->
<div id="modalUsuario" class="modal">
    <div class="modal-content modal-usuario">
        <!-- Header com cor de Usuários (Roxo) -->
        <div class="modal-header modal-header-usuario">
            <div class="modal-header-content">
                <i class="fas fa-user"></i>
                <h2 id="modalUsuarioTitleText">Novo Usuário</h2>
            </div>
            <span class="close" onclick="closeModal('modalUsuario')">&times;</span>
        </div>

        <form id="formUsuario" action="../../config/validacao/process_usuario.php" method="POST">
            <input type="hidden" id="usuario_id" name="usuario_id" value="">
            <input type="hidden" id="action_usuario" name="action" value="create">

            <div class="form-row">
                <div class="form-group">
                    <label for="nome_usuario">Nome Completo *</label>
                    <input type="text" id="nome_usuario" name="nome" required minlength="3" maxlength="100"
                           placeholder="Ex: João da Silva">
                </div>

                <div class="form-group">
                    <label for="username">Nome de Usuário *</label>
                    <input type="text" id="username" name="username" required minlength="3" maxlength="50"
                           placeholder="Ex: joao.silva">
                    <small>Usado para login no sistema</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email_usuario">E-mail</label>
                    <input type="email" id="email_usuario" name="email" maxlength="100"
                           placeholder="usuario@empresa.com">
                </div>

                <div class="form-group">
                    <label for="tipo_usuario">Tipo de Usuário *</label>
                    <select id="tipo_usuario" name="tipo" required>
                        <option value="usuario">Usuário</option>
                        <option value="gestor">Gestor</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="setor_id_usuario">Setor</label>
                    <select id="setor_id_usuario" name="setor_id">
                        <option value="">Sem setor</option>
                        <?php foreach ($setores_select as $setor): ?>
                            <option value="<?php echo $setor['id']; ?>">
                                <?php echo sanitize($setor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status_usuario">Status *</label>
                    <select id="status_usuario" name="status" required>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="password_usuario">Senha *</label>
                <input type="password" id="password_usuario" name="password" minlength="6"
                       placeholder="Mínimo 6 caracteres">
                <small id="password-hint">Obrigatório ao criar novo usuário</small>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalUsuario')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> <span id="btnSubmitTextUsuario">Criar Usuário</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     MODAL: RESETAR SENHA
     ============================================ -->
<div id="modalResetSenha" class="modal">
    <div class="modal-content modal-warning">
        <div class="modal-header modal-header-warning">
            <div class="modal-header-content">
                <i class="fas fa-key"></i>
                <h2>Resetar Senha do Usuário</h2>
            </div>
            <span class="close" onclick="closeModal('modalResetSenha')">&times;</span>
        </div>

        <form id="formResetSenha" action="../../config/validacao/process_usuario.php" method="POST">
            <input type="hidden" name="action" value="reset_senha">
            <input type="hidden" id="reset_usuario_id" name="usuario_id" value="">

            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="margin: 0; color: #856404;">
                    <i class="fas fa-exclamation-triangle"></i>
                    Você está prestes a redefinir a senha do usuário: <strong id="usuarioNomeReset"></strong>
                </p>
            </div>

            <div class="form-group">
                <label for="nova_senha">Nova Senha *</label>
                <input type="password" id="nova_senha" name="nova_senha" required minlength="6"
                       placeholder="Mínimo 6 caracteres">
            </div>

            <div class="form-group">
                <label for="confirma_senha">Confirmar Senha *</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required minlength="6"
                       placeholder="Digite a senha novamente">
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalResetSenha')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-warning">
                    <i class="fas fa-key"></i> Alterar Senha
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     MODAL: CATEGORIAS
     ============================================ -->
<div id="modalCategoria" class="modal">
    <div class="modal-content modal-categoria">
        <div class="modal-header modal-header-categoria">
            <div class="modal-header-content">
                <i class="fas fa-tags"></i>
                <h2 id="modalCategoriaTitleText">Nova Categoria</h2>
            </div>
            <span class="close" onclick="closeModal('modalCategoria')">&times;</span>
        </div>

        <form id="formCategoria" action="../../config/validacao/process_categoria.php" method="POST">
            <input type="hidden" id="categoria_id" name="categoria_id" value="">
            <input type="hidden" id="action_categoria" name="action" value="create">
            <input type="hidden" id="tipo_categoria" name="tipo_categoria" value="">
            <input type="hidden" id="icone" name="icone" value="fa-leaf">

            <div class="form-group">
                <label for="nome_categoria">Nome da Categoria *</label>
                <input type="text" id="nome_categoria" name="nome" required minlength="3" maxlength="100"
                       placeholder="Ex: Economia de Energia">
            </div>

            <div class="form-group">
                <label for="descricao_categoria">Descrição</label>
                <textarea id="descricao_categoria" name="descricao" rows="3" maxlength="500"
                          placeholder="Breve descrição sobre esta categoria..."></textarea>
            </div>

            <!-- Seletor de Ícone Visual -->
            <div class="form-group" id="container-seletor-icone">
                <!-- O seletor será inserido aqui pelo JavaScript -->
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="cor_categoria">Cor *</label>
                    <input type="color" id="cor_categoria" name="cor" required value="#10b981">
                </div>

                <div class="form-group">
                    <label for="status_categoria">Status *</label>
                    <select id="status_categoria" name="status" required>
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
            </div>

            <?php if ($user_tipo === 'super_admin'): ?>
                <div class="form-group">
                    <label for="setor_id_categoria">Setor</label>
                    <select id="setor_id_categoria" name="setor_id">
                        <option value="">Global (todos os setores)</option>
                        <?php foreach ($setores_select as $setor): ?>
                            <option value="<?php echo $setor['id']; ?>">
                                <?php echo sanitize($setor['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Deixe "Global" para que todos os setores possam usar</small>
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalCategoria')">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-success" id="btnSubmitCategoria">
                    <i class="fas fa-check"></i> <span id="btnSubmitTextCategoria">Criar Categoria</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Cores dos headers dos modais */
.modal-header-bp {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.modal-header-nc {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.modal-header-setor {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.modal-header-usuario {
    background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
}

.modal-header-warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.modal-header-info {
    background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
}

.modal-header-categoria {
    background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);
}

/* Bordas coloridas nos modais */
.modal-bp {
    border-top: 4px solid #10b981;
}

.modal-nc {
    border-top: 4px solid #ef4444;
}

.modal-setor {
    border-top: 4px solid #3b82f6;
}

.modal-usuario {
    border-top: 4px solid #8b5cf6;
}

.modal-warning {
    border-top: 4px solid #f59e0b;
}

.modal-info {
    border-top: 4px solid #06b6d4;
}

.modal-categoria {
    border-top: 4px solid #ec4899;
}

</style>

