<?php
session_start();
require_once "db/conexao.php";

function gerarCodigo() {
    $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $codigo = '';
    for ($i = 0; $i < 6; $i++) {
        $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
    }
    return $codigo;
}

$codigo = gerarCodigo();
$sql    = "INSERT INTO salas (codigo, status) VALUES ('$codigo', 'aguardando')";
mysqli_query($conn, $sql);
$sala_id = mysqli_insert_id($conn);

$_SESSION['sala_id']     = $sala_id;
$_SESSION['sala_codigo'] = $codigo;

$ip  = '192.168.1.16';
$url = "http://$ip/quiz/cadastro.php?sala=$codigo";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Multiplayer</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="tela-host">
<div class="container-host">

    <div class="header-host">
        <h1>🎮 Quiz Multiplayer</h1>
        <p class="subtitulo">Escaneie o QR Code para entrar no jogo!</p>
    </div>

    <div class="qr-box">
        <img src="<?= $qr_url ?>" alt="QR Code" class="qr-img">
        <div class="codigo-sala">
            <span class="label-codigo">Código da sala</span>
            <span class="codigo"><?= $codigo ?></span>
        </div>
        <p class="url-sala">📱 Ou acesse: <strong><?= $url ?></strong></p>
    </div>

    <div class="aguardando-box">
        <h2>Jogadores na sala: <span id="total-jogadores">0</span></h2>
        <div id="lista-jogadores" class="lista-jogadores">
            <p class="msg-espera">Aguardando jogadores entrarem...</p>
        </div>
    </div>

    <form action="host.php" method="GET">
        <input type="hidden" name="sala" value="<?= $codigo ?>">
        <button type="submit" class="btn-iniciar">🚀 Iniciar Jogo</button>
    </form>

</div>
<script>
    function atualizarJogadores() {
        fetch('api/status.php?sala=<?= $codigo ?>&tipo=jogadores')
            .then(r => r.json())
            .then(data => {
                document.getElementById('total-jogadores').textContent = data.total;
                if (data.total > 0) {
                    let html = '';
                    data.jogadores.forEach(j => {
                        html += `<div class="jogador-chip">👤 ${j.nome}</div>`;
                    });
                    document.getElementById('lista-jogadores').innerHTML = html;
                }
            }).catch(() => {});
    }
    atualizarJogadores();
    setInterval(atualizarJogadores, 2000);
</script>
</body>
</html>