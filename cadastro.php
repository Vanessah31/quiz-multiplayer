<?php
session_start();
require_once "db/conexao.php";

$codigo = isset($_GET['sala']) ? strtoupper(trim($_GET['sala'])) : '';

$sala = null;
if ($codigo) {
    $sql  = "SELECT * FROM salas WHERE codigo = '$codigo' AND status = 'aguardando'";
    $res  = mysqli_query($conn, $sql);
    $sala = mysqli_fetch_assoc($res);
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome        = trim($_POST['nome']        ?? '');
    $email       = trim($_POST['email']       ?? '');
    $nascimento  = trim($_POST['nascimento']  ?? '');
    $sala_id     = intval($_POST['sala_id']   ?? 0);

    // Validações
    if (empty($nome) || strlen($nome) < 2) {
        $erro = 'Digite seu nome completo (mínimo 2 letras)!';
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Digite um e-mail válido!';
    } elseif (empty($nascimento)) {
        $erro = 'Digite sua data de nascimento!';
    } else {
        // Calcula idade
        $hoje    = new DateTime();
        $nasc    = new DateTime($nascimento);
        $idade   = $hoje->diff($nasc)->y;

        if ($idade < 5 || $idade > 100) {
            $erro = 'Data de nascimento inválida!';
        } else {
            // Verifica se email já está na sala
            $email_safe = mysqli_real_escape_string($conn, $email);
            $nome_safe  = mysqli_real_escape_string($conn, $nome);
            $sql = "SELECT id FROM jogadores WHERE sala_id = $sala_id AND email = '$email_safe'";
            $res = mysqli_query($conn, $sql);
            if (mysqli_num_rows($res) > 0) {
                $erro = 'Esse e-mail já está cadastrado nessa sala!';
            } else {
                // Insere jogador
                $sql = "INSERT INTO jogadores (sala_id, nome, email, nascimento, xp, nivel, pontos_partida)
                        VALUES ($sala_id, '$nome_safe', '$email_safe', '$nascimento', 0, 1, 0)";
                mysqli_query($conn, $sql);
                $jogador_id = mysqli_insert_id($conn);

                $_SESSION['jogador_id']   = $jogador_id;
                $_SESSION['jogador_nome'] = $nome;
                $_SESSION['sala_id']      = $sala_id;
                $_SESSION['sala_codigo']  = $codigo;

                header("Location: jogador.php?sala=$codigo");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar no Quiz</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="tela-jogador">
<div class="container-jogador">

    <?php if (!$sala): ?>
    <div class="card">
        <h1>❌ Sala inválida</h1>
        <p>Essa sala não existe ou o jogo já começou.<br>Peça um novo QR Code ao host!</p>
    </div>

    <?php else: ?>
    <div class="card">
        <h1>🎮 Quiz Multiplayer</h1>
        <p>Sala <strong style="color:var(--neon-blue)"><?= $codigo ?></strong><br>Preencha seus dados para entrar!</p>

        <?php if ($erro): ?>
        <div class="msg-acerto errado" style="margin-bottom:16px">
            ⚠️ <?= $erro ?>
        </div>
        <?php endif; ?>

        <div class="form-group">
            <label>Nome completo</label>
            <input type="text" id="nome" name="nome" placeholder="Ex: Vanessa Sousa"
                maxlength="50" autocomplete="off" autofocus
                value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>E-mail</label>
            <input type="email" id="email" name="email" placeholder="Ex: vanessa@email.com"
                autocomplete="off"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Data de nascimento</label>
            <input type="date" id="nascimento" name="nascimento"
                max="<?= date('Y-m-d') ?>"
                value="<?= htmlspecialchars($_POST['nascimento'] ?? '') ?>">
        </div>

        <input type="hidden" id="sala_id" value="<?= $sala['id'] ?>">

        <button class="btn-entrar" onclick="entrar()">
            🚀 Entrar no Jogo
        </button>

        <div class="divisor mt-16"></div>
        <p class="text-muted text-center mt-8">
            Sala <strong><?= $codigo ?></strong> — Seus dados são usados apenas nessa partida
        </p>
    </div>

    <div class="card" style="padding:16px 24px">
        <p class="text-muted text-center">
            💡 Fique com o celular em mãos — o jogo começa em breve!
        </p>
    </div>
    <?php endif; ?>

</div>
<script>
    function entrar() {
        const nome       = document.getElementById('nome').value.trim();
        const email      = document.getElementById('email').value.trim();
        const nascimento = document.getElementById('nascimento').value;
        const sala_id    = document.getElementById('sala_id').value;

        if (!nome)       { alert('Digite seu nome!');             return; }
        if (!email)      { alert('Digite seu e-mail!');           return; }
        if (!nascimento) { alert('Digite sua data de nascimento!'); return; }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        [['nome', nome], ['email', email], ['nascimento', nascimento], ['sala_id', sala_id]].forEach(([n, v]) => {
            const i = document.createElement('input');
            i.name = n; i.value = v;
            form.appendChild(i);
        });

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('keypress', e => { if (e.key === 'Enter') entrar(); });
</script>
</body>
</html>