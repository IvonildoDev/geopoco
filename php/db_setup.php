<?php
// Arquivo para criar/configurar o banco de dados
// Acesse: http://localhost/coletacordenadas/php/db_setup.php

// Configurações de exibição para desenvolvimento
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definições do banco
$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

echo "<h1>Configuração do Banco de Dados GeoWell</h1>";

try {
    // Conectar ao MySQL sem especificar um banco
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<p>✅ Conexão ao MySQL estabelecida com sucesso.</p>";

    // Criar o banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    echo "<p>✅ Banco de dados '$db' verificado/criado com sucesso.</p>";

    // Selecionar o banco de dados
    $pdo->exec("USE `$db`");

    // Criar tabela de poços se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `wells` (
            `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `city` varchar(100) NOT NULL,
            `wellName` varchar(100) NOT NULL,
            `latitude` decimal(10,8) NOT NULL,
            `longitude` decimal(11,8) NOT NULL,
            `photo` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "<p>✅ Tabela 'wells' verificada/criada com sucesso.</p>";

    // Verificar se há dados de exemplo
    $stmt = $pdo->query("SELECT COUNT(*) FROM wells");
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // Inserir alguns dados de exemplo
        $pdo->exec("
            INSERT INTO `wells` (`city`, `wellName`, `latitude`, `longitude`, `photo`) VALUES
            ('São Paulo', 'Poço Central', -23.550520, -46.633308, NULL),
            ('Rio de Janeiro', 'Poço Copacabana', -22.969177, -43.180851, NULL),
            ('Belo Horizonte', 'Poço Pampulha', -19.852764, -43.963181, NULL)
        ");
        echo "<p>✅ Dados de exemplo inseridos com sucesso.</p>";
    } else {
        echo "<p>ℹ️ A tabela 'wells' já contém dados. Nenhum dado de exemplo foi inserido.</p>";
    }

    // Informações sobre os dados no banco
    echo "<h2>Dados na tabela 'wells':</h2>";
    $stmt = $pdo->query("SELECT * FROM wells LIMIT 10");
    $wells = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5'>";

    // Cabeçalhos
    if (count($wells) > 0) {
        echo "<tr>";
        foreach (array_keys($wells[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "</tr>";

        // Dados
        foreach ($wells as $well) {
            echo "<tr>";
            foreach ($well as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td>Nenhum dado encontrado</td></tr>";
    }

    echo "</table>";

    echo "<p>✅ Configuração concluída com sucesso!</p>";
    echo "<p>👉 <a href='../index.html'>Voltar para a página principal</a></p>";
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 20px; border: 1px solid red;'>";
    echo "<h2>❌ Erro:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";

    if (strpos($e->getMessage(), "Access denied") !== false) {
        echo "<h3>Sugestões:</h3>";
        echo "<ul>";
        echo "<li>Verifique se o servidor MySQL está em execução</li>";
        echo "<li>Verifique se as credenciais de usuário e senha estão corretas</li>";
        echo "<li>Verifique se o usuário tem permissões para criar bancos de dados</li>";
        echo "</ul>";
    }

    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        padding: 20px;
        max-width: 1000px;
        margin: 0 auto;
    }

    h1,
    h2 {
        color: #2196f3;
    }

    table {
        border-collapse: collapse;
        margin: 20px 0;
        width: 100%;
    }

    th {
        background-color: #f0f0f0;
        text-align: left;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    a {
        color: #2196f3;
        text-decoration: none;
        font-weight: bold;
    }

    a:hover {
        text-decoration: underline;
    }
</style>