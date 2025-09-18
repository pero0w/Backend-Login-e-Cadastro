<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
// CORREÇÃO 1: Adicionado OPTIONS aqui
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// CORREÇÃO 2: Bloco para responder ao "preflight request"
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// O resto do seu código continua igual...
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "app_cadastro";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

$dados = json_decode(file_get_contents("php://input"));

if (!empty($dados->respostas) && !empty($dados->id_usuario)) {
    $id_usuario = intval($dados->id_usuario);

    $stmt_submissao = $conexao->prepare("INSERT INTO submissoes (id_usuario) VALUES (?)");
    $stmt_submissao->bind_param("i", $id_usuario);
    $stmt_submissao->execute();
    $id_nova_submissao = $conexao->insert_id;
    $stmt_submissao->close();

    $stmt_respostas = $conexao->prepare("INSERT INTO respostas (id_pergunta, resposta, id_submissao) VALUES (?, ?, ?)");
    $stmt_respostas->bind_param("iii", $id_pergunta, $resposta_bool, $id_nova_submissao);

    foreach ($dados->respostas as $item) {
        $id_pergunta = $item->id_pergunta;
        $resposta_bool = $item->resposta ? 1 : 0;
        $stmt_respostas->execute();
    }

    $stmt_respostas->close();
    $conexao->close();

    http_response_code(200);
    echo json_encode(array("mensagem" => "Respostas salvas com sucesso."));
} else {
    http_response_code(400);
    echo json_encode(array("mensagem" => "Dados incompletos. ID do usuário ou respostas faltando."));
}
?>