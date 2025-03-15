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
    logDebug("Diretório de uploads criado: $uploadDir");
}

// Conectar ao banco de dados
$host = 'localhost';
$db = 'geowell';
$user = 'root';
$pass = '';

try {
    // Primeiro, conectar sem especificar o banco de dados
    $pdoBase = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdoBase->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logDebug("Conexão inicial ao MySQL estabelecida");

    // Verificar se o banco de dados existe
    $dbExists = $pdoBase->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'")->rowCount() > 0;
    logDebug("Verificação de banco de dados: " . ($dbExists ? "Banco '$db' existe" : "Banco '$db' não existe"));

    // Criar o banco de dados se não existir
    if (!$dbExists) {
        logDebug("Tentando criar o banco de dados '$db'");
        $pdoBase->exec("CREATE DATABASE IF NOT EXISTS `$db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        logDebug("Banco de dados '$db' criado com sucesso");
    }

    // Agora conectar ao banco de dados específico
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logDebug("Conexão com banco de dados '$db' estabelecida");

    // Verificar se a tabela existe
    $tableExists = $pdo->query("SHOW TABLES LIKE 'wells'")->rowCount() > 0;
    logDebug("Verificação de tabela: " . ($tableExists ? "Tabela 'wells' existe" : "Tabela 'wells' não existe"));

    if (!$tableExists) {
        // Criar tabela se não existir
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
        logDebug("Tabela 'wells' criada com sucesso");
    }

    // Log inicial
    logDebug("Iniciando cadastro de poço");

    // Depurar dados recebidos
    logDebug("POST recebidos: " . print_r($_POST, true));
    logDebug("FILES recebidos: " . print_r($_FILES, true));

    // Verificar dados do formulário
    if (!isset($_POST['city']) || !isset($_POST['wellName']) || !isset($_POST['latitude']) || !isset($_POST['longitude'])) {
        throw new Exception("Dados obrigatórios não fornecidos");
    }

    $city = trim($_POST['city']);
    $wellName = trim($_POST['wellName']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);

    logDebug("Dados validados: city=$city, wellName=$wellName, lat=$latitude, long=$longitude");

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
            logDebug("Falha ao mover arquivo de $tempFile para $targetFile");
            throw new Exception("Falha ao mover arquivo de imagem");
        }

        logDebug("Foto salva como: $photoName em $targetFile");
    } elseif (isset($_FILES['photo'])) {
        // Log do erro de upload caso exista
        $uploadError = $_FILES['photo']['error'];
        logDebug("Erro no upload da foto. Código: $uploadError");
    }

    // Inserir dados no banco
    $sql = "INSERT INTO wells (city, wellName, latitude, longitude, photo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    logDebug("SQL preparado: $sql");
    logDebug("Parâmetros: city=$city, wellName=$wellName, lat=$latitude, long=$longitude, photo=$photoName");

    // Verificar valores antes de inserir
    if (strlen($city) > 100 || strlen($wellName) > 100) {
        throw new Exception("Dados muito longos para os campos city ou wellName");
    }

    try {
        $result = $stmt->execute([$city, $wellName, $latitude, $longitude, $photoName]);
        logDebug("Execução da SQL: " . ($result ? "Sucesso" : "Falha"));

        if (!$result) {
            $errorInfo = $stmt->errorInfo();
            logDebug("Detalhes do erro SQL: " . print_r($errorInfo, true));
            throw new Exception("Falha ao inserir dados no banco: " . $errorInfo[2]);
        }

        $id = $pdo->lastInsertId();
        logDebug("Poço cadastrado com sucesso. ID: $id");
    } catch (PDOException $pdoEx) {
        logDebug("Exceção PDO ao inserir: " . $pdoEx->getMessage() . "\nCódigo: " . $pdoEx->getCode());
        throw new Exception("Erro ao inserir no banco: " . $pdoEx->getMessage());
    }

    // Determinar se é uma chamada AJAX ou um envio de formulário normal
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Requisição AJAX - retornar JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Poço cadastrado com sucesso!',
            'id' => $id
        ]);
    } else {
        // Envio normal - redirecionar
        header("Location: ../index.html?success=true&message=" . urlencode('Poço cadastrado com sucesso!'));
    }
    exit;
} catch (PDOException $e) {
    logDebug("Erro de banco de dados: " . $e->getMessage() . "\nCódigo: " . $e->getCode() . "\nTraço: " . $e->getTraceAsString());

    // Mensagem de erro mais amigável para o usuário
    $userMessage = "Erro ao acessar o banco de dados. ";

    // Verificar se é um erro de conexão ou banco de dados inexistente
    if ($e->getCode() == 1049) {
        $userMessage .= "O banco de dados 'geowell' não existe. O sistema tentou criá-lo automaticamente, mas ocorreu um erro.";
    } else if ($e->getCode() == 2002) {
        $userMessage .= "Não foi possível conectar ao servidor MySQL. Verifique se o MySQL está em execução.";
    } else {
        $userMessage .= $e->getMessage();
    }

    // Determinar se é uma chamada AJAX ou um envio de formulário normal
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Requisição AJAX - retornar JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $userMessage
        ]);
    } else {
        // Envio normal - redirecionar
        header("Location: ../index.html?error=true&message=" . urlencode($userMessage));
    }
    exit;
} catch (Exception $e) {
    logDebug("Erro: " . $e->getMessage() . "\nTraço: " . $e->getTraceAsString());

    // Determinar se é uma chamada AJAX ou um envio de formulário normal
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Requisição AJAX - retornar JSON
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao cadastrar: ' . $e->getMessage()
        ]);
    } else {
        // Envio normal - redirecionar
        header("Location: ../index.html?error=true&message=" . urlencode('Erro ao cadastrar: ' . $e->getMessage()));
    }
    exit;
}
