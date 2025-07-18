<?php
// Cabeçalhos CORS
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$conn = new mysqli("localhost", "root", "", "app_cadastro");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro de conexão com o Banco de Dados."]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->email) || !isset($data->senha)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "E-mail e senha são obrigatórios."]);
    exit();
}

$email = $conn->real_escape_string($data->email);
$senha_enviada = $data->senha;

$sql = "SELECT * FROM usuarios WHERE email = '$email'";
$result = $conn->query($sql);

if ($result && $result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($senha_enviada, $user['senha'])) {
        // Senha correta, agora verifica se o e-mail foi confirmado
        if ($user['email_verificado'] == 1) {
            // Login permitido
            echo json_encode(["success" => true, "message" => "Login bem-sucedido", "usuario" => $user]);
        } else {
            // E-mail ainda não verificado
            http_response_code(403); // 403 Forbidden
            echo json_encode(["success" => false, "message" => "Seu e-mail ainda não foi verificado. Por favor, verifique sua caixa de entrada."]);
        }
    } else {
        // Senha incorreta
        http_response_code(401); // 401 Unauthorized
        echo json_encode(["success" => false, "message" => "E-mail ou senha incorretos."]);
    }
} else {
    // Usuário não encontrado
    http_response_code(401); // 401 Unauthorized
    echo json_encode(["success" => false, "message" => "E-mail ou senha incorretos."]);
}

$conn->close();
?>