<?php
// Este arquivo é apenas para testes - não use em produção
// Acesse: http://localhost/coletacordenadas/php/test_search.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

// Parâmetros de teste
$term = isset($_GET['term']) ? $_GET['term'] : 'São';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

echo "<h1>Teste de Busca - GeoWell</h1>";
echo "<p>Termo de busca: <strong>$term</strong> | Tipo: <strong>$type</strong></p>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Construir consulta
    $sql = "SELECT * FROM wells WHERE ";
    $params = [];

    switch ($type) {
        case 'city':
            $sql .= "city LIKE ?";
            $params[] = "%$term%";
            break;
        case 'well':
            $sql .= "wellName LIKE ?";
            $params[] = "%$term%";
            break;
        default: // 'all'
            $sql .= "city LIKE ? OR wellName LIKE ?";
            $params[] = "%$term%";
            $params[] = "%$term%";
    }

    echo "<h2>Consulta SQL</h2>";
    echo "<pre>$sql</pre>";
    echo "<p>Parâmetros: " . implode(", ", array_map(function ($p) {
        return "'$p'";
    }, $params)) . "</p>";

    // Executar consulta
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Resultados (encontrados: " . count($results) . ")</h2>";

    if (count($results) > 0) {
        // Mostrar detalhes dos resultados
        echo "<table border='1' cellpadding='5'>";

        // Cabeçalhos
        echo "<tr>";
        foreach (array_keys($results[0]) as $header) {
            echo "<th>$header</th>";
        }
        echo "<th>Ações</th>";
        echo "</tr>";

        // Dados
        foreach ($results as $row) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                if ($key === 'photo' && !empty($value)) {
                    echo "<td><img src='../uploads/$value' width='100' onerror=\"this.src='../img/no-image.png';\"></td>";
                } else {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
            }
            echo "<td><button onclick='testShowData(" . json_encode($row) . ")'>Ver JSON</button></td>";
            echo "</tr>";
        }

        echo "</table>";

        // Mostrar JSON formatado
        echo "<h2>JSON de Exemplo (primeiro resultado)</h2>";
        echo "<pre>" . htmlspecialchars(json_encode($results[0], JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<div style='padding: 20px; background: #ffdddd; border: 1px solid #ff0000;'>";
        echo "<p>Nenhum resultado encontrado para o termo de busca '$term'.</p>";
        echo "</div>";
    }
} catch (PDOException $e) {
    echo "<div style='padding: 20px; background: #ffdddd; border: 1px solid #ff0000;'>";
    echo "<h2>Erro de Banco de Dados</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<h2>Testar outras buscas</h2>
<form method="GET">
    <label>
        Termo de busca:
        <input type="text" name="term" value="<?= htmlspecialchars($term) ?>">
    </label>
    <br>
    <label>
        <input type="radio" name="type" value="all" <?= $type === 'all' ? 'checked' : '' ?>> Todos
    </label>
    <label>
        <input type="radio" name="type" value="city" <?= $type === 'city' ? 'checked' : '' ?>> Cidade
    </label>
    <label>
        <input type="radio" name="type" value="well" <?= $type === 'well' ? 'checked' : '' ?>> Poço
    </label>
    <br>
    <button type="submit">Testar</button>
</form>

<h2>Links Úteis</h2>
<ul>
    <li><a href="../index.html">Voltar para a página principal</a></li>
    <li><a href="db_setup.php">Configurar banco de dados</a></li>
</ul>

<script>
    function testShowData(data) {
        alert(JSON.stringify(data, null, 2));
    }
</script>

<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        line-height: 1.6;
    }

    h1,
    h2 {
        color: #0066cc;
    }

    table {
        border-collapse: collapse;
        margin-bottom: 20px;
        width: 100%;
    }

    th {
        background-color: #f0f0f0;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    pre {
        background-color: #f8f8f8;
        border: 1px solid #ddd;
        padding: 10px;
        overflow: auto;
    }

    form {
        background-color: #f0f0f0;
        padding: 15px;
        margin-bottom: 20px;
    }

    label {
        margin-right: 15px;
    }
</style>