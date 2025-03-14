<?php
// Configurações
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Função para log
function logDebug($message)
{
    $logFile = '../logs/save_log.txt';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Verificar se o diretório uploads existe
$uploadDir = '../uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Conectar ao banco de dados
$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

try {
    // Log inicial
    logDebug("Iniciando cadastro de poço");

    // Verificar dados do formulário
    if (!isset($_POST['city']) || !isset($_POST['wellName']) || !isset($_POST['latitude']) || !isset($_POST['longitude'])) {
        throw new Exception("Dados obrigatórios não fornecidos");
    }

    $city = trim($_POST['city']);
    $wellName = trim($_POST['wellName']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);

    if (empty($city) || empty($wellName) || $latitude == 0 || $longitude == 0) {
        throw new Exception("Campos obrigatórios não preenchidos corretamente");
    }

    // Processar foto se foi enviada
    $photoName = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $tempFile = $_FILES['photo']['tmp_name'];
        $originalName = $_FILES['photo']['name'];

        // Gerar nome único para evitar sobreposição
        $photoName = uniqid() . '_' . basename($originalName);
        $targetFile = $uploadDir . $photoName;

        // Mover arquivo para o diretório de uploads
        if (!move_uploaded_file($tempFile, $targetFile)) {
            throw new Exception("Falha ao mover arquivo de imagem");
        }

        logDebug("Foto salva como: $photoName");
    }

    // Conectar ao banco e inserir dados
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "INSERT INTO wells (city, wellName, latitude, longitude, photo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$city, $wellName, $latitude, $longitude, $photoName]);

    $id = $pdo->lastInsertId();
    logDebug("Poço cadastrado com sucesso. ID: $id");

    // Redirecionar de volta para a página inicial com mensagem de sucesso
    header("Location: ../index.html?message=Poço cadastrado com sucesso!");
} catch (Exception $e) {
    logDebug("Erro: " . $e->getMessage());
    header("Location: ../index.html?message=Erro ao cadastrar: " . urlencode($e->getMessage()));
}
