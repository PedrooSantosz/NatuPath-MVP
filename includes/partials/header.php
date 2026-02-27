<?php
// ============================================
// HEADER - INCLUI AUTENTICAÇÃO E CONEXÃO
// ============================================

// Inclui autenticação (já tem session_start dentro)
require_once __DIR__ . '/../../config/validacao/check_auth.php';

// Inclui conexão com banco de dados
require_once __DIR__ . '/../../config/config.php';

// Inclui funções auxiliares
require_once __DIR__ . '/../../config/functions.php';

// Busca informações do usuário logado (incluindo foto)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nome, email, foto FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user_data = $stmt->fetch();

// Define a foto do usuário com tratamento melhorado
$tem_foto_custom = false;
$user_foto = null;

// Verifica se há foto no banco E se o arquivo existe fisicamente
if (!empty($user_data['foto'])) {
    $foto_path_abs = __DIR__ . '/../../uploads/perfil/' . $user_data['foto'];
    $foto_path_rel = '../../uploads/perfil/' . $user_data['foto'];
    
    // Verifica se o arquivo existe
    if (file_exists($foto_path_abs)) {
        $user_foto = $foto_path_rel;
        $tem_foto_custom = true;
    } else {
        // Se a foto está no banco mas não existe no servidor, limpa o banco
        try {
            $stmt_clear = $conn->prepare("UPDATE usuarios SET foto = NULL WHERE id = :id");
            $stmt_clear->execute(['id' => $user_id]);
        } catch (Exception $e) {
            error_log("Erro ao limpar foto inexistente: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - NatuPath</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../../assets/css/bootstrap.min.css">
    
    <!-- CSS Principal (INTEGRADO - inclui modais e configurações) -->
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <!-- Sidebar -->
    <nav>
        <a href="dashboard.php" class="logo-name" style="text-decoration: none; color: inherit; cursor: pointer;">
            <div class="logo-image">
                <i class="fas fa-leaf" style="font-size: 35px; color: #10b981;"></i>
            </div>
            <span class="logo_name">NatuPath</span>
        </a>

        <div class="menu-items">
            <ul class="nav-links">
                <!-- Dashboard -->
                <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span class="link-name">Dashboard</span>
                    </a></li>

                <!-- Boas Práticas (Modal) -->
                <li><a href="#" onclick="openModal('boasPraticasModal'); return false;">
                        <i class="fas fa-leaf"></i>
                        <span class="link-name">Boas Práticas</span>
                    </a></li>

                <!-- Não Conformidades (Modal) -->
                <li><a href="#" onclick="openModal('naoConformidadesModal'); return false;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="link-name">Não Conformidades</span>
                    </a></li>

                <!-- Relatórios (Todos) -->
                <li><a href="relatorios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'relatorios.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span class="link-name">Relatórios</span>
                    </a></li>

                <?php if (isGestorOrAdmin()): ?>
                    <!-- Usuários (Super Admin e Gestor) -->
                    <li><a href="usuarios.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span class="link-name"><?php echo isSuperAdmin() ? 'Usuários' : 'Colaboradores'; ?></span>
                        </a></li>
                <?php endif; ?>

                <?php if (isSuperAdmin()): ?>
                    <!-- Setores (Super Admin) -->
                    <li><a href="setores.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'setores.php' ? 'active' : ''; ?>">
                            <i class="fas fa-building"></i>
                            <span class="link-name">Setores</span>
                        </a></li>
                <?php endif; ?>

                <?php if (isGestorOrAdmin()): ?>
                    <!-- Categorias (Gestor e Super Admin) -->
                    <li><a href="categorias.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tags"></i>
                            <span class="link-name">Categorias</span>
                        </a></li>
                <?php endif; ?>

                <!-- Configurações (Todos) -->
                <li><a href="configuracoes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span class="link-name">Configurações</span>
                    </a></li>
            </ul>

            <ul class="logout-mode">
                <li><a href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="link-name">Sair</span>
                    </a></li>

                <li class="mode">
                    <a href="#" onclick="toggleDarkMode(); return false;" style="cursor: pointer;">
                        <i class="fas fa-moon"></i>
                        <span class="link-name">Modo Escuro</span>
                    </a>
                    <div class="mode-toggle" onclick="toggleDarkMode();" style="cursor: pointer;">
                        <span class="switch"></span>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <section class="dashboard">
        <div class="top">
            <i class="fas fa-bars sidebar-toggle"></i>
            
            <!-- Informações do Usuário com Foto OU Ícone -->
            <div class="user-info" style="display: flex; align-items: center; gap: 12px; cursor: pointer;" onclick="window.location.href='configuracoes.php'" title="Clique para editar seu perfil">
                
                <?php if ($tem_foto_custom): ?>
                    <!-- Foto personalizada do usuário -->
                    <img src="<?php echo htmlspecialchars($user_foto); ?>" 
                         alt="Foto de <?php echo sanitize($user_data['nome'] ?? 'Usuário'); ?>"
                         style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #10b981; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <!-- Fallback: Ícone (caso imagem falhe ao carregar) -->
                    <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: none; align-items: center; justify-content: center; border: 2px solid #10b981; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <i class="fas fa-user" style="color: white; font-size: 20px;"></i>
                    </div>
                <?php else: ?>
                    <!-- Ícone padrão de usuário -->
                    <div style="width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; border: 2px solid #10b981; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <i class="fas fa-user" style="color: white; font-size: 20px;"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Nome e Tipo do Usuário -->
                <div style="display: flex; flex-direction: column; line-height: 1.4;">
                    <span style="color: var(--text-color); font-weight: 600; font-size: 14px;">
                        <?php echo sanitize($user_data['nome'] ?? $_SESSION['nome'] ?? 'Usuário'); ?>
                    </span>
                    <?php if (isset($_SESSION['tipo'])): ?>
                        <small style="color: #999; font-size: 12px;">
                            <?php
                            $tipos = [
                                'super_admin' => 'Super Administrador',
                                'gestor' => 'Gestor',
                                'usuario' => 'Usuário'
                            ];
                            echo $tipos[$_SESSION['tipo']] ?? $_SESSION['tipo'];
                            ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dash-content">

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" id="alertSuccess">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo sanitize($_SESSION['success']);
                            unset($_SESSION['success']); ?></span>
                    <button onclick="closeAlert('alertSuccess')" style="margin-left: auto; background: none; border: none; cursor: pointer; color: inherit; font-size: 18px;">×</button>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error" id="alertError">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo sanitize($_SESSION['error']);
                            unset($_SESSION['error']); ?></span>
                    <button onclick="closeAlert('alertError')" style="margin-left: auto; background: none; border: none; cursor: pointer; color: inherit; font-size: 18px;">×</button>
                </div>
            <?php endif; ?>

<?php
// ============================================
// INCLUI MODAIS GLOBAIS
// ============================================
require_once __DIR__ . '/modais_globais.php';
?>