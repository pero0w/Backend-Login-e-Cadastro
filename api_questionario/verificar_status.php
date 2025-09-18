<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "app_cadastro"; // <-- CORRIGIDO

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

$id_usuario = isset($_GET['id_usuario']) ? intval($_GET['id_usuario']) : 0;

if ($id_usuario <= 0) {
    echo json_encode(['pode_responder' => false, 'mensagem' => 'ID de usuário inválido.']);
    exit;
}

$sql = "SELECT data_submissao FROM submissoes WHERE id_usuario = ? ORDER BY data_submissao DESC LIMIT 1";
$stmt = $conexao->prepare($sql);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $linha = $resultado->fetch_assoc();
    $ultima_submissao = new DateTime($linha['data_submissao']);
    $agora = new DateTime();
    $diferenca = $agora->getTimestamp() - $ultima_submissao->getTimestamp();

    if ($diferenca < 86400) { // 86400 segundos = 24 horas
        $tempo_restante = 86400 - $diferenca;
        $horas = floor($tempo_restante / 3600);
        $minutos = floor(($tempo_restante % 3600) / 60);
        echo json_encode([
            'pode_responder' => false,
            'mensagem' => "Você precisa esperar mais {$horas}h e {$minutos}min para responder novamente."
        ]);
    } else {
        echo json_encode(['pode_responder' => true]);
    }
} else {
    echo json_encode(['pode_responder' => true]);
}

$stmt->close();
$conexao->close();
?>