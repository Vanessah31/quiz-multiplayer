<?php
// =============================================
// CONEXÃO COM O BANCO DE DADOS
// Arquivo: db/conexao.php
// =============================================

$host     = "localhost";
$usuario  = "root";
$senha    = "";          // No XAMPP a senha padrão é vazia
$banco    = "quiz_game";

// Cria a conexão
$conn = mysqli_connect($host, $usuario, $senha, $banco);

// Verifica se conectou
if (!$conn) {
    die(json_encode([
        "erro" => "Falha na conexão: " . mysqli_connect_error()
    ]));
}

// Define o charset para suportar acentos
mysqli_set_charset($conn, "utf8mb4");
?>
