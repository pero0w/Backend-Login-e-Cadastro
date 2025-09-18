<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "app_cadastro";

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Conexão falhou: " . $conexao->connect_error);
}

$sql = "SELECT id, texto_pergunta FROM perguntas ORDER BY id";
$resultado = $conexao->query($sql);

$perguntas = array();
if ($resultado->num_rows > 0) {
    while($linha = $resultado->fetch_assoc()) {
        $linha['texto'] = $linha['texto_pergunta'];
        unset($linha['texto_pergunta']);
        $perguntas[] = $linha;
    }
}

echo json_encode($perguntas);
$conexao->close();
?>