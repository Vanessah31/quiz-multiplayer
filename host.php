<?php
// =============================================
// TELA DO HOST - host.php
// Controla o jogo e mostra ranking ao vivo
// =============================================
session_start();
require_once "db/conexao.php";

$codigo = isset($_GET['sala']) ? strtoupper(trim($_GET['sala'])) : '';

// Busca a sala
$sql  = "SELECT * FROM salas WHERE codigo = '$codigo'";
$res  = mysqli_query($conn, $sql);
$sala = mysqli_fetch_assoc($res);

if (!$sala) {
    die("<h2 style='color:white;text-align:center;padding:40px'>Sala não encontrada!</h2>");
}

// Inicia o jogo ao abrir o host.php
if ($sala['status'] === 'aguardando') {
    $sql = "UPDATE salas SET status = 'jogando', pergunta_atual = 0 WHERE codigo = '$codigo'";
    mysqli_query($conn, $sql);
}

// Conta total de perguntas
$total_res      = mysqli_query($conn, "SELECT COUNT(*) as total FROM perguntas");
$total_row      = mysqli_fetch_assoc($total_res);
$total_perguntas = intval($total_row['total']);

// Busca pergunta atual
$numero   = intval($sala['pergunta_atual']);
$sql      = "SELECT * FROM perguntas LIMIT 1 OFFSET $numero";
$res      = mysqli_query($conn, $sql);
$pergunta = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host - Quiz Multiplayer</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .host-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }
        .pergunta-host {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: var(--shadow-neon);
        }
        .ranking-host {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: var(--shadow-neon);
        }
        .header-jogo {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .opcao-host {
            background: var(--bg-card2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }
        .btn-proxima {
            background: linear-gradient(135deg, var(--neon-green), #00b8a9);
            color: var(--bg-dark);
            border: none;
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 800;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 16px;
        }
        .btn-proxima:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 30px rgba(0,255,136,0.3);
        }
        .btn-encerrar {
            background: linear-gradient(135deg, var(--neon-pink), #c90070);
            color: white;
            border: none;
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 800;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 10px;
        }
        .contador-host {
            font-size: 4rem;
            font-weight: 800;
            text-align: center;
            color: var(--neon-blue);
            font-family: 'Courier New', monospace;
            text-shadow: 0 0 20px rgba(0,212,255,0.5);
            margin: 8px 0;
        }
        .section-title {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 16px;
        }
        @media (max-width: 768px) {
            .host-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="tela-host">

    <!-- Header do jogo -->
    <div class="header-jogo">
        <div style="display:flex;align-items:center;gap:16px">
            <h2 style="font-size:1.2rem;background:linear-gradient(135deg,var(--neon-blue),var(--neon-purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
                🎮 Quiz Multiplayer
            </h2>
            <span class="chip">Sala: <strong style="color:var(--neon-blue);margin-left:4px"><?= $codigo ?></strong></span>
        </div>
        <div style="display:flex;align-items:center;gap:16px">
            <span class="chip">👥 <span id="total-jogadores">0</span> jogadores</span>
            <span class="chip">❓ Pergunta <span id="num-pergunta"><?= $numero + 1 ?></span> de <?= $total_perguntas ?></span>
        </div>
    </div>

    <!-- Grid principal -->
    <div class="host-grid" style="position:relative;z-index:1">

        <!-- Coluna esquerda: pergunta atual -->
        <div class="pergunta-host">
            <p class="section-title">Pergunta atual</p>

            <!-- Contador regressivo -->
            <div class="contador-host" id="contador">30</div>

            <!-- Barra de progresso do tempo -->
            <div class="progress-bar-container" style="margin-bottom:20px">
                <div class="progress-bar" id="barra-tempo" style="width:100%"></div>
            </div>

            <!-- Texto da pergunta -->
            <div id="texto-pergunta" class="pergunta-texto" style="margin-bottom:20px;font-size:1.3rem">
                <?= $pergunta ? htmlspecialchars($pergunta['texto']) : 'Carregando...' ?>
            </div>

            <!-- Opções -->
            <div id="opcoes-host">
                <?php if ($pergunta): ?>
                <div class="opcao-host"><span class="letra-opcao">A</span><?= htmlspecialchars($pergunta['opcao_a']) ?></div>
                <div class="opcao-host"><span class="letra-opcao">B</span><?= htmlspecialchars($pergunta['opcao_b']) ?></div>
                <div class="opcao-host"><span class="letra-opcao">C</span><?= htmlspecialchars($pergunta['opcao_c']) ?></div>
                <div class="opcao-host"><span class="letra-opcao">D</span><?= htmlspecialchars($pergunta['opcao_d']) ?></div>
                <?php endif; ?>
            </div>

            <!-- Botões de controle -->
            <button class="btn-proxima" onclick="proximaPergunta()">
                ⏭ Próxima Pergunta
            </button>
            <button class="btn-encerrar" onclick="encerrarJogo()">
                🏁 Encerrar Jogo
            </button>
        </div>

        <!-- Coluna direita: ranking ao vivo -->
        <div class="ranking-host">
            <p class="section-title">🏆 Ranking ao vivo</p>
            <div id="ranking-lista" class="ranking-list">
                <p class="text-muted text-center">Aguardando respostas...</p>
            </div>
        </div>

    </div>

    <script>
        const sala    = '<?= $codigo ?>';
        const total   = <?= $total_perguntas ?>;
        let tempo     = 30;
        let intervalo = null;

        // Inicia o contador regressivo
        function iniciarContador() {
            tempo = 30;
            clearInterval(intervalo);
            document.getElementById('contador').classList.remove('urgente');

            intervalo = setInterval(() => {
                tempo--;
                document.getElementById('contador').textContent = tempo;
                document.getElementById('barra-tempo').style.width = (tempo / 30 * 100) + '%';

                if (tempo <= 5) {
                    document.getElementById('contador').classList.add('urgente');
                }
                if (tempo <= 0) {
                    clearInterval(intervalo);
                }
            }, 1000);
        }

        // Vai para a próxima pergunta
        function proximaPergunta() {
            fetch('api/avancar.php?sala=' + sala)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'finalizada') {
                        window.location.href = 'ranking.php?sala=' + sala;
                        return;
                    }
                    // Atualiza a pergunta na tela
                    document.getElementById('num-pergunta').textContent = data.numero;
                    document.getElementById('texto-pergunta').textContent = data.texto;
                    document.getElementById('opcoes-host').innerHTML = `
                        <div class="opcao-host"><span class="letra-opcao">A</span>${data.opcao_a}</div>
                        <div class="opcao-host"><span class="letra-opcao">B</span>${data.opcao_b}</div>
                        <div class="opcao-host"><span class="letra-opcao">C</span>${data.opcao_c}</div>
                        <div class="opcao-host"><span class="letra-opcao">D</span>${data.opcao_d}</div>
                    `;
                    iniciarContador();
                });
        }

        // Encerra o jogo e vai para o ranking
        function encerrarJogo() {
            if (confirm('Encerrar o jogo agora?')) {
                fetch('api/avancar.php?sala=' + sala + '&encerrar=1')
                    .then(() => window.location.href = 'ranking.php?sala=' + sala);
            }
        }

        // Atualiza ranking ao vivo
        function atualizarRanking() {
            fetch('api/status.php?sala=' + sala + '&tipo=ranking')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('total-jogadores').textContent = data.ranking.length;

                    if (data.ranking.length === 0) return;

                    const medalhas = ['🥇','🥈','🥉'];
                    const classes  = ['gold','silver','bronze'];
                    let html = '';

                    data.ranking.forEach((j, i) => {
                        const medalha = medalhas[i] || `${i+1}º`;
                        const cls     = classes[i] || '';
                        html += `
                            <div class="ranking-item">
                                <span class="ranking-pos ${cls}">${medalha}</span>
                                <span class="ranking-nome">${j.nome}</span>
                                <span class="nivel-badge">${j.nivel}⭐</span>
                                <span class="ranking-pontos">${j.pontos} pts</span>
                            </div>`;
                    });
                    document.getElementById('ranking-lista').innerHTML = html;
                });
        }

        // Inicia tudo
        iniciarContador();
        atualizarRanking();
        setInterval(atualizarRanking, 2000);
    </script>

</body>
</html><?php
session_start();
require_once "db/conexao.php";

$codigo = isset($_GET['sala']) ? strtoupper(trim($_GET['sala'])) : '';

$sql  = "SELECT * FROM salas WHERE codigo = '$codigo'";
$res  = mysqli_query($conn, $sql);
$sala = mysqli_fetch_assoc($res);

if (!$sala) die("<h2 style='color:white;text-align:center;padding:40px'>Sala não encontrada!</h2>");

if ($sala['status'] === 'aguardando') {
    mysqli_query($conn, "UPDATE salas SET status='jogando', pergunta_atual=0 WHERE codigo='$codigo'");
}

$total_res       = mysqli_query($conn, "SELECT COUNT(*) as total FROM perguntas");
$total_row       = mysqli_fetch_assoc($total_res);
$total_perguntas = intval($total_row['total']);

$numero   = intval($sala['pergunta_atual']);
$sql      = "SELECT * FROM perguntas LIMIT 1 OFFSET $numero";
$res      = mysqli_query($conn, $sql);
$pergunta = mysqli_fetch_assoc($res);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host - Quiz Multiplayer</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .host-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px;
        }
        .pergunta-host, .ranking-host {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            box-shadow: var(--shadow-neon);
            position: relative;
            overflow: hidden;
        }
        .pergunta-host::before, .ranking-host::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-blue), var(--neon-purple), transparent);
        }
        .header-jogo {
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 1;
        }
        .opcao-host {
            background: var(--bg-card2);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 14px 18px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        .opcao-host.correta {
            border-color: var(--neon-green);
            background: rgba(0,255,136,0.1);
            color: var(--neon-green);
        }
        .btn-proxima {
            background: linear-gradient(135deg, var(--neon-green), #00b8a9);
            color: var(--bg-dark);
            border: none;
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 800;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 16px;
            font-family: 'Orbitron', monospace;
        }
        .btn-proxima:hover { transform: translateY(-2px); box-shadow: 0 6px 30px rgba(0,255,136,0.3); }
        .btn-encerrar {
            background: linear-gradient(135deg, var(--neon-pink), #c90070);
            color: white;
            border: none;
            padding: 14px 32px;
            font-size: 1rem;
            font-weight: 800;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 10px;
            font-family: 'Orbitron', monospace;
        }
        .contador-host {
            font-family: 'Orbitron', monospace;
            font-size: 4.5rem;
            font-weight: 900;
            text-align: center;
            color: var(--neon-blue);
            text-shadow: 0 0 30px rgba(0,212,255,0.8);
            margin: 8px 0;
            transition: color 0.3s;
        }
        .contador-host.urgente {
            color: var(--neon-pink);
            text-shadow: 0 0 30px rgba(255,0,110,0.8);
            animation: pulsar 0.5s infinite;
        }
        .section-title {
            font-family: 'Orbitron', monospace;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 16px;
        }
        .auto-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,255,136,0.15);
            border: 1px solid rgba(0,255,136,0.3);
            color: var(--neon-green);
            padding: 4px 14px;
            border-radius: 50px;
            font-size: 0.78rem;
            font-weight: 700;
        }
        @media (max-width: 768px) { .host-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="tela-host">

<div class="header-jogo">
    <div style="display:flex;align-items:center;gap:16px">
        <h2 style="font-family:'Orbitron',monospace;font-size:1.1rem;background:linear-gradient(135deg,var(--neon-blue),var(--neon-purple));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
            🎮 Quiz Multiplayer
        </h2>
        <span class="chip">Sala: <strong style="color:var(--neon-blue);margin-left:4px"><?= $codigo ?></strong></span>
    </div>
    <div style="display:flex;align-items:center;gap:12px">
        <span class="chip">👥 <span id="total-jogadores">0</span></span>
        <span class="chip">❓ <span id="num-pergunta"><?= $numero + 1 ?></span>/<?= $total_perguntas ?></span>
        <span class="auto-badge">⚡ Auto</span>
    </div>
</div>

<div class="host-grid" style="position:relative;z-index:1">

    <!-- Pergunta atual -->
    <div class="pergunta-host">
        <p class="section-title">Pergunta atual</p>

        <div class="contador-host" id="contador">30</div>

        <div class="progress-bar-container" style="margin-bottom:20px">
            <div class="progress-bar" id="barra-tempo" style="width:100%"></div>
        </div>

        <div id="texto-pergunta" class="pergunta-texto" style="margin-bottom:20px;font-size:1.3rem;color:#fff">
            <?= $pergunta ? htmlspecialchars($pergunta['texto']) : 'Carregando...' ?>
        </div>

        <div id="opcoes-host">
            <?php if ($pergunta): ?>
            <div class="opcao-host"><span class="letra-opcao">A</span><?= htmlspecialchars($pergunta['opcao_a']) ?></div>
            <div class="opcao-host"><span class="letra-opcao">B</span><?= htmlspecialchars($pergunta['opcao_b']) ?></div>
            <div class="opcao-host"><span class="letra-opcao">C</span><?= htmlspecialchars($pergunta['opcao_c']) ?></div>
            <div class="opcao-host"><span class="letra-opcao">D</span><?= htmlspecialchars($pergunta['opcao_d']) ?></div>
            <?php endif; ?>
        </div>

        <div id="resp-correta" style="display:none;margin-top:12px" class="msg-acerto certo"></div>

        <button class="btn-proxima" onclick="proximaPergunta()">⏭ Próxima Pergunta</button>
        <button class="btn-encerrar" onclick="encerrarJogo()">🏁 Encerrar Jogo</button>
    </div>

    <!-- Ranking ao vivo -->
    <div class="ranking-host">
        <p class="section-title">🏆 Ranking ao vivo</p>
        <div id="ranking-lista" class="ranking-list">
            <p class="text-muted text-center">Aguardando respostas...</p>
        </div>
    </div>

</div>

<script>
    const sala  = '<?= $codigo ?>';
    const total = <?= $total_perguntas ?>;
    const TEMPO = 30; // segundos por pergunta
    let tempo     = TEMPO;
    let intervalo = null;
    let avancando = false;
    let respostaCorreta = '';

    function iniciarContador(resposta) {
        tempo = TEMPO;
        avancando = false;
        respostaCorreta = resposta || '';
        clearInterval(intervalo);

        const el    = document.getElementById('contador');
        const barra = document.getElementById('barra-tempo');
        const divResp = document.getElementById('resp-correta');
        el.classList.remove('urgente');
        divResp.style.display = 'none';

        intervalo = setInterval(() => {
            tempo--;
            el.textContent    = tempo;
            barra.style.width = (tempo / TEMPO * 100) + '%';

            if (tempo <= 5)  el.classList.add('urgente');
            if (tempo <= 0) {
                clearInterval(intervalo);
                // Mostra resposta correta por 2s antes de avançar
                if (respostaCorreta) {
                    divResp.textContent    = '✅ Resposta correta: ' + respostaCorreta;
                    divResp.style.display  = 'block';
                }
                setTimeout(() => proximaPergunta(), 2000);
            }
        }, 1000);
    }

    function proximaPergunta() {
        if (avancando) return;
        avancando = true;
        clearInterval(intervalo);

        fetch('api/avancar.php?sala=' + sala)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'finalizada') {
                    window.location.href = 'ranking.php?sala=' + sala;
                    return;
                }
                document.getElementById('num-pergunta').textContent  = data.numero;
                document.getElementById('texto-pergunta').textContent = data.texto;
                document.getElementById('opcoes-host').innerHTML = `
                    <div class="opcao-host"><span class="letra-opcao">A</span>${data.opcao_a}</div>
                    <div class="opcao-host"><span class="letra-opcao">B</span>${data.opcao_b}</div>
                    <div class="opcao-host"><span class="letra-opcao">C</span>${data.opcao_c}</div>
                    <div class="opcao-host"><span class="letra-opcao">D</span>${data.opcao_d}</div>
                `;
                iniciarContador(data.resposta);
            });
    }

    function encerrarJogo() {
        if (confirm('Encerrar o jogo agora?')) {
            clearInterval(intervalo);
            fetch('api/avancar.php?sala=' + sala + '&encerrar=1')
                .then(() => window.location.href = 'ranking.php?sala=' + sala);
        }
    }

    function atualizarRanking() {
        fetch('api/status.php?sala=' + sala + '&tipo=ranking')
            .then(r => r.json())
            .then(data => {
                document.getElementById('total-jogadores').textContent = data.ranking.length;
                if (!data.ranking.length) return;
                const medalhas = ['🥇','🥈','🥉'];
                const classes  = ['gold','silver','bronze'];
                let html = '';
                data.ranking.forEach((j, i) => {
                    html += `
                        <div class="ranking-item">
                            <span class="ranking-pos ${classes[i]||''}">${medalhas[i]||`${i+1}º`}</span>
                            <span class="ranking-nome">${j.nome}</span>
                            <span class="nivel-badge">${j.nivel}⭐</span>
                            <span class="ranking-pontos">${j.pontos} pts</span>
                        </div>`;
                });
                document.getElementById('ranking-lista').innerHTML = html;
            });
    }

    // Busca a resposta correta da primeira pergunta para o timer
    fetch('api/avancar.php?sala=' + sala + '&peek=1')
        .then(r => r.json())
        .then(data => iniciarContador(data.resposta || ''))
        .catch(() => iniciarContador(''));

    atualizarRanking();
    setInterval(atualizarRanking, 2000);
</script>
</body>
</html>