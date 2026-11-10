
<?php
// Exemplo de configuração do banco de dados OrtegaLog.
// Copie este arquivo para conexao.php e ajuste os dados
// conforme seu ambiente local.

$servidor = "localhost";
$banco = "ortegalog";
$usuario = "SEU_USUARIO";
$senha = "SUA_SENHA";

try {
    $conexao = new PDO(
        "mysql:host=$servidor;dbname=$banco;charset=utf8mb4",
        $usuario,
        $senha
    );

    $conexao->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    http_response_code(500);
    exit("Erro ao conectar ao banco de dados.");
}
