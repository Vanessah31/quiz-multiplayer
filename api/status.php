<?php
// =============================================
// API STATUS - api/status.php
// Retorna informações da sala em tempo real
// Usada pelo index.php e host.php via fetch()
// =============================================
require_once "../db/conexao.php";

header('Content-Type: application/json');

$codigo = isset($_GET['sala']) ? strtoupper(trim($_GET['sala'])) : '';
$tipo   = isset($_GET['tipo']) ? $_GET['tipo'] : 'jogadores';

if (!$codigo) {
    echo json_encode(['erro' => 'Código da sala não informado']);
    exit;
}

// Busca a sala
$sql  = "SELECT * FROM salas WHERE codigo = '$codigo'";
$res  = mysqli_query($conn, $sql);
$sala = mysqli_fetch_assoc($res);

if (!$sala) {
    echo json_encode(['erro' => 'Sala não encontrada']);
    exit;
}

// -----------------------------------------------
// TIPO: jogadores — lista quem está na sala
// Usado no index.php para mostrar quem entrou
// -----------------------------------------------
if ($tipo === 'jogadores') {
    $sql      = "SELECT id, nome, nivel, xp FROM jogadores
                 WHERE sala_id = {$sala['id']}
                 ORDER BY entrou_em ASC";
    $res      = mysqli_query($conn, $sql);
    $jogadores = [];

    while ($j = mysqli_fetch_assoc($res)) {
        $jogadores[] = [
            'id'    => $j['id'],
            'nome'  => $j['nome'],
            'nivel' => $j['nivel'],
            'xp'    => $j['xp']
        ];
    }

    echo json_encode([
        'total'     => count($jogadores),
        'jogadores' => $jogadores,
        'status'    => $sala['status']
    ]);
}

// -----------------------------------------------
// TIPO: ranking — placar ao vivo durante o jogo
// Usado no host.php para mostrar pontuação
// -----------------------------------------------
elseif ($tipo === 'ranking') {
    $sql = "SELECT nome, pontos_partida, nivel, xp
            FROM jogadores
            WHERE sala_id = {$sala['id']}
            ORDER BY pontos_partida DESC
            LIMIT 10";
    $res     = mysqli_query($conn, $sql);
    $ranking = [];

    while ($j = mysqli_fetch_assoc($res)) {
        $ranking[] = [
            'nome'          => $j['nome'],
            'pontos'        => $j['pontos_partida'],
            'nivel'         => $j['nivel'],
            'xp'            => $j['xp']
        ];
    }

    echo json_encode([
        'ranking'          => $ranking,
        'status'           => $sala['status'],
        'pergunta_atual'   => $sala['pergunta_atual']
    ]);
}

// -----------------------------------------------
// TIPO: sala — status geral para o jogador
// Usado no jogador.php para saber se começou
// -----------------------------------------------
elseif ($tipo === 'sala') {
    echo json_encode([
        'status'         => $sala['status'],
        'pergunta_atual' => $sala['pergunta_atual']
    ]);
}
?>
