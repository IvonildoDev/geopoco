<?php
// Script para configuração inicial do banco de dados
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Função para log
function logSetup($message)
{
    echo date('Y-m-d H:i:s') . " - " . $message . "<br>\n";

    $logFile = '../logs/setup_log.txt';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Configurações do banco de dados
$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

echo "<h1>GeoWell - Configuração do Banco de Dados</h1>";

try {
    logSetup("Iniciando configuração do banco de dados");

    // Conectar ao MySQL sem especificar o banco de dados
    logSetup("Tentando conectar ao servidor MySQL...");
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logSetup("Conexão ao servidor MySQL estabelecida com sucesso");

    // Verificar se o banco de dados existe
    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
    $dbExists = $stmt->rowCount() > 0;

    if ($dbExists) {
        logSetup("O banco de dados '$db' já existe");
    } else {
        logSetup("O banco de dados '$db' não existe. Tentando criar...");
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        logSetup("Banco de dados '$db' criado com sucesso");
    }

    // Conectar ao banco de dados específico
    logSetup("Conectando ao banco de dados '$db'...");
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logSetup("Conexão ao banco de dados '$db' estabelecida com sucesso");

    // Verificar se a tabela existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'wells'")->rowCount() > 0;

    if ($tableExists) {
        logSetup("A tabela 'wells' já existe");
    } else {
        logSetup("A tabela 'wells' não existe. Criando...");
        $sql = "CREATE TABLE wells (
            id INT AUTO_INCREMENT PRIMARY KEY,
            city VARCHAR(100) NOT NULL,
            wellName VARCHAR(100) NOT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            photo VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($sql);
        logSetup("Tabela 'wells' criada com sucesso");
    }

    echo "<div style='background-color: #dff0d8; color: #3c763d; padding: 10px; border-radius: 5px; margin-top: 20px;'>";
    echo "<strong>Configuração concluída com sucesso!</strong><br>";
    echo "O banco de dados está pronto para uso.";
    echo "</div>";

    echo "<div style='margin-top: 20px;'>";
    echo "<a href='../index.html' style='background-color: #337ab7; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px;'>Voltar para o aplicativo</a>";
    echo "</div>";
} catch (PDOException $e) {
    logSetup("ERRO: " . $e->getMessage());

    echo "<div style='background-color: #f2dede; color: #a94442; padding: 10px; border-radius: 5px; margin-top: 20px;'>";
    echo "<strong>Erro na configuração do banco de dados:</strong><br>";
    echo $e->getMessage();
    echo "</div>";

    echo "<h2>Possíveis soluções:</h2>";
    echo "<ul>";
    echo "<li>Verifique se o MySQL está em execução</li>";
    echo "<li>Verifique se as credenciais (usuário e senha) estão corretas</li>";
    echo "<li>Se estiver usando XAMPP, verifique se o serviço MySQL foi iniciado</li>";
    echo "<li>Certifique-se de que o usuário 'root' tem permissões para criar bancos de dados</li>";
    echo "</ul>";

    echo "<div style='margin-top: 20px;'>";
    echo "<button onclick='location.reload()' style='background-color: #337ab7; color: white; padding: 10px 15px; border: none; border-radius: 3px; cursor: pointer;'>Tentar novamente</button>";
    echo "</div>";
}
