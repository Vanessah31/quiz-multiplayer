<?php
// =============================================
// TELA DO JOGADOR - jogador.php
// Tela do celular durante o jogo
// =============================================
session_start();
require_once "db/conexao.php";

$codigo = isset($_GET['sala']) ? strtoupper(trim($_GET['sala'])) : '';

// Verifica sessão do jogador
if (!isset($_SESSION['jogador_id'])) {
    header("Location: cadastro.php?sala=$codigo");
    exit;
}

$jogador_id   = $_SESSION['jogador_id'];
$jogador_nome = $_SESSION['jogador_nome'];

// Busca dados atuais do jogador
$sql     = "SELECT * FROM jogadores WHERE id = $jogador_id";
$res     = mysqli_query($conn, $sql);
$jogador = mysqli_fetch_assoc($res);

$niveis_xp = [1=>0, 2=>100, 3=>300, 4=>700, 5=>1500];
$nivel     = $jogador['nivel'];
$xp        = $jogador['xp'];
$xp_proximo = isset($niveis_xp[$nivel + 1]) ? $niveis_xp[$nivel + 1] : null;
$xp_base    = $niveis_xp[$nivel];
$xp_progresso = $xp_proximo
    ? round((($xp - $xp_base) / ($xp_proximo - $xp_base)) * 100)
    : 100;

$nomes_nivel = [
    1 => '⭐ Iniciante',
    2 => '🔥 Aprendiz',
    3 => '⚡ Guerreiro',
    4 => '💎 Expert',
    5 => '👑 Mestre'
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - <?= htmlspecialchars($jogador_nome) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="tela-jogador">

    <div class="container-jogador">

        <!-- Card de status do jogador -->
        <div class="card" style="padding: 16px 24px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                <div>
                    <p style="font-weight:700;font-size:1rem"><?= htmlspecialchars($jogador_nome) ?></p>
                    <span class="nivel-badge" id="nivel-badge"><?= $nomes_nivel[$nivel] ?></span>
                </div>
                <div style="text-align:right">
                    <p class="text-muted" style="font-size:0.8rem">Pontos</p>
                    <p style="font-size:1.4rem;font-weight:800;color:var(--neon-green)" id="pontos-display">
                        <?= $jogador['pontos_partida'] ?>
                    </p>
                </div>
            </div>
            <!-- Barra de XP -->
            <div style="display:flex;justify-content:space-between;margin-bottom:4px">
                <span class="text-muted" style="font-size:0.75rem">XP: <span id="xp-display"><?= $xp ?></span></span>
                <span class="text-muted" style="font-size:0.75rem">
                    <?= $xp_proximo ? "Próximo nível: $xp_proximo XP" : "Nível máximo!" ?>
                </span>
            </div>
            <div class="xp-bar-container">
                <div class="xp-bar" id="xp-bar" style="width:<?= $xp_progresso ?>%"></div>
            </div>
        </div>

        <!-- Área principal do jogo -->
        <div class="card" id="area-jogo">

            <!-- TELA: Aguardando o jogo começar -->
            <div id="tela-aguardando">
                <p style="font-size:2rem;text-align:center;margin-bottom:12px">⏳</p>
                <h2 style="text-align:center;margin-bottom:8px">Aguardando...</h2>
                <p class="text-muted text-center">O host vai iniciar o jogo em breve!</p>
                <div style="margin-top:20px;text-align:center">
                    <div class="chip">Sala: <strong style="color:var(--neon-blue);margin-left:4px"><?= $codigo ?></strong></div>
                </div>
            </div>

            <!-- TELA: Pergunta -->
            <div id="tela-pergunta" style="display:none">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
                    <span class="text-muted" style="font-size:0.85rem">Pergunta <span id="num-pergunta">1</span></span>
                    <span class="contador" id="contador-jogador">30</span>
                </div>

                <p class="pergunta-texto" id="texto-pergunta" style="margin-bottom:20px"></p>

                <div class="opcoes-grid" id="opcoes-container"></div>
            </div>

            <!-- TELA: Já respondeu, aguardando próxima -->
            <div id="tela-respondeu" style="display:none">
                <div id="feedback-resposta"></div>
                <div style="margin-top:20px;text-align:center">
                    <p class="text-muted">Aguardando próxima pergunta...</p>
                    <div style="margin-top:12px;font-size:2rem" id="emoji-espera">⏳</div>
                </div>
            </div>

            <!-- TELA: Jogo finalizado -->
            <div id="tela-fim" style="display:none">
                <p style="font-size:3rem;text-align:center">🏆</p>
                <h2 style="text-align:center;margin:12px 0">Jogo encerrado!</h2>
                <p class="text-muted text-center">Veja o placar final na tela do host!</p>
                <a href="ranking.php?sala=<?= $codigo ?>" style="text-decoration:none">
                    <button class="btn-entrar" style="margin-top:20px">Ver ranking final</button>
                </a>
            </div>

        </div>

        <!-- Card de subiu de nível (aparece quando sobe) -->
        <div class="card" id="card-nivel-up" style="display:none;border-color:var(--neon-purple);text-align:center">
            <p style="font-size:2.5rem">🎉</p>
            <h2 style="color:var(--neon-purple);margin:8px 0">Subiu de nível!</h2>
            <p id="novo-nivel-texto" style="font-size:1.2rem;font-weight:700"></p>
        </div>

    </div>

    <script>
        const sala       = '<?= $codigo ?>';
        const jogadorId  = <?= $jogador_id ?>;
        let perguntaAtual = null;
        let tempoInicio   = null;
        let contadorInt   = null;
        let statusAtual   = 'aguardando';

        // Mostra uma tela e esconde as outras
        function mostrarTela(nome) {
            ['aguardando','pergunta','respondeu','fim'].forEach(t => {
                document.getElementById('tela-' + t).style.display = 'none';
            });
            document.getElementById('tela-' + nome).style.display = 'block';
        }

        // Inicia contador regressivo do jogador
        function iniciarContador() {
            let tempo = 30;
            clearInterval(contadorInt);
            const el = document.getElementById('contador-jogador');
            el.classList.remove('urgente');

            contadorInt = setInterval(() => {
                tempo--;
                el.textContent = tempo;
                if (tempo <= 5) el.classList.add('urgente');
                if (tempo <= 0) clearInterval(contadorInt);
            }, 1000);
        }

        // Carrega a pergunta atual
        function carregarPergunta() {
            fetch(`api/pergunta.php?sala=${sala}&jogador=${jogadorId}`)
                .then(r => r.json())
                .then(data => {

                    if (data.status === 'aguardando') {
                        mostrarTela('aguardando');
                        return;
                    }

                    if (data.status === 'finalizada') {
                        mostrarTela('fim');
                        clearInterval(contadorInt);
                        return;
                    }

                    // Nova pergunta
                    if (!perguntaAtual || perguntaAtual !== data.pergunta_id) {
                        perguntaAtual = data.pergunta_id;
                        tempoInicio   = Date.now();

                        if (data.ja_respondeu) {
                            mostrarTela('respondeu');
                            return;
                        }

                        // Monta a tela da pergunta
                        document.getElementById('num-pergunta').textContent = data.numero;
                        document.getElementById('texto-pergunta').textContent = data.texto;

                        const opcoes = [
                            { letra: 'A', texto: data.opcao_a },
                            { letra: 'B', texto: data.opcao_b },
                            { letra: 'C', texto: data.opcao_c },
                            { letra: 'D', texto: data.opcao_d }
                        ];

                        let html = '';
                        opcoes.forEach(op => {
                            html += `
                                <button class="btn-opcao" onclick="responder('${op.letra}')">
                                    <div class="letra-opcao">${op.letra}</div>
                                    <div>${op.texto}</div>
                                </button>`;
                        });

                        document.getElementById('opcoes-container').innerHTML = html;
                        mostrarTela('pergunta');
                        iniciarContador();

                    } else if (data.ja_respondeu) {
                        mostrarTela('respondeu');
                    }
                })
                .catch(() => {});
        }

        // Envia a resposta do jogador
        function responder(letra) {
            // Desabilita todos os botões
            document.querySelectorAll('.btn-opcao').forEach(btn => {
                btn.disabled = true;
                btn.classList.add('selecionado');
            });

            // Marca o botão clicado
            event.currentTarget.classList.remove('selecionado');
            event.currentTarget.style.borderColor = 'var(--neon-blue)';

            const tempo_ms = Date.now() - tempoInicio;
            clearInterval(contadorInt);

            const form = new FormData();
            form.append('jogador_id',  jogadorId);
            form.append('pergunta_id', perguntaAtual);
            form.append('resposta',    letra);
            form.append('tempo_ms',    tempo_ms);

            fetch('api/responder.php', { method: 'POST', body: form })
                .then(r => r.json())
                .then(data => {
                    // Feedback visual
                    const feedback = document.getElementById('feedback-resposta');

                    if (data.correta) {
                        feedback.innerHTML = `
                            <div class="msg-acerto certo">
                                ✅ Acertou! +${data.pontos} pts
                            </div>
                            <p style="text-align:center;margin-top:12px;color:var(--neon-green)">
                                +${data.xp_ganho} XP ganhos!
                            </p>`;
                    } else {
                        feedback.innerHTML = `
                            <div class="msg-acerto errado">
                                ❌ Errou! A resposta era <strong>${data.resp_certa}</strong>
                            </div>
                            <p style="text-align:center;margin-top:12px;color:var(--text-muted)">
                                +${data.xp_ganho} XP por participar
                            </p>`;
                    }

                    // Atualiza XP e pontos na tela
                    document.getElementById('xp-display').textContent   = data.xp_total;
                    document.getElementById('xp-bar').style.width        = data.xp_progresso + '%';
                    document.getElementById('nivel-badge').textContent   = data.nome_nivel;
                    document.getElementById('pontos-display').textContent = parseInt(document.getElementById('pontos-display').textContent) + data.pontos;

                    // Mostra card de subiu de nível
                    if (data.subiu_nivel) {
                        const cardNivel = document.getElementById('card-nivel-up');
                        document.getElementById('novo-nivel-texto').textContent = data.nome_nivel;
                        cardNivel.style.display = 'block';
                        setTimeout(() => cardNivel.style.display = 'none', 4000);
                    }

                    mostrarTela('respondeu');
                });
        }

        // Polling — verifica o status a cada 2 segundos
        carregarPergunta();
        setInterval(carregarPergunta, 2000);
    </script>

</body>
</html>