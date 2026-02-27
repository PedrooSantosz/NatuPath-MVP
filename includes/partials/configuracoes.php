<?php
$pageTitle = "Configurações";
require_once 'header.php';

$user_id = $_SESSION['user_id'];

// Busca dados do usuário
$stmt = $conn->prepare("
    SELECT u.*, s.nome as setor_nome 
    FROM usuarios u
    LEFT JOIN setores s ON u.setor_id = s.id
    WHERE u.id = :id
");
$stmt->execute(['id' => $user_id]);
$usuario = $stmt->fetch();

if (!$usuario) {
    $_SESSION['error'] = "Erro ao carregar dados do usuário!";
    header('Location: dashboard.php');
    exit;
}

// Define caminho da foto com verificação melhorada
$tem_foto = !empty($usuario['foto']);
if ($tem_foto) {
    $foto_path_abs = __DIR__ . '/../../uploads/perfil/' . $usuario['foto'];
    if (file_exists($foto_path_abs)) {
        $foto_perfil = '../../uploads/perfil/' . $usuario['foto'];
    } else {
        // Foto está no banco mas não existe no servidor - limpar
        $stmt_clear = $conn->prepare("UPDATE usuarios SET foto = NULL WHERE id = :id");
        $stmt_clear->execute(['id' => $user_id]);
        $foto_perfil = null;
        $tem_foto = false;
    }
} else {
    $foto_perfil = null;
}
?>

<div class="overview">
    <div class="title">
        <i class="fas fa-cog"></i>
        <span class="text">Configurações</span>
    </div>
</div>

<!-- ========================================== -->
<!-- SEÇÃO DE FOTO DE PERFIL - COM ÍCONE -->
<!-- ========================================== -->
<div class="activity" style="margin-bottom: 25px;">
    <div class="title">
        <i class="fas fa-camera"></i>
        <span class="text">Foto de Perfil</span>
    </div>

    <div style="padding: 25px; background: var(--panel-color); border-radius: 10px;">
        <div style="display: flex; gap: 30px; align-items: center; flex-wrap: wrap;">
            <!-- Preview da Foto OU Ícone -->
            <div style="text-align: center;">
                <div style="position: relative; display: inline-block;">
                    <?php if ($tem_foto && $foto_perfil): ?>
                        <!-- Foto personalizada -->
                        <img id="previewFotoPerfil" 
                             src="<?php echo htmlspecialchars($foto_perfil); ?>" 
                             alt="Foto de perfil" 
                             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #10b981; box-shadow: 0 4px 15px rgba(0,0,0,0.1); display: block;"
                             onerror="this.style.display='none'; document.getElementById('iconeFallback').style.display='flex';">
                        <!-- Fallback: Ícone (caso imagem falhe) -->
                        <div id="iconeFallback" style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: none; align-items: center; justify-content: center; border: 4px solid #10b981; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <i class="fas fa-user" style="color: white; font-size: 60px;"></i>
                        </div>
                    <?php else: ?>
                        <!-- Ícone padrão quando não há foto -->
                        <div id="previewFotoPerfil" style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; border: 4px solid #10b981; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                            <i class="fas fa-user" style="color: white; font-size: 60px;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Badge indicando status -->
                    <div style="position: absolute; bottom: 10px; right: 10px; background: <?php echo $tem_foto ? '#10b981' : '#666'; ?>; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 3px solid var(--panel-color); box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                        <i class="fas <?php echo $tem_foto ? 'fa-check' : 'fa-camera'; ?>" style="font-size: 16px;"></i>
                    </div>
                </div>
                <p style="margin-top: 15px; color: #999; font-size: 13px; font-weight: 500;">
                    <?php echo $tem_foto ? '✓ Foto personalizada' : '○ Sem foto personalizada'; ?>
                </p>
            </div>

            <!-- Formulários de Upload e Remoção -->
            <div style="flex: 1; min-width: 300px;">
                <!-- Upload de Nova Foto -->
                <form id="formUploadFoto" action="../../config/validacao/process_foto_perfil.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="upload_foto">
                    
                    <div style="margin-bottom: 15px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--text-color); font-weight: 500;">
                            <i class="fas fa-upload"></i> Escolher nova foto
                        </label>
                        <input type="file" 
                               id="inputFotoPerfil" 
                               name="foto" 
                               accept="image/jpeg,image/jpg,image/png,image/gif,image/webp"
                               onchange="previewImage(this)"
                               style="display: block; width: 100%; padding: 10px; border: 2px dashed #ddd; border-radius: 8px; cursor: pointer; background: var(--panel-color);">
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
                        <button type="submit" class="btn btn-success" id="btnUploadFoto" style="flex: 1;" disabled>
                            <i class="fas fa-cloud-upload-alt"></i> Enviar Foto
                        </button>
                        <button type="button" 
                                class="btn btn-secondary" 
                                onclick="cancelarUpload();">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>

                    <p style="color: #999; font-size: 13px; margin: 0;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Formatos aceitos:</strong> JPG, PNG, GIF, WEBP<br>
                        <i class="fas fa-info-circle"></i> 
                        <strong>Tamanho máximo:</strong> 5MB
                    </p>
                </form>

                <!-- Remover Foto (só aparece se tiver foto customizada) -->
                <?php if ($tem_foto): ?>
                    <form id="formRemoverFoto" action="../../config/validacao/process_foto_perfil.php" method="POST" onsubmit="return confirmarRemocao();" style="border-top: 1px solid #eee; padding-top: 20px;">
                        <input type="hidden" name="action" value="remover_foto">
                        
                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            <i class="fas fa-trash-alt"></i> Remover Foto Atual
                        </button>
                        
                        <p style="color: #999; font-size: 12px; margin-top: 8px; text-align: center;">
                            O ícone padrão será restaurado
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Perfil do Usuário -->
<div class="activity" style="margin-bottom: 30px;">
    <div class="title">
        <i class="fas fa-user"></i>
        <span class="text">Meu Perfil</span>
    </div>

    <div class="perfil-container">
        <!-- Informações do Perfil -->
        <div class="perfil-info" style="width: 100%;">
            <h2><?php echo sanitize($usuario['nome']); ?></h2>
            
            <div class="perfil-detalhes">
                <div class="perfil-item">
                    <i class="fas fa-user"></i>
                    <span class="perfil-item-label">Username:</span>
                    <span class="perfil-item-value"><?php echo sanitize($usuario['username']); ?></span>
                </div>
                
                <div class="perfil-item">
                    <i class="fas fa-envelope"></i>
                    <span class="perfil-item-label">Email:</span>
                    <span class="perfil-item-value"><?php echo sanitize($usuario['email'] ?? 'Não cadastrado'); ?></span>
                </div>
                
                <div class="perfil-item">
                    <i class="fas fa-shield-alt"></i>
                    <span class="perfil-item-label">Tipo:</span>
                    <span class="perfil-item-value">
                        <?php
                        $tipos = [
                            'super_admin' => '<span class="badge badge-danger">Super Administrador</span>',
                            'gestor' => '<span class="badge badge-warning">Gestor</span>',
                            'usuario' => '<span class="badge badge-info">Usuário</span>'
                        ];
                        echo $tipos[$usuario['tipo']] ?? $usuario['tipo'];
                        ?>
                    </span>
                </div>
                
                <div class="perfil-item">
                    <i class="fas fa-building"></i>
                    <span class="perfil-item-label">Setor:</span>
                    <span class="perfil-item-value"><?php echo sanitize($usuario['setor_nome'] ?? 'Sem setor'); ?></span>
                </div>
                
                <div class="perfil-item">
                    <i class="fas fa-calendar"></i>
                    <span class="perfil-item-label">Membro desde:</span>
                    <span class="perfil-item-value"><?php echo formatDate($usuario['criado_em']); ?></span>
                </div>
                
                <?php if ($usuario['ultimo_login']): ?>
                <div class="perfil-item">
                    <i class="fas fa-clock"></i>
                    <span class="perfil-item-label">Último acesso:</span>
                    <span class="perfil-item-value"><?php echo formatDateTime($usuario['ultimo_login']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Editar Perfil -->
<div class="secao-form">
    <div class="title">
        <i class="fas fa-edit"></i>
        <span class="text">Editar Perfil</span>
    </div>

    <form action="../../config/validacao/process_config.php" method="POST">
        <input type="hidden" name="action" value="editar_perfil">

        <div class="form-row">
            <div class="form-group">
                <label for="nome">Nome Completo *</label>
                <input type="text" id="nome" name="nome" required minlength="3" maxlength="100"
                       value="<?php echo sanitize($usuario['nome']); ?>">
            </div>

            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required minlength="3" maxlength="50"
                       value="<?php echo sanitize($usuario['username']); ?>"
                       pattern="[a-zA-Z0-9._-]+"
                       title="Apenas letras, números, ponto, hífen e underscore">
                <small>Usado para fazer login</small>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" maxlength="100"
                   value="<?php echo sanitize($usuario['email'] ?? ''); ?>">
            <small>Usado para recuperação de senha</small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </form>
</div>

<!-- Alterar Senha -->
<div class="secao-form">
    <div class="title">
        <i class="fas fa-key"></i>
        <span class="text">Alterar Senha</span>
    </div>

    <form action="../../config/validacao/process_config.php" method="POST" id="formSenha">
        <input type="hidden" name="action" value="alterar_senha">

        <div class="form-group">
            <label for="senha_atual">Senha Atual *</label>
            <div class="input-password-wrapper">
                <input type="password" id="senha_atual" name="senha_atual" required minlength="6"
                       placeholder="Digite sua senha atual">
                <i class="fas fa-eye toggle-password" id="toggleSenhaAtual" 
                   onclick="togglePasswordVisibility('senha_atual', 'toggleSenhaAtual')"></i>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="nova_senha">Nova Senha *</label>
                <div class="input-password-wrapper">
                    <input type="password" id="nova_senha" name="nova_senha" required minlength="6"
                           placeholder="Mínimo 6 caracteres">
                    <i class="fas fa-eye toggle-password" id="toggleNovaSenha" 
                       onclick="togglePasswordVisibility('nova_senha', 'toggleNovaSenha')"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirma_senha">Confirmar Nova Senha *</label>
                <div class="input-password-wrapper">
                    <input type="password" id="confirma_senha" name="confirma_senha" required minlength="6"
                           placeholder="Digite novamente a nova senha">
                    <i class="fas fa-eye toggle-password" id="toggleConfirmaSenha" 
                       onclick="togglePasswordVisibility('confirma_senha', 'toggleConfirmaSenha')"></i>
                </div>
            </div>
        </div>

        <div id="senhaForte" class="senha-forca" style="display: none;">
            <strong>Força da senha:</strong>
            <div class="senha-forca-barra">
                <div id="barraForca" class="senha-forca-progresso"></div>
            </div>
            <small id="textoForca" class="senha-forca-texto"></small>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-key"></i> Alterar Senha
            </button>
        </div>
    </form>
</div>

<!-- Estatísticas do Usuário -->
<div class="secao-form">
    <div class="title">
        <i class="fas fa-chart-bar"></i>
        <span class="text">Minhas Estatísticas</span>
    </div>

    <?php
    // Busca estatísticas do usuário
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE usuario_id = :id");
    $stmt->execute(['id' => $user_id]);
    $total_bp = $stmt->fetch()['total'];

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM boas_praticas WHERE usuario_id = :id AND status = 'aprovado'");
    $stmt->execute(['id' => $user_id]);
    $bp_aprovadas = $stmt->fetch()['total'];

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nao_conformidades WHERE usuario_id = :id");
    $stmt->execute(['id' => $user_id]);
    $total_nc = $stmt->fetch()['total'];

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM nao_conformidades WHERE usuario_id = :id AND status = 'resolvido'");
    $stmt->execute(['id' => $user_id]);
    $nc_resolvidas = $stmt->fetch()['total'];
    
    $taxa_aprovacao = $total_bp > 0 ? round(($bp_aprovadas / $total_bp) * 100) : 0;
    ?>

    <div class="estatisticas-boxes">
        <div class="estatistica-box box-verde">
            <i class="fas fa-leaf"></i>
            <span class="titulo">Boas Práticas</span>
            <span class="numero"><?php echo $total_bp; ?></span>
            <small><?php echo $bp_aprovadas; ?> aprovadas</small>
        </div>

        <div class="estatistica-box box-vermelho">
            <i class="fas fa-exclamation-triangle"></i>
            <span class="titulo">Não Conformidades</span>
            <span class="numero"><?php echo $total_nc; ?></span>
            <small><?php echo $nc_resolvidas; ?> resolvidas</small>
        </div>

        <div class="estatistica-box box-azul">
            <i class="fas fa-percentage"></i>
            <span class="titulo">Taxa de Aprovação</span>
            <span class="numero"><?php echo $taxa_aprovacao; ?>%</span>
            <small>de boas práticas</small>
        </div>

        <div class="estatistica-box box-amarelo">
            <i class="fas fa-clipboard-check"></i>
            <span class="titulo">Total de Registros</span>
            <span class="numero"><?php echo $total_bp + $total_nc; ?></span>
            <small>contribuições</small>
        </div>
    </div>
</div>

<script>
// ========================================
// SISTEMA DE FOTO DE PERFIL
// ========================================

// Preview da imagem antes de enviar
function previewImage(input) {
    const preview = document.getElementById('previewFotoPerfil');
    const btnUpload = document.getElementById('btnUploadFoto');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Valida tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Tipo de arquivo inválido! Use apenas JPG, PNG, GIF ou WEBP.');
            input.value = '';
            btnUpload.disabled = true;
            return;
        }
        
        // Valida tamanho (5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Arquivo muito grande! Tamanho máximo: 5MB');
            input.value = '';
            btnUpload.disabled = true;
            return;
        }
        
        // Mostra preview da imagem
        const reader = new FileReader();
        reader.onload = function(e) {
            // Substitui o ícone por uma imagem
            preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #10b981; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
            btnUpload.disabled = false;
        };
        reader.readAsDataURL(file);
    } else {
        resetPreview();
        btnUpload.disabled = true;
    }
}

// Cancelar upload
function cancelarUpload() {
    document.getElementById('inputFotoPerfil').value = '';
    document.getElementById('btnUploadFoto').disabled = true;
    resetPreview();
}

// Reset do preview
function resetPreview() {
    const preview = document.getElementById('previewFotoPerfil');
    <?php if ($tem_foto && $foto_perfil): ?>
        preview.innerHTML = '<img src="<?php echo htmlspecialchars($foto_perfil); ?>" alt="Foto de perfil" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid #10b981; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
    <?php else: ?>
        preview.innerHTML = '<div style="width: 150px; height: 150px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; border: 4px solid #10b981; box-shadow: 0 4px 15px rgba(0,0,0,0.1);"><i class="fas fa-user" style="color: white; font-size: 60px;"></i></div>';
    <?php endif; ?>
}

// Confirmar remoção
function confirmarRemocao() {
    return confirm('Tem certeza que deseja remover sua foto de perfil?\n\nO ícone padrão será restaurado.');
}

// Habilita/desabilita botão de upload ao selecionar arquivo
document.getElementById('inputFotoPerfil').addEventListener('change', function() {
    document.getElementById('btnUploadFoto').disabled = !this.files.length;
});

// ========================================
// VALIDAÇÃO DE SENHA
// ========================================

// Validar senha
document.getElementById('formSenha').addEventListener('submit', function(e) {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmaSenha = document.getElementById('confirma_senha').value;
    
    if (novaSenha !== confirmaSenha) {
        e.preventDefault();
        alert('As senhas não conferem!');
        return false;
    }
    
    if (novaSenha.length < 6) {
        e.preventDefault();
        alert('A nova senha deve ter pelo menos 6 caracteres!');
        return false;
    }
});

// Medidor de força da senha
document.getElementById('nova_senha').addEventListener('input', function() {
    const senha = this.value;
    const divForca = document.getElementById('senhaForte');
    const barraForca = document.getElementById('barraForca');
    const textoForca = document.getElementById('textoForca');
    
    if (senha.length === 0) {
        divForca.style.display = 'none';
        return;
    }
    
    divForca.style.display = 'block';
    
    const resultado = verificarForcaSenha(senha);
    
    barraForca.style.width = resultado.forca + '%';
    barraForca.style.backgroundColor = resultado.cor;
    textoForca.textContent = resultado.texto;
    textoForca.style.color = resultado.cor;
});
</script>

<?php require_once 'footer.php'; ?>