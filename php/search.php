<?php
// Configuração para debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Habilitar CORS para testes locais
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// Log para arquivo
function logDebug($message)
{
    $logFile = '../logs/search_log.txt';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Conectar ao banco de dados
$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

try {
    // Log de início da pesquisa
    logDebug("Iniciando pesquisa");

    // Conectar ao banco de dados diretamente
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obter parâmetros
    $term = isset($_GET['term']) ? trim($_GET['term']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';

    logDebug("Termo: '$term', Tipo: '$type'");

    // Obter o nome exato das colunas na tabela
    $columns = [];
    $colQuery = $pdo->query("SHOW COLUMNS FROM wells");
    while ($col = $colQuery->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $col['Field'];
    }

    logDebug("Colunas na tabela: " . implode(", ", $columns));

    // Verificar nomes de colunas específicas
    $cityColumn = in_array('city', $columns) ? 'city' : (in_array('CITY', $columns) ? 'CITY' : 'cidade');
    $wellColumn = in_array('wellName', $columns) ? 'wellName' : (in_array('WELLNAME', $columns) ? 'WELLNAME' : 'nome_poco');
    $latColumn = in_array('latitude', $columns) ? 'latitude' : 'lat';
    $lngColumn = in_array('longitude', $columns) ? 'longitude' : 'lng';
    $photoColumn = in_array('photo', $columns) ? 'photo' : 'foto';

    // Consulta SQL com os nomes corretos das colunas
    if (empty($term)) {
        // Se não houver termo, retornar tudo (limitado a 20 registros)
        $stmt = $pdo->query("SELECT * FROM wells LIMIT 20");
    } else {
        switch ($type) {
            case 'city':
                $sql = "SELECT * FROM wells WHERE $cityColumn LIKE :term";
                break;
            case 'well':
                $sql = "SELECT * FROM wells WHERE $wellColumn LIKE :term";
                break;
            default:
                $sql = "SELECT * FROM wells WHERE $cityColumn LIKE :term OR $wellColumn LIKE :term";
                break;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':term', "%$term%");
        $stmt->execute();
    }

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = count($results);

    logDebug("Encontrados $count resultados");

    // Normalizar resultados para garantir nomes de colunas consistentes
    $normalizedResults = [];
    foreach ($results as $row) {
        $normalizedRow = [
            'id' => $row['id'] ?? null,
            'city' => $row[$cityColumn] ?? '',
            'wellName' => $row[$wellColumn] ?? '',
            'latitude' => $row[$latColumn] ?? 0,
            'longitude' => $row[$lngColumn] ?? 0,
            'photo' => $row[$photoColumn] ?? null
        ];
        $normalizedResults[] = $normalizedRow;
    }

    if ($count > 0) {
        logDebug("Primeiro resultado normalizado: " . json_encode($normalizedResults[0]));
    }

    // Retornar resultados normalizados
    echo json_encode($normalizedResults);
} catch (PDOException $e) {
    logDebug("Erro: " . $e->getMessage());
    echo json_encode(['error' => 'Erro no banco de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    logDebug("Erro: " . $e->getMessage());
    echo json_encode(['error' => 'Erro: ' . $e->getMessage()]);
}
