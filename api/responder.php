<?php
// =============================================
// API RESPONDER - api/responder.php
// Salva a resposta do jogador e calcula pontos
// =============================================
require_once "../db/conexao.php";

header('Content-Type: application/json');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['erro' => 'Método inválido']);
    exit;
}

$jogador_id  = intval($_POST['jogador_id']  ?? 0);
$pergunta_id = intval($_POST['pergunta_id'] ?? 0);
$resposta    = strtoupper(trim($_POST['resposta'] ?? ''));
$tempo_ms    = intval($_POST['tempo_ms']    ?? 0);

// Validações básicas
if (!$jogador_id || !$pergunta_id || !in_array($resposta, ['A','B','C','D'])) {
    echo json_encode(['erro' => 'Dados inválidos']);
    exit;
}

// Verifica se já respondeu essa pergunta
$sql = "SELECT id FROM respostas
        WHERE jogador_id = $jogador_id AND pergunta_id = $pergunta_id";
$res = mysqli_query($conn, $sql);
if (mysqli_num_rows($res) > 0) {
    echo json_encode(['erro' => 'Você já respondeu essa pergunta!']);
    exit;
}

// Busca a resposta correta
$sql      = "SELECT resposta FROM perguntas WHERE id = $pergunta_id";
$res      = mysqli_query($conn, $sql);
$pergunta = mysqli_fetch_assoc($res);

if (!$pergunta) {
    echo json_encode(['erro' => 'Pergunta não encontrada']);
    exit;
}

$correta    = ($resposta === $pergunta['resposta']) ? 1 : 0;
$resp_certa = $pergunta['resposta'];

// -----------------------------------------------
// CÁLCULO DE PONTOS
// Acertou: pontos baseados na velocidade
// Errou: 0 pontos
// -----------------------------------------------
$pontos   = 0;
$xp_ganho = 0;

if ($correta) {
    // Quanto mais rápido, mais pontos (máx 1000, mín 200)
    $tempo_segundos = $tempo_ms / 1000;
    $pontos   = max(200, round(1000 - ($tempo_segundos * 26.67)));
    $xp_ganho = 50;
} else {
    // Erra mas ganha 10 XP por participar
    $xp_ganho = 10;
}

// Salva a resposta no banco
$sql = "INSERT INTO respostas (jogador_id, pergunta_id, resposta, correta, tempo_ms)
        VALUES ($jogador_id, $pergunta_id, '$resposta', $correta, $tempo_ms)";
mysqli_query($conn, $sql);

// Atualiza pontos e XP do jogador
$sql = "UPDATE jogadores
        SET pontos_partida = pontos_partida + $pontos,
            xp = xp + $xp_ganho
        WHERE id = $jogador_id";
mysqli_query($conn, $sql);

// -----------------------------------------------
// SISTEMA DE NÍVEIS
// -----------------------------------------------
$niveis_xp = [1=>0, 2=>100, 3=>300, 4=>700, 5=>1500];

$sql     = "SELECT xp, nivel FROM jogadores WHERE id = $jogador_id";
$res     = mysqli_query($conn, $sql);
$jogador = mysqli_fetch_assoc($res);

$xp_atual    = $jogador['xp'];
$nivel_atual = $jogador['nivel'];
$subiu_nivel = false;
$novo_nivel  = $nivel_atual;

foreach ($niveis_xp as $nivel => $xp_necessario) {
    if ($xp_atual >= $xp_necessario && $nivel > $nivel_atual) {
        $novo_nivel  = $nivel;
        $subiu_nivel = true;
    }
}

if ($subiu_nivel) {
    $sql = "UPDATE jogadores SET nivel = $novo_nivel WHERE id = $jogador_id";
    mysqli_query($conn, $sql);
}

$xp_proximo   = isset($niveis_xp[$novo_nivel + 1]) ? $niveis_xp[$novo_nivel + 1] : null;
$xp_base      = $niveis_xp[$novo_nivel];
$xp_progresso = $xp_proximo
    ? round((($xp_atual - $xp_base) / ($xp_proximo - $xp_base)) * 100)
    : 100;

$nomes_nivel = [
    1 => '⭐ Iniciante',
    2 => '🔥 Aprendiz',
    3 => '⚡ Guerreiro',
    4 => '💎 Expert',
    5 => '👑 Mestre'
];

echo json_encode([
    'correta'      => $correta,
    'resp_certa'   => $resp_certa,
    'pontos'       => $pontos,
    'xp_ganho'     => $xp_ganho,
    'xp_total'     => $xp_atual,
    'nivel'        => $novo_nivel,
    'nome_nivel'   => $nomes_nivel[$novo_nivel],
    'subiu_nivel'  => $subiu_nivel,
    'xp_progresso' => $xp_progresso
]);
?>