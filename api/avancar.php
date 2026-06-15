<?php
require_once "../db/conexao.php";
header('Content-Type: application/json');

$codigo   = isset($_GET['sala'])     ? strtoupper(trim($_GET['sala'])) : '';
$encerrar = isset($_GET['encerrar']) ? true : false;
$peek     = isset($_GET['peek'])     ? true : false;

if (!$codigo) { echo json_encode(['erro' => 'Sala não informada']); exit; }

$sql  = "SELECT * FROM salas WHERE codigo = '$codigo'";
$res  = mysqli_query($conn, $sql);
$sala = mysqli_fetch_assoc($res);

if (!$sala) { echo json_encode(['erro' => 'Sala não encontrada']); exit; }

if ($encerrar) {
    mysqli_query($conn, "UPDATE salas SET status='finalizada' WHERE codigo='$codigo'");
    echo json_encode(['status' => 'finalizada']);
    exit;
}

// Peek: retorna pergunta atual sem avançar
$numero = intval($sala['pergunta_atual']);
if ($peek) {
    $sql      = "SELECT * FROM perguntas LIMIT 1 OFFSET $numero";
    $res      = mysqli_query($conn, $sql);
    $pergunta = mysqli_fetch_assoc($res);
    echo json_encode([
        'status'   => 'jogando',
        'resposta' => $pergunta ? $pergunta['resposta'] : ''
    ]);
    exit;
}

// Avança para a próxima
$proximo  = $numero + 1;
$sql      = "SELECT * FROM perguntas LIMIT 1 OFFSET $proximo";
$res      = mysqli_query($conn, $sql);
$pergunta = mysqli_fetch_assoc($res);

if (!$pergunta) {
    mysqli_query($conn, "UPDATE salas SET status='finalizada' WHERE codigo='$codigo'");
    echo json_encode(['status' => 'finalizada']);
    exit;
}

mysqli_query($conn, "UPDATE salas SET pergunta_atual=$proximo WHERE codigo='$codigo'");

echo json_encode([
    'status'   => 'jogando',
    'numero'   => $proximo + 1,
    'texto'    => $pergunta['texto'],
    'opcao_a'  => $pergunta['opcao_a'],
    'opcao_b'  => $pergunta['opcao_b'],
    'opcao_c'  => $pergunta['opcao_c'],
    'opcao_d'  => $pergunta['opcao_d'],
    'resposta' => $pergunta['resposta']
]);
?>