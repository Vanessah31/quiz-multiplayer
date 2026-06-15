<?php
// =============================================
// PAINEL ADMIN - admin/index.php
// Gerencia perguntas + gerador com IA
// =============================================
require_once "../db/conexao.php";

// Deleta pergunta
if (isset($_GET['deletar'])) {
    $id = intval($_GET['deletar']);
    mysqli_query($conn, "DELETE FROM perguntas WHERE id = $id");
    header("Location: index.php?msg=deletada");
    exit;
}

// Busca todas as perguntas
$sql      = "SELECT * FROM perguntas ORDER BY id DESC";
$res      = mysqli_query($conn, $sql);
$perguntas = [];
while ($p = mysqli_fetch_assoc($res)) {
    $perguntas[] = $p;
}

$total = count($perguntas);
$msg   = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Quiz Multiplayer</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .admin-container {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 24px;
        }
        .admin-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }
        .pergunta-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.4s ease;
            transition: border-color 0.3s;
        }
        .pergunta-card:hover { border-color: rgba(0,212,255,0.4); }
        .pergunta-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-blue), transparent);
        }
        .pergunta-texto-admin {
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 12px;
            color: #fff;
            line-height: 1.5;
        }
        .opcoes-admin {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .opcao-admin {
            background: var(--bg-card2);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 0.82rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .opcao-admin.correta {
            border-color: var(--neon-green);
            background: rgba(0,255,136,0.08);
            color: var(--neon-green);
        }
        .btn-deletar {
            background: rgba(255,0,110,0.15);
            border: 1px solid var(--neon-pink);
            color: var(--neon-pink);
            padding: 6px 16px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-deletar:hover { background: rgba(255,0,110,0.3); }

        /* Formulário manual */
        .form-admin input,
        .form-admin select,
        .form-admin textarea {
            background: var(--bg-card2);
            border: 1px solid rgba(0,212,255,0.2);
            border-radius: var(--radius-sm);
            color: #fff;
            padding: 12px 16px;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 12px;
        }
        .form-admin input:focus,
        .form-admin textarea:focus,
        .form-admin select:focus {
            border-color: var(--neon-blue);
            box-shadow: 0 0 15px rgba(0,212,255,0.15);
        }
        .form-admin textarea { resize: vertical; min-height: 80px; }
        .form-admin select option { background: var(--bg-card2); }

        .btn-salvar {
            width: 100%;
            background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple));
            color: white;
            border: none;
            padding: 14px;
            font-size: 0.9rem;
            font-weight: 700;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-family: 'Orbitron', monospace;
        }
        .btn-salvar:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,212,255,0.3); }

        /* Gerador IA */
        .ia-box {
            background: var(--bg-card);
            border: 1px solid rgba(168,85,247,0.3);
            border-radius: var(--radius);
            padding: 28px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        .ia-box::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-purple), var(--neon-blue), transparent);
        }
        .btn-ia {
            background: linear-gradient(135deg, var(--neon-purple), #6d28d9);
            color: white;
            border: none;
            padding: 16px 32px;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-family: 'Orbitron', monospace;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
        }
        .btn-ia:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(168,85,247,0.4); }
        .btn-ia:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .ia-resultado {
            margin-top: 20px;
            display: none;
        }
        .ia-pergunta-preview {
            background: var(--bg-card2);
            border: 1px solid rgba(168,85,247,0.3);
            border-radius: var(--radius-sm);
            padding: 16px;
            margin-bottom: 12px;
            animation: fadeInUp 0.4s ease;
        }
        .loading-dots::after {
            content: '';
            animation: dots 1.5s infinite;
        }
        @keyframes dots {
            0%   { content: ''; }
            33%  { content: '.'; }
            66%  { content: '..'; }
            100% { content: '...'; }
        }
        .badge-total {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0,212,255,0.1);
            border: 1px solid rgba(0,212,255,0.3);
            color: var(--neon-blue);
            padding: 4px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .msg-sucesso {
            background: rgba(0,255,136,0.1);
            border: 1px solid var(--neon-green);
            color: var(--neon-green);
            padding: 12px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-weight: 600;
            animation: popIn 0.3s ease;
        }
        .section-title {
            font-family: 'Orbitron', monospace;
            font-size: 0.72rem;
            color: rgba(255,255,255,0.4);
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 16px;
        }
        @media (max-width: 768px) { .admin-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="tela-host">
<div class="admin-container">

    <!-- Header -->
    <div class="header-host" style="margin-bottom:32px">
        <h1>⚙️ Painel Admin</h1>
        <p class="subtitulo">Gerencie as perguntas do Quiz</p>
    </div>

    <!-- Link para o jogo -->
    <div style="display:flex;gap:12px;margin-bottom:28px;flex-wrap:wrap">
        <a href="../index.php" style="text-decoration:none">
            <span class="chip" style="cursor:pointer;color:var(--neon-blue)">🎮 Ir para o Jogo</span>
        </a>
        <span class="badge-total">📚 <?= $total ?> perguntas cadastradas</span>
    </div>

    <?php if ($msg === 'salva'): ?>
    <div class="msg-sucesso">✅ Pergunta salva com sucesso!</div>
    <?php elseif ($msg === 'deletada'): ?>
    <div class="msg-sucesso" style="border-color:var(--neon-pink);color:var(--neon-pink);background:rgba(255,0,110,0.1)">
        🗑️ Pergunta deletada!
    </div>
    <?php elseif ($msg === 'ia'): ?>
    <div class="msg-sucesso">🤖 Perguntas geradas pela IA salvas com sucesso!</div>
    <?php endif; ?>

    <!-- GERADOR COM IA -->
    <div class="ia-box">
        <p class="section-title">🤖 Gerador de perguntas com IA</p>
        <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;margin-bottom:4px">
            Digite o tema e a IA vai criar perguntas automaticamente para o seu quiz!
        </p>

        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;align-items:end;margin-top:16px">
            <div>
                <label style="font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:3px;display:block;margin-bottom:8px">
                    Tema das perguntas
                </label>
                <input type="text" id="tema-ia"
                    placeholder="Ex: HTML e CSS, PHP, Banco de Dados, JavaScript..."
                    style="background:var(--bg-card2);border:1px solid rgba(168,85,247,0.3);border-radius:12px;color:#fff;padding:14px 18px;font-size:0.95rem;outline:none;width:100%;font-family:'Inter',sans-serif">
            </div>
            <div>
                <label style="font-size:0.72rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:3px;display:block;margin-bottom:8px">
                    Quantidade
                </label>
                <select id="qtd-ia" style="background:var(--bg-card2);border:1px solid rgba(168,85,247,0.3);border-radius:12px;color:#fff;padding:14px 18px;font-size:0.95rem;outline:none;font-family:'Inter',sans-serif">
                    <option value="3">3</option>
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                </select>
            </div>
        </div>

        <button class="btn-ia" onclick="gerarComIA()" id="btn-ia">
            <span>✨</span> <span id="btn-ia-texto">Gerar com IA</span>
        </button>

        <!-- Resultado da IA -->
        <div class="ia-resultado" id="ia-resultado">
            <div class="divisor" style="margin:20px 0"></div>
            <p class="section-title">Preview das perguntas geradas</p>
            <div id="ia-preview"></div>
            <button class="btn-salvar" onclick="salvarIA()" id="btn-salvar-ia" style="margin-top:8px">
                💾 Salvar todas no banco
            </button>
        </div>
    </div>

    <!-- GRID: Cadastro manual + Lista -->
    <div class="admin-grid">

        <!-- Formulário manual -->
        <div>
            <p class="section-title">✏️ Cadastrar pergunta manualmente</p>
            <div class="card form-admin">
                <textarea id="f-texto" placeholder="Digite a pergunta..."></textarea>
                <input type="text" id="f-a" placeholder="Opção A">
                <input type="text" id="f-b" placeholder="Opção B">
                <input type="text" id="f-c" placeholder="Opção C">
                <input type="text" id="f-d" placeholder="Opção D">
                <select id="f-resp">
                    <option value="">Selecione a resposta correta</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                </select>
                <button class="btn-salvar" onclick="salvarManual()">💾 Salvar Pergunta</button>
            </div>
        </div>

        <!-- Lista de perguntas -->
        <div>
            <p class="section-title">📋 Perguntas cadastradas (<?= $total ?>)</p>
            <?php if (empty($perguntas)): ?>
            <div class="card">
                <p class="text-muted text-center">Nenhuma pergunta cadastrada ainda.</p>
            </div>
            <?php else: ?>
            <div style="max-height:600px;overflow-y:auto;padding-right:4px">
                <?php foreach ($perguntas as $i => $p): ?>
                <div class="pergunta-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                        <p class="pergunta-texto-admin"><?= ($i+1) ?>. <?= htmlspecialchars($p['texto']) ?></p>
                        <button class="btn-deletar" onclick="confirmarDeletar(<?= $p['id'] ?>)">🗑️</button>
                    </div>
                    <div class="opcoes-admin">
                        <?php foreach (['A','B','C','D'] as $l): ?>
                        <div class="opcao-admin <?= $p['resposta']===$l ? 'correta' : '' ?>">
                            <span class="letra-opcao" style="width:22px;height:22px;font-size:0.72rem"><?= $l ?></span>
                            <?= htmlspecialchars($p['opcao_'.strtolower($l)]) ?>
                            <?php if ($p['resposta']===$l): ?> ✅<?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<script>
    let perguntasIA = [];

    // Salva pergunta manualmente
    function salvarManual() {
        const dados = {
            texto:    document.getElementById('f-texto').value.trim(),
            opcao_a:  document.getElementById('f-a').value.trim(),
            opcao_b:  document.getElementById('f-b').value.trim(),
            opcao_c:  document.getElementById('f-c').value.trim(),
            opcao_d:  document.getElementById('f-d').value.trim(),
            resposta: document.getElementById('f-resp').value
        };

        if (!dados.texto || !dados.opcao_a || !dados.opcao_b || !dados.opcao_c || !dados.opcao_d || !dados.resposta) {
            alert('Preencha todos os campos!');
            return;
        }

        const form = new FormData();
        Object.entries(dados).forEach(([k,v]) => form.append(k, v));

        fetch('salvar.php', { method: 'POST', body: form })
            .then(r => r.json())
            .then(data => {
                if (data.sucesso) window.location.href = 'index.php?msg=salva';
                else alert('Erro ao salvar: ' + data.erro);
            });
    }

    // Gera perguntas com IA
    async function gerarComIA() {
        const tema = document.getElementById('tema-ia').value.trim();
        const qtd  = document.getElementById('qtd-ia').value;

        if (!tema) { alert('Digite um tema para gerar as perguntas!'); return; }

        const btn  = document.getElementById('btn-ia');
        const texto = document.getElementById('btn-ia-texto');
        btn.disabled = true;
        texto.innerHTML = '<span class="loading-dots">Gerando</span>';

        try {
            const resp = await fetch('gerar_ia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ tema, quantidade: qtd })
            });

            const data = await resp.json();

            if (data.erro) {
                alert('Erro: ' + data.erro);
                return;
            }

            perguntasIA = data.perguntas;
            mostrarPreview(data.perguntas);

        } catch(e) {
            alert('Erro ao conectar com a IA. Verifique sua conexão!');
        } finally {
            btn.disabled = false;
            texto.textContent = 'Gerar com IA';
        }
    }

    // Mostra preview das perguntas geradas
    function mostrarPreview(perguntas) {
        const div = document.getElementById('ia-preview');
        let html = '';

        perguntas.forEach((p, i) => {
            html += `
                <div class="ia-pergunta-preview">
                    <p style="font-weight:700;margin-bottom:10px;color:#fff">${i+1}. ${p.texto}</p>
                    <div class="opcoes-admin" style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
                        ${['A','B','C','D'].map(l => `
                            <div class="opcao-admin ${p.resposta===l?'correta':''}">
                                <span class="letra-opcao" style="width:20px;height:20px;font-size:0.7rem">${l}</span>
                                ${p['opcao_'+l.toLowerCase()]}
                                ${p.resposta===l?' ✅':''}
                            </div>
                        `).join('')}
                    </div>
                </div>`;
        });

        div.innerHTML = html;
        document.getElementById('ia-resultado').style.display = 'block';
        document.getElementById('ia-resultado').scrollIntoView({ behavior: 'smooth' });
    }

    // Salva perguntas da IA no banco
    function salvarIA() {
        if (!perguntasIA.length) return;

        const btn = document.getElementById('btn-salvar-ia');
        btn.disabled = true;
        btn.textContent = 'Salvando...';

        fetch('salvar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ perguntas: perguntasIA })
        })
        .then(r => r.json())
        .then(data => {
            if (data.sucesso) window.location.href = 'index.php?msg=ia';
            else alert('Erro ao salvar: ' + data.erro);
        });
    }

    // Confirma antes de deletar
    function confirmarDeletar(id) {
        if (confirm('Tem certeza que deseja deletar essa pergunta?')) {
            window.location.href = 'index.php?deletar=' + id;
        }
    }
</script>
</body>
</html>