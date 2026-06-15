<?php
// =============================================
// RANKING FINAL - ranking.php
// Placar final com pódio animado
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

// Encerra a sala se ainda não estiver finalizada
if ($sala['status'] !== 'finalizada') {
    mysqli_query($conn, "UPDATE salas SET status = 'finalizada' WHERE codigo = '$codigo'");
}

// Busca todos os jogadores ordenados por pontuação
$sql      = "SELECT * FROM jogadores WHERE sala_id = {$sala['id']} ORDER BY pontos_partida DESC";
$res      = mysqli_query($conn, $sql);
$jogadores = [];
while ($j = mysqli_fetch_assoc($res)) {
    $jogadores[] = $j;
}

// Busca estatísticas gerais
$total_jogadores = count($jogadores);
$sql = "SELECT COUNT(*) as total FROM respostas r
        INNER JOIN jogadores j ON r.jogador_id = j.id
        WHERE j.sala_id = {$sala['id']} AND r.correta = 1";
$res        = mysqli_query($conn, $sql);
$row        = mysqli_fetch_assoc($res);
$total_acertos = intval($row['total']);

$nomes_nivel = [
    1 => '⭐ Iniciante',
    2 => '🔥 Aprendiz',
    3 => '⚡ Guerreiro',
    4 => '💎 Expert',
    5 => '👑 Mestre'
];

// Top 3 para o pódio
$primeiro = $jogadores[0] ?? null;
$segundo  = $jogadores[1] ?? null;
$terceiro = $jogadores[2] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking Final - Quiz</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .container-ranking {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 24px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
        }
        .stat-valor {
            font-size: 2rem;
            font-weight: 800;
            color: var(--neon-blue);
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        .podio-container {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-neon);
        }
        .lista-completa {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
        }
        .btn-novo-jogo {
            background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple));
            color: white;
            border: none;
            padding: 16px 48px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-decoration: none;
            display: inline-block;
            margin-top: 8px;
        }
        .btn-novo-jogo:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0,212,255,0.4);
        }
        .confetti {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        .confete {
            position: absolute;
            width: 10px;
            height: 10px;
            opacity: 0;
            animation: cair linear forwards;
        }
        @keyframes cair {
            0%   { opacity: 1; transform: translateY(-20px) rotate(0deg); }
            100% { opacity: 0; transform: translateY(100vh) rotate(720deg); }
        }
        .ranking-item-final {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 14px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            background: var(--bg-card2);
            border: 1px solid var(--border);
            animation: slideIn 0.4s ease both;
        }
        .secao-titulo {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 16px;
        }
    </style>
</head>
<body class="tela-host">

    <!-- Confetes animados -->
    <div class="confetti" id="confetti"></div>

    <div class="container-ranking">

        <!-- Cabeçalho -->
        <div class="header-host" style="margin-bottom:32px">
            <h1 style="font-size:2.5rem">🏆 Resultado Final</h1>
            <p class="subtitulo">Sala <?= $codigo ?> — <?= $total_jogadores ?> jogadores</p>
        </div>

        <!-- Estatísticas gerais -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-valor"><?= $total_jogadores ?></div>
                <div class="stat-label">Jogadores</div>
            </div>
            <div class="stat-card">
                <div class="stat-valor" style="color:var(--neon-green)"><?= $total_acertos ?></div>
                <div class="stat-label">Acertos totais</div>
            </div>
            <div class="stat-card">
                <div class="stat-valor" style="color:var(--neon-purple)">
                    <?= $primeiro ? $primeiro['pontos_partida'] : 0 ?>
                </div>
                <div class="stat-label">Maior pontuação</div>
            </div>
        </div>

        <!-- Pódio top 3 -->
        <?php if ($total_jogadores >= 1): ?>
        <div class="podio-container">
            <p class="secao-titulo" style="text-align:center">🥇 Pódio</p>
            <div class="podio">

                <!-- 2º lugar -->
                <?php if ($segundo): ?>
                <div class="podio-item segundo" style="animation: fadeInUp 0.6s ease 0.2s both">
                    <div class="podio-avatar">🥈</div>
                    <p style="font-weight:700;font-size:0.9rem;text-align:center;max-width:90px">
                        <?= htmlspecialchars($segundo['nome']) ?>
                    </p>
                    <p style="color:var(--text-muted);font-size:0.8rem"><?= $segundo['pontos_partida'] ?> pts</p>
                    <div class="podio-base">2</div>
                </div>
                <?php endif; ?>

                <!-- 1º lugar -->
                <?php if ($primeiro): ?>
                <div class="podio-item primeiro" style="animation: fadeInUp 0.6s ease both">
                    <div class="podio-avatar">🥇</div>
                    <p style="font-weight:700;font-size:1rem;text-align:center;max-width:90px">
                        <?= htmlspecialchars($primeiro['nome']) ?>
                    </p>
                    <p style="color:var(--neon-green);font-size:0.85rem;font-weight:700">
                        <?= $primeiro['pontos_partida'] ?> pts
                    </p>
                    <div class="podio-base">1</div>
                </div>
                <?php endif; ?>

                <!-- 3º lugar -->
                <?php if ($terceiro): ?>
                <div class="podio-item terceiro" style="animation: fadeInUp 0.6s ease 0.4s both">
                    <div class="podio-avatar">🥉</div>
                    <p style="font-weight:700;font-size:0.9rem;text-align:center;max-width:90px">
                        <?= htmlspecialchars($terceiro['nome']) ?>
                    </p>
                    <p style="color:var(--text-muted);font-size:0.8rem"><?= $terceiro['pontos_partida'] ?> pts</p>
                    <div class="podio-base">3</div>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endif; ?>

        <!-- Lista completa -->
        <div class="lista-completa">
            <p class="secao-titulo">Classificação completa</p>

            <?php foreach ($jogadores as $i => $j): ?>
            <div class="ranking-item-final" style="animation-delay: <?= $i * 0.08 ?>s">
                <span class="ranking-pos <?= $i===0?'gold':($i===1?'silver':($i===2?'bronze':'')) ?>">
                    <?= $i===0?'🥇':($i===1?'🥈':($i===2?'🥉':($i+1).'º')) ?>
                </span>
                <span class="ranking-nome"><?= htmlspecialchars($j['nome']) ?></span>
                <span class="nivel-badge"><?= $nomes_nivel[$j['nivel']] ?></span>
                <div style="text-align:right;margin-left:auto">
                    <p style="font-weight:800;color:var(--neon-green)"><?= $j['pontos_partida'] ?> pts</p>
                    <p style="font-size:0.75rem;color:var(--text-muted)"><?= $j['xp'] ?> XP</p>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($jogadores)): ?>
            <p class="text-muted text-center">Nenhum jogador participou.</p>
            <?php endif; ?>
        </div>

        <!-- Botão novo jogo -->
        <div style="text-align:center;margin-top:32px">
            <a href="index.php" class="btn-novo-jogo">🎮 Novo Jogo</a>
        </div>

    </div>

    <script>
        // Gera confetes coloridos
        function gerarConfetes() {
            const cores = ['#00d4ff','#a855f7','#00ff88','#ff006e','#ffd700'];
            const container = document.getElementById('confetti');

            for (let i = 0; i < 80; i++) {
                setTimeout(() => {
                    const el = document.createElement('div');
                    el.classList.add('confete');
                    el.style.left        = Math.random() * 100 + 'vw';
                    el.style.top         = '-10px';
                    el.style.background  = cores[Math.floor(Math.random() * cores.length)];
                    el.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
                    el.style.width       = (Math.random() * 8 + 6) + 'px';
                    el.style.height      = (Math.random() * 8 + 6) + 'px';
                    el.style.animationDuration = (Math.random() * 2 + 2) + 's';
                    el.style.animationDelay    = Math.random() * 1 + 's';
                    container.appendChild(el);
                    setTimeout(() => el.remove(), 4000);
                }, i * 40);
            }
        }

        gerarConfetes();
        // Repete confetes a cada 5 segundos
        setInterval(gerarConfetes, 5000);
    </script>

</body>
</html>