<?php
// Arquivo para debug - use apenas em ambiente de desenvolvimento
// Para usar, acesse: http://localhost/coletacordenadas/debug.php

// Desativa exibição de erros no navegador (remova em produção)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h1>Informações de Debug - GeoWell</h1>";

    // Obter nomes das tabelas
    echo "<h2>Tabelas no banco de dados</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) === 0) {
        echo "<p>Nenhuma tabela encontrada. Verifique se o banco de dados 'geowell' existe e está configurado.</p>";
    } else {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    }

    // Para cada tabela, mostrar estrutura
    foreach ($tables as $table) {
        echo "<h2>Estrutura da tabela: $table</h2>";

        $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";

        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }

        echo "</table>";

        // Mostrar primeiros 5 registros da tabela
        echo "<h3>Registros da tabela (limitado a 5)</h3>";
        $rows = $pdo->query("SELECT * FROM $table LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) === 0) {
            echo "<p>Nenhum registro encontrado nesta tabela.</p>";
        } else {
            echo "<table border='1' cellpadding='5'>";

            // Cabeçalho da tabela
            echo "<tr>";
            foreach (array_keys($rows[0]) as $header) {
                echo "<th>$header</th>";
            }
            echo "</tr>";

            // Dados da tabela
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $key => $value) {
                    if ($key === 'photo' && !empty($value)) {
                        echo "<td><img src='uploads/$value' width='100' onerror=\"this.onerror=null;this.src='img/no-image.png';\"></td>";
                    } else {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                }
                echo "</tr>";
            }

            echo "</table>";
        }
    }
} catch (PDOException $e) {
    echo "<div style='color: red; padding: 20px; border: 1px solid red;'>";
    echo "<h2>Erro de Banco de Dados</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        line-height: 1.6;
    }

    h1,
    h2,
    h3 {
        color: #0066cc;
    }

    table {
        border-collapse: collapse;
        margin-bottom: 20px;
    }

    th {
        background-color: #f0f0f0;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
</style>