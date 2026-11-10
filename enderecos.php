
<?php
require_once __DIR__ . "/config/conexao.php";

$mensagem = "";
$tipoMensagem = "success";
$enderecoEditar = null;

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Cadastrar ou editar endereço
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $cep = trim($_POST["cep"] ?? "");
    $logradouro = trim($_POST["logradouro"] ?? "");
    $numero = trim($_POST["numero"] ?? "");
    $complemento = trim($_POST["complemento"] ?? "");
    $bairro = trim($_POST["bairro"] ?? "");
    $cidade = trim($_POST["cidade"] ?? "");
    $estado = strtoupper(trim($_POST["estado"] ?? ""));

    if (
        !preg_match('/^\d{5}-?\d{3}$/', $cep) ||
        $logradouro === "" ||
        $numero === "" ||
        $bairro === "" ||
        $cidade === "" ||
        !preg_match('/^[A-Z]{2}$/', $estado)
    ) {
        $mensagem = "Preencha todos os campos corretamente. O CEP deve ter 8 números.";
        $tipoMensagem = "danger";
    } else {
        try {
            if ($id) {
                $sql = "UPDATE enderecos
                        SET cep = ?, logradouro = ?, numero = ?,
                            complemento = ?, bairro = ?, cidade = ?,
                            estado = ?
                        WHERE id = ?";

                $dados = [
                    $cep, $logradouro, $numero,
                    $complemento, $bairro, $cidade,
                    $estado, $id
                ];
            } else {
                $sql = "INSERT INTO enderecos
                        (cep, logradouro, numero, complemento,
                         bairro, cidade, estado)
                        VALUES (?, ?, ?, ?, ?, ?, ?)";

                $dados = [
                    $cep, $logradouro, $numero,
                    $complemento, $bairro, $cidade,
                    $estado
                ];
            }

            $comando = $conexao->prepare($sql);
            $comando->execute($dados);

            header("Location: enderecos.php?sucesso=1");
            exit;

        } catch (PDOException $erro) {
            error_log("Erro ao salvar endereço: " . $erro->getMessage());
            $mensagem = "Erro ao salvar endereço. Verifique o banco de dados e o log do PHP.";
            $tipoMensagem = "danger";
        }
    }
}

if (isset($_GET["sucesso"])) {
    $mensagem = "Endereço salvo com sucesso!";
}

// Buscar endereço para edição
$idEditar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

if ($idEditar) {
    $comando = $conexao->prepare(
        "SELECT * FROM enderecos WHERE id = ?"
    );
    $comando->execute([$idEditar]);
    $enderecoEditar = $comando->fetch(PDO::FETCH_ASSOC);
}

// Preservar dados digitados caso a validação falhe
if ($_SERVER["REQUEST_METHOD"] === "POST" && $tipoMensagem === "danger") {
    $enderecoEditar = [
        "id" => $id ?: "",
        "cep" => $cep,
        "logradouro" => $logradouro,
        "numero" => $numero,
        "complemento" => $complemento,
        "bairro" => $bairro,
        "cidade" => $cidade,
        "estado" => $estado
    ];
}

// Listar endereços
$comando = $conexao->query(
    "SELECT * FROM enderecos ORDER BY id DESC"
);
$enderecos = $comando->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Endereços | OrtegaLog</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            background: #f3f6fb;
            font-family: Arial, sans-serif;
        }

        .sidebar {
            background: #142b49;
            min-height: 100vh;
            padding: 25px 15px;
            color: white;
        }

        .sidebar h2 {
            font-size: 25px;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: #dbe5f2;
            text-decoration: none;
            padding: 13px;
            border-radius: 8px;
            margin-bottom: 7px;
        }

        .sidebar a:hover,
        .sidebar a.ativo {
            background: #285a91;
            color: white;
        }

        .conteudo {
            padding: 35px;
        }

        .painel {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        @media (max-width: 767px) {
            .sidebar {
                min-height: auto;
            }

            .conteudo {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <nav class="col-md-3 col-lg-2 sidebar">
            <h2>🚚 OrtegaLog</h2>

            <a href="index.php">🏠 Painel Inicial</a>
            <a href="produtos.php">📦 Produtos</a>
            <a href="enderecos.php" class="ativo">📍 Endereços</a>
            <a href="transportadores.php">🚛 Transportadores</a>
            <a href="fretes.php">📋 Fretes</a>
            <a href="relatorios.php">📊 Relatórios</a>
        </nav>

        <main class="col-md-9 col-lg-10 conteudo">

            <h1 class="fw-bold">Cadastro de Endereços</h1>
            <p class="text-muted mb-4">
                Gerencie os endereços de origem e destino dos fretes.
            </p>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo escapar($tipoMensagem); ?>">
                    <?php echo escapar($mensagem); ?>
                </div>
            <?php endif; ?>

            <div class="painel">
                <h4 class="mb-4">
                    <?php echo $enderecoEditar
                        ? "Editar Endereço"
                        : "Novo Endereço"; ?>
                </h4>

                <form method="POST" action="enderecos.php">
                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo escapar($enderecoEditar["id"] ?? ""); ?>">

                    <div class="row g-3">

                        <div class="col-md-4">
                            <label class="form-label">CEP</label>
                            <input
                                type="text"
                                name="cep"
                                class="form-control"
                                maxlength="9"
                                placeholder="00000-000"
                                pattern="[0-9]{5}-?[0-9]{3}"
                                title="Digite um CEP com 8 números"
                                required
                                value="<?php echo escapar($enderecoEditar["cep"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Logradouro</label>
                            <input
                                type="text"
                                name="logradouro"
                                class="form-control"
                                maxlength="150"
                                required
                                value="<?php echo escapar($enderecoEditar["logradouro"] ?? ""); ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Número</label>
                            <input
                                type="text"
                                name="numero"
                                class="form-control"
                                maxlength="20"
                                required
                                value="<?php echo escapar($enderecoEditar["numero"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Bairro</label>
                            <input
                                type="text"
                                name="bairro"
                                class="form-control"
                                maxlength="100"
                                required
                                value="<?php echo escapar($enderecoEditar["bairro"] ?? ""); ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Cidade</label>
                            <input
                                type="text"
                                name="cidade"
                                class="form-control"
                                maxlength="100"
                                required
                                value="<?php echo escapar($enderecoEditar["cidade"] ?? ""); ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Estado (UF)</label>
                            <input
                                type="text"
                                name="estado"
                                class="form-control"
                                maxlength="2"
                                placeholder="PR"
                                required
                                value="<?php echo escapar($enderecoEditar["estado"] ?? ""); ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Complemento</label>
                            <input
                                type="text"
                                name="complemento"
                                class="form-control"
                                maxlength="100"
                                value="<?php echo escapar($enderecoEditar["complemento"] ?? ""); ?>">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            Salvar Endereço
                        </button>

                        <a href="enderecos.php"
                           class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <div class="painel">
                <h4 class="mb-4">Endereços Cadastrados</h4>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>CEP</th>
                                <th>Logradouro</th>
                                <th>Número</th>
                                <th>Bairro</th>
                                <th>Cidade/UF</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($enderecos as $endereco): ?>
                                <tr>
                                    <td><?php echo escapar($endereco["id"]); ?></td>
                                    <td><?php echo escapar($endereco["cep"]); ?></td>
                                    <td><?php echo escapar($endereco["logradouro"]); ?></td>
                                    <td><?php echo escapar($endereco["numero"]); ?></td>
                                    <td><?php echo escapar($endereco["bairro"]); ?></td>
                                    <td>
                                        <?php echo escapar($endereco["cidade"]); ?>
                                        /
                                        <?php echo escapar($endereco["estado"]); ?>
                                    </td>
                                    <td>
                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="enderecos.php?editar=<?php echo (int)$endereco["id"]; ?>">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($enderecos) === 0): ?>
                                <tr>
                                    <td colspan="7"
                                        class="text-center text-muted py-4">
                                        Nenhum endereço cadastrado.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>
</body>
</html>
