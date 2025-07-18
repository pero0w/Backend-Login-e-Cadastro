<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "app_cadastro");

if ($conn->connect_error) {
    die("Erro de conexão com o Banco de Dados: " . $conn->connect_error);
}

$mensagem_usuario = "Ocorreu um erro desconhecido.";

if (isset($_GET['token'])) {
    $token_recebido = $conn->real_escape_string($_GET['token']);

    $sql_busca_token = "SELECT * FROM verificacao_email WHERE token = '$token_recebido' LIMIT 1";
    $resultado_token = $conn->query($sql_busca_token);

    if ($resultado_token && $resultado_token->num_rows === 1) {
        $dados_token = $resultado_token->fetch_assoc();
        
        if (strtotime($dados_token['expira_em']) > time()) {
            // Token válido e não expirado
            $id_usuario_para_verificar = $dados_token['id_usuario'];

            $sql_update_usuario = "UPDATE usuarios SET email_verificado = 1 WHERE id = '$id_usuario_para_verificar'";

            if ($conn->query($sql_update_usuario)) {
                $sql_delete_token = "DELETE FROM verificacao_email WHERE token = '$token_recebido'";
                $conn->query($sql_delete_token);
                $mensagem_usuario = "Seu e-mail foi verificado com sucesso! Você já pode fazer o login no aplicativo.";
            } else {
                $mensagem_usuario = "Erro ao atualizar seu status. Por favor, contate o suporte.";
            }
        } else {
            $mensagem_usuario = "Link de verificação expirado.";
        }
    } else {
        $mensagem_usuario = "Link de verificação inválido.";
    }
} else {
    $mensagem_usuario = "Nenhum token de verificação fornecido.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Verificação de Conta</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f0f2f5; }
        .container { text-align: center; padding: 40px; background-color: white; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        p { color: #666; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Status da Verificação</h1>
        <p><?php echo htmlspecialchars($mensagem_usuario); ?></p>
    </div>
</body>
</html>