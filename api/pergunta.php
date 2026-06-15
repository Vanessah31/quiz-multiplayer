<?php
// =============================================
// API PERGUNTA - api/pergunta.php
// Retorna a pergunta atual da sala
// Usada pelo jogador.php e host.php via fetch()
// =============================================
require_once "../db/conexao.php";

header('Content-Type: application/json');

$codigo = isset($_GET['sala']) ? strtoupper(trim($_GET['sala'])) : '';

if (!$codigo) {
    echo json_encode(['erro' => 'Código da sala não informado']);
    exit;
}

// Busca a sala e a pergunta atual
$sql  = "SELECT * FROM salas WHERE codigo = '$codigo'";
$res  = mysqli_query($conn, $sql);
$sala = mysqli_fetch_assoc($res);

if (!$sala) {
    echo json_encode(['erro' => 'Sala não encontrada']);
    exit;
}

// Se o jogo não começou ainda
if ($sala['status'] === 'aguardando') {
    echo json_encode(['status' => 'aguardando']);
    exit;
}

// Se o jogo já finalizou
if ($sala['status'] === 'finalizada') {
    echo json_encode(['status' => 'finalizada']);
    exit;
}

// Busca a pergunta atual pelo número da rodada
$numero   = intval($sala['pergunta_atual']);
$sql      = "SELECT * FROM perguntas LIMIT 1 OFFSET $numero";
$res      = mysqli_query($conn, $sql);
$pergunta = mysqli_fetch_assoc($res);

if (!$pergunta) {
    // Não há mais perguntas — finaliza o jogo
    $sql = "UPDATE salas SET status = 'finalizada' WHERE codigo = '$codigo'";
    mysqli_query($conn, $sql);
    echo json_encode(['status' => 'finalizada']);
    exit;
}

// Verifica se o jogador já respondeu essa pergunta
$jogador_id = isset($_GET['jogador']) ? intval($_GET['jogador']) : 0;
$ja_respondeu = false;

if ($jogador_id > 0) {
    $sql = "SELECT id FROM respostas
            WHERE jogador_id = $jogador_id
            AND pergunta_id = {$pergunta['id']}";
    $res = mysqli_query($conn, $sql);
    $ja_respondeu = mysqli_num_rows($res) > 0;
}

// Conta total de perguntas
$total_sql       = "SELECT COUNT(*) as total FROM perguntas";
$total_res       = mysqli_query($conn, $total_sql);
$total_row       = mysqli_fetch_assoc($total_res);
$total_perguntas = intval($total_row['total']);

// Retorna a pergunta (SEM a resposta correta!)
echo json_encode([
    'status'       => 'jogando',
    'pergunta_id'  => $pergunta['id'],
    'numero'       => $numero + 1,
    'total'        => $total_perguntas,
    'texto'        => $pergunta['texto'],
    'opcao_a'      => $pergunta['opcao_a'],
    'opcao_b'      => $pergunta['opcao_b'],
    'opcao_c'      => $pergunta['opcao_c'],
    'opcao_d'      => $pergunta['opcao_d'],
    'ja_respondeu' => $ja_respondeu
]);
?>