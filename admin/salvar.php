<?php
// =============================================
// SALVAR PERGUNTAS - admin/salvar.php
// Salva perguntas no banco (manual ou IA)
// =============================================
require_once "../db/conexao.php";
header('Content-Type: application/json');

// Detecta se veio JSON (IA) ou FormData (manual)
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    // Salvar múltiplas perguntas da IA
    $input    = json_decode(file_get_contents('php://input'), true);
    $perguntas = $input['perguntas'] ?? [];

    if (empty($perguntas)) {
        echo json_encode(['erro' => 'Nenhuma pergunta recebida']);
        exit;
    }

    $salvos = 0;
    foreach ($perguntas as $p) {
        $texto   = mysqli_real_escape_string($conn, $p['texto']);
        $opcao_a = mysqli_real_escape_string($conn, $p['opcao_a']);
        $opcao_b = mysqli_real_escape_string($conn, $p['opcao_b']);
        $opcao_c = mysqli_real_escape_string($conn, $p['opcao_c']);
        $opcao_d = mysqli_real_escape_string($conn, $p['opcao_d']);
        $resposta = strtoupper($p['resposta']);

        if (!in_array($resposta, ['A','B','C','D'])) continue;

        $sql = "INSERT INTO perguntas (texto, opcao_a, opcao_b, opcao_c, opcao_d, resposta)
                VALUES ('$texto','$opcao_a','$opcao_b','$opcao_c','$opcao_d','$resposta')";

        if (mysqli_query($conn, $sql)) $salvos++;
    }

    echo json_encode(['sucesso' => true, 'salvos' => $salvos]);

} else {
    // Salvar pergunta manual (FormData)
    $texto   = trim($_POST['texto']    ?? '');
    $opcao_a = trim($_POST['opcao_a']  ?? '');
    $opcao_b = trim($_POST['opcao_b']  ?? '');
    $opcao_c = trim($_POST['opcao_c']  ?? '');
    $opcao_d = trim($_POST['opcao_d']  ?? '');
    $resposta = strtoupper(trim($_POST['resposta'] ?? ''));

    if (!$texto || !$opcao_a || !$opcao_b || !$opcao_c || !$opcao_d || !in_array($resposta, ['A','B','C','D'])) {
        echo json_encode(['erro' => 'Preencha todos os campos corretamente']);
        exit;
    }

    $texto   = mysqli_real_escape_string($conn, $texto);
    $opcao_a = mysqli_real_escape_string($conn, $opcao_a);
    $opcao_b = mysqli_real_escape_string($conn, $opcao_b);
    $opcao_c = mysqli_real_escape_string($conn, $opcao_c);
    $opcao_d = mysqli_real_escape_string($conn, $opcao_d);

    $sql = "INSERT INTO perguntas (texto, opcao_a, opcao_b, opcao_c, opcao_d, resposta)
            VALUES ('$texto','$opcao_a','$opcao_b','$opcao_c','$opcao_d','$resposta')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['erro' => mysqli_error($conn)]);
    }
}
?>