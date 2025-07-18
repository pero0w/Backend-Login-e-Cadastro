<?php
// Em um ambiente de produção, você pode querer desativar a exibição de erros
// ini_set('display_errors', 0);
// error_reporting(0);
// Mas para desenvolvimento, é bom deixar como está:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Bloco de Cabeçalhos CORS ---
header("Access-Control-Allow-Origin: http://localhost:8100");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
// --- Fim do Bloco CORS ---

// Importa as classes do PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Carrega os arquivos da biblioteca PHPMailer usando o caminho absoluto
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

// Conexão com o banco de dados
$conn = new mysqli("localhost", "root", "", "app_cadastro");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro de conexão com o Banco de Dados."]);
    exit();
}

// Pega os dados enviados pelo Ionic
$data = json_decode(file_get_contents("php://input"));

if (!$data || !isset($data->nome) || !isset($data->email) || !isset($data->senha)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Dados obrigatórios ausentes (nome, email, senha)."]);
    exit();
}

// Limpa os dados
$nome = $conn->real_escape_string($data->nome);
$email = $conn->real_escape_string($data->email);
$senha_hashed = password_hash($data->senha, PASSWORD_DEFAULT);

// Diferencia o tipo de usuário
if (isset($data->area)) {
    $tipo = 'profissional';
    $area = $conn->real_escape_string($data->area);
    $instituto = $conn->real_escape_string($data->instituto);
} else {
    $tipo = 'responsavel';
    $area = '';
    $instituto = '';
}

try {
    // Insere o usuário com status não verificado (0)
    $sql_usuario = "INSERT INTO usuarios (nome, email, senha, tipo, area, instituto, email_verificado) VALUES ('$nome', '$email', '$senha_hashed', '$tipo', '$area', '$instituto', 0)";
    $conn->query($sql_usuario);

    $id_usuario_inserido = $conn->insert_id;

    // Gera e salva o token de verificação
    $token = bin2hex(random_bytes(32));
    $expira_em = date('Y-m-d H:i:s', time() + (60 * 60 * 24)); // Expira em 24h
    $sql_token = "INSERT INTO verificacao_email (id_usuario, token, expira_em) VALUES ('$id_usuario_inserido', '$token', '$expira_em')";
    
    if ($conn->query($sql_token)) {
        // Se o token foi salvo, tenta enviar o e-mail
        
        $mail = new PHPMailer(true);
        try {
            // Configurações do Servidor para o MailHog
            $mail->isSMTP();
            $mail->Host       = 'localhost';
            $mail->SMTPAuth   = false;
            $mail->Port       = 1025;
            $mail->CharSet    = 'UTF-8';

            // Remetente e Destinatário
            $mail->setFrom('nao-responda@teaamo.com', 'TEA-AMO');
            $mail->addAddress($email, $nome);

            // Conteúdo do E-mail
            $link_verificacao = "http://localhost/backend/verificar_email.php?token=" . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Ative sua Conta - TEAAMO';
            $mail->Body    = "Olá, <b>$nome</b>!<br><br>Obrigado por se cadastrar. Clique no link abaixo para ativar sua conta:<br><br><a href='$link_verificacao' style='background-color:#0094FF;color:white;padding:10px 15px;text-decoration:none;border-radius:5px;'>Ativar Minha Conta</a>";
            
            $mail->send();
            
            // Mensagem final de sucesso
            echo json_encode(["success" => true, "message" => "Cadastro realizado! Um e-mail de verificação foi enviado (verifique o MailHog)."]);

        } catch (Exception $e) {
            http_response_code(500);
            error_log("Erro do Mailer: {$mail->ErrorInfo}");
            echo json_encode(["success" => false, "message" => "Usuário cadastrado, mas o e-mail de verificação falhou. Erro: " . $mail->ErrorInfo]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Erro ao gerar token de verificação."]);
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1062) { // Erro de e-mail duplicado
        http_response_code(409); // Conflito
        echo json_encode(["success" => false, "message" => "Este e-mail já está em uso."]);
    } else {
        http_response_code(500);
        error_log("Erro de BD no cadastro: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Ocorreu um erro ao processar seu cadastro."]);
    }
}

$conn->close();
?>