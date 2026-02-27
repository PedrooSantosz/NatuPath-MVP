<?php
// ============================================
// DIAGNÓSTICO E CORREÇÃO - Sistema de Setores
// ============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Sistema de Setores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
            background: #f3f4f6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #10b981;
            margin-bottom: 10px;
        }
        h2 {
            color: #374151;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e5e7eb;
        }
        .check {
            display: flex;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            gap: 12px;
        }
        .check.success {
            background: #d1fae5;
            border-left: 4px solid #10b981;
        }
        .check.error {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
        }
        .check.warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .check i {
            font-size: 24px;
        }
        .check.success i { color: #10b981; }
        .check.error i { color: #ef4444; }
        .check.warning i { color: #f59e0b; }
        .check-content {
            flex: 1;
        }
        .check-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .check-desc {
            font-size: 14px;
            color: #6b7280;
        }
        code {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        .solution {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .solution h3 {
            color: #1e40af;
            margin-bottom: 10px;
        }
        .solution ol {
            padding-left: 20px;
        }
        .solution li {
            margin: 8px 0;
            line-height: 1.6;
        }
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 10px 0;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-stethoscope"></i> Diagnóstico do Sistema de Setores</h1>
        <p style="color: #6b7280; margin-bottom: 30px;">
            Este diagnóstico verifica todos os componentes necessários para o funcionamento correto do módulo de setores.
        </p>

        <?php
        require_once __DIR__ . '/../../config/config.php';
        
        $problemas = [];
        $avisos = [];
        $sucessos = [];
        
        // ============================================
        // 1. VERIFICAR ESTRUTURA DO BANCO DE DADOS
        // ============================================
        echo "<h2>1️⃣ Estrutura do Banco de Dados</h2>";
        
        try {
            $stmt = $conn->query("DESCRIBE setores");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (in_array('gestor_id', $columns)) {
                $sucessos[] = [
                    'titulo' => 'Coluna gestor_id existe',
                    'desc' => 'A coluna gestor_id está presente na tabela setores'
                ];
                echo "<div class='check success'>";
                echo "<i class='fas fa-check-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>✅ Coluna gestor_id existe</div>";
                echo "<div class='check-desc'>A estrutura do banco de dados está correta</div>";
                echo "</div></div>";
            } else {
                $problemas[] = [
                    'titulo' => 'Coluna gestor_id NÃO existe',
                    'desc' => 'Execute o script de correção SQL'
                ];
                echo "<div class='check error'>";
                echo "<i class='fas fa-times-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>❌ Coluna gestor_id NÃO existe</div>";
                echo "<div class='check-desc'>A tabela setores precisa ser corrigida</div>";
                echo "</div></div>";
            }
            
            // Verificar chave estrangeira
            $stmt = $conn->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_NAME = 'setores' 
                AND COLUMN_NAME = 'gestor_id' 
                AND REFERENCED_TABLE_NAME = 'usuarios'
            ");
            
            if ($stmt->rowCount() > 0) {
                echo "<div class='check success'>";
                echo "<i class='fas fa-check-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>✅ Chave estrangeira configurada</div>";
                echo "<div class='check-desc'>O relacionamento com a tabela usuarios está correto</div>";
                echo "</div></div>";
            } else {
                $avisos[] = [
                    'titulo' => 'Chave estrangeira não encontrada',
                    'desc' => 'Recomenda-se adicionar para integridade referencial'
                ];
                echo "<div class='check warning'>";
                echo "<i class='fas fa-exclamation-triangle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>⚠️ Chave estrangeira não configurada</div>";
                echo "<div class='check-desc'>Funciona, mas é recomendado adicionar para melhor integridade</div>";
                echo "</div></div>";
            }
            
        } catch(PDOException $e) {
            $problemas[] = [
                'titulo' => 'Erro ao verificar banco de dados',
                'desc' => $e->getMessage()
            ];
            echo "<div class='check error'>";
            echo "<i class='fas fa-times-circle'></i>";
            echo "<div class='check-content'>";
            echo "<div class='check-title'>❌ Erro ao verificar banco de dados</div>";
            echo "<div class='check-desc'>" . htmlspecialchars($e->getMessage()) . "</div>";
            echo "</div></div>";
        }
        
        // ============================================
        // 2. VERIFICAR ARQUIVOS PHP
        // ============================================
        echo "<h2>2️⃣ Arquivos PHP</h2>";
        
        $arquivos_necessarios = [
            'setores.php' => __DIR__ . '/setores.php',
            'modais_globais.php' => __DIR__ . '/modais_globais.php',
            'process_setor.php' => __DIR__ . '/../../config/validacao/process_setor.php',
            'functions.php' => __DIR__ . '/../../config/functions.php'
        ];
        
        foreach ($arquivos_necessarios as $nome => $caminho) {
            if (file_exists($caminho)) {
                echo "<div class='check success'>";
                echo "<i class='fas fa-check-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>✅ {$nome} encontrado</div>";
                echo "<div class='check-desc'>Caminho: <code>" . htmlspecialchars($caminho) . "</code></div>";
                echo "</div></div>";
            } else {
                $problemas[] = [
                    'titulo' => "{$nome} não encontrado",
                    'desc' => "Caminho esperado: {$caminho}"
                ];
                echo "<div class='check error'>";
                echo "<i class='fas fa-times-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>❌ {$nome} não encontrado</div>";
                echo "<div class='check-desc'>Caminho: <code>" . htmlspecialchars($caminho) . "</code></div>";
                echo "</div></div>";
            }
        }
        
        // ============================================
        // 3. VERIFICAR MODAL NO modais_globais.php
        // ============================================
        echo "<h2>3️⃣ Modal de Setores</h2>";
        
        $modais_path = __DIR__ . '/modais_globais.php';
        if (file_exists($modais_path)) {
            $conteudo = file_get_contents($modais_path);
            
            if (strpos($conteudo, 'id="modalSetor"') !== false) {
                echo "<div class='check success'>";
                echo "<i class='fas fa-check-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>✅ Modal modalSetor encontrado</div>";
                echo "<div class='check-desc'>O modal está implementado no arquivo modais_globais.php</div>";
                echo "</div></div>";
            } else {
                $problemas[] = [
                    'titulo' => 'Modal modalSetor não encontrado',
                    'desc' => 'O modal precisa ser adicionado ao modais_globais.php'
                ];
                echo "<div class='check error'>";
                echo "<i class='fas fa-times-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>❌ Modal modalSetor não encontrado</div>";
                echo "<div class='check-desc'>O modal de setores não está implementado</div>";
                echo "</div></div>";
            }
            
            if (strpos($conteudo, 'id="modalColaboradores"') !== false) {
                echo "<div class='check success'>";
                echo "<i class='fas fa-check-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>✅ Modal modalColaboradores encontrado</div>";
                echo "<div class='check-desc'>O modal de colaboradores está implementado</div>";
                echo "</div></div>";
            } else {
                $avisos[] = [
                    'titulo' => 'Modal modalColaboradores não encontrado',
                    'desc' => 'Recomendado para visualizar colaboradores do setor'
                ];
                echo "<div class='check warning'>";
                echo "<i class='fas fa-exclamation-triangle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>⚠️ Modal modalColaboradores não encontrado</div>";
                echo "<div class='check-desc'>Não é crítico, mas é útil para visualizar colaboradores</div>";
                echo "</div></div>";
            }
        }
        
        // ============================================
        // 4. VERIFICAR FUNÇÕES JAVASCRIPT
        // ============================================
        echo "<h2>4️⃣ Funções JavaScript</h2>";
        
        $setores_path = __DIR__ . '/setores.php';
        if (file_exists($setores_path)) {
            $conteudo_setores = file_get_contents($setores_path);
            
            $funcoes_necessarias = [
                'openModalSetor' => 'Abre o modal para criar/editar setor',
                'editarSetor' => 'Função para editar um setor existente',
                'deletarSetor' => 'Função para deletar um setor',
                'verColaboradores' => 'Função para ver colaboradores do setor'
            ];
            
            foreach ($funcoes_necessarias as $funcao => $descricao) {
                if (strpos($conteudo_setores, "function {$funcao}") !== false) {
                    echo "<div class='check success'>";
                    echo "<i class='fas fa-check-circle'></i>";
                    echo "<div class='check-content'>";
                    echo "<div class='check-title'>✅ Função {$funcao}() encontrada</div>";
                    echo "<div class='check-desc'>{$descricao}</div>";
                    echo "</div></div>";
                } else {
                    $problemas[] = [
                        'titulo' => "Função {$funcao}() não encontrada",
                        'desc' => $descricao
                    ];
                    echo "<div class='check error'>";
                    echo "<i class='fas fa-times-circle'></i>";
                    echo "<div class='check-content'>";
                    echo "<div class='check-title'>❌ Função {$funcao}() não encontrada</div>";
                    echo "<div class='check-desc'>{$descricao}</div>";
                    echo "</div></div>";
                }
            }
        }
        
        // ============================================
        // 5. TESTAR CONSULTA SQL
        // ============================================
        echo "<h2>5️⃣ Teste de Consulta SQL</h2>";
        
        try {
            $stmt = $conn->query("
                SELECT 
                    s.id, 
                    s.nome, 
                    s.gestor_id,
                    u.nome as gestor_nome
                FROM setores s
                LEFT JOIN usuarios u ON s.gestor_id = u.id
                LIMIT 1
            ");
            
            if ($stmt) {
                echo "<div class='check success'>";
                echo "<i class='fas fa-check-circle'></i>";
                echo "<div class='check-content'>";
                echo "<div class='check-title'>✅ Consulta SQL funcionando</div>";
                echo "<div class='check-desc'>O JOIN entre setores e usuarios está funcionando corretamente</div>";
                echo "</div></div>";
                
                // Mostrar exemplo de dados
                $exemplo = $stmt->fetch();
                if ($exemplo) {
                    echo "<div class='check success'>";
                    echo "<i class='fas fa-info-circle'></i>";
                    echo "<div class='check-content'>";
                    echo "<div class='check-title'>📊 Exemplo de dados</div>";
                    echo "<div class='check-desc'>";
                    echo "Setor: <strong>" . htmlspecialchars($exemplo['nome']) . "</strong><br>";
                    echo "Gestor: <strong>" . ($exemplo['gestor_nome'] ? htmlspecialchars($exemplo['gestor_nome']) : 'Sem gestor') . "</strong>";
                    echo "</div></div></div>";
                }
            }
        } catch(PDOException $e) {
            $problemas[] = [
                'titulo' => 'Erro na consulta SQL',
                'desc' => $e->getMessage()
            ];
            echo "<div class='check error'>";
            echo "<i class='fas fa-times-circle'></i>";
            echo "<div class='check-content'>";
            echo "<div class='check-title'>❌ Erro na consulta SQL</div>";
            echo "<div class='check-desc'>" . htmlspecialchars($e->getMessage()) . "</div>";
            echo "</div></div>";
        }
        
        // ============================================
        // RESUMO FINAL
        // ============================================
        echo "<h2>📊 Resumo Final</h2>";
        
        $total_checks = count($sucessos) + count($problemas) + count($avisos);
        
        echo "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;'>";
        
        echo "<div style='text-align: center; padding: 20px; background: #d1fae5; border-radius: 8px;'>";
        echo "<div style='font-size: 36px; color: #10b981; font-weight: bold;'>" . count($sucessos) . "</div>";
        echo "<div style='color: #047857; font-weight: 600;'>Sucessos</div>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 20px; background: #fef3c7; border-radius: 8px;'>";
        echo "<div style='font-size: 36px; color: #f59e0b; font-weight: bold;'>" . count($avisos) . "</div>";
        echo "<div style='color: #92400e; font-weight: 600;'>Avisos</div>";
        echo "</div>";
        
        echo "<div style='text-align: center; padding: 20px; background: #fee2e2; border-radius: 8px;'>";
        echo "<div style='font-size: 36px; color: #ef4444; font-weight: bold;'>" . count($problemas) . "</div>";
        echo "<div style='color: #991b1b; font-weight: 600;'>Problemas</div>";
        echo "</div>";
        
        echo "</div>";
        
        // ============================================
        // SOLUÇÕES
        // ============================================
        if (!empty($problemas)) {
            echo "<div class='solution'>";
            echo "<h3><i class='fas fa-wrench'></i> Soluções Recomendadas</h3>";
            echo "<ol>";
            
            foreach ($problemas as $problema) {
                echo "<li><strong>" . htmlspecialchars($problema['titulo']) . "</strong><br>";
                echo "<small>" . htmlspecialchars($problema['desc']) . "</small></li>";
            }
            
            echo "</ol>";
            
            echo "<h4 style='margin-top: 20px;'>Script SQL de Correção:</h4>";
            echo "<pre>-- Adicionar coluna gestor_id se não existir
ALTER TABLE setores 
ADD COLUMN IF NOT EXISTS gestor_id INT DEFAULT NULL AFTER descricao;

-- Adicionar índice
ALTER TABLE setores 
ADD INDEX IF NOT EXISTS idx_gestor (gestor_id);

-- Adicionar chave estrangeira
ALTER TABLE setores
ADD CONSTRAINT fk_setores_gestor 
FOREIGN KEY (gestor_id) REFERENCES usuarios(id) ON DELETE SET NULL;</pre>";
            
            echo "</div>";
        } else {
            echo "<div style='background: #d1fae5; border: 2px solid #10b981; padding: 20px; border-radius: 8px; text-align: center;'>";
            echo "<i class='fas fa-check-circle' style='font-size: 48px; color: #10b981; margin-bottom: 10px;'></i>";
            echo "<h3 style='color: #047857; margin: 0;'>✅ Sistema de Setores está funcionando perfeitamente!</h3>";
            echo "<p style='color: #065f46; margin-top: 10px;'>Todos os componentes necessários foram encontrados e estão configurados corretamente.</p>";
            echo "</div>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="setores.php" class="btn">
                <i class="fas fa-building"></i> Ir para Página de Setores
            </a>
        </div>
    </div>
</body>
</html>