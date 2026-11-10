
<?php
require_once __DIR__ . "/config/conexao.php";

$mensagem = "";
$tipoMensagem = "success";
$transportadorEditar = null;

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Cadastrar ou editar transportador
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $nome = trim($_POST["nome"] ?? "");
    $documento = trim($_POST["documento"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $veiculo = trim($_POST["veiculo"] ?? "");
    $placa = strtoupper(trim($_POST["placa"] ?? ""));

    if (
        $nome === "" ||
        $documento === "" ||
        ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL))
    ) {
        $mensagem = "Preencha o nome e o CPF/CNPJ corretamente. Verifique também o e-mail.";
        $tipoMensagem = "danger";
    } else {
        try {
            if ($id) {
                $sql = "UPDATE transportadores
                        SET nome = ?, documento = ?, telefone = ?,
                            email = ?, veiculo = ?, placa = ?
                        WHERE id = ?";

                $dados = [
                    $nome, $documento, $telefone,
                    $email, $veiculo, $placa, $id
                ];
            } else {
                $sql = "INSERT INTO transportadores
                        (nome, documento, telefone, email, veiculo, placa)
                        VALUES (?, ?, ?, ?, ?, ?)";

                $dados = [
                    $nome, $documento, $telefone,
                    $email, $veiculo, $placa
                ];
            }

            $comando = $conexao->prepare($sql);
            $comando->execute($dados);

            header("Location: transportadores.php?sucesso=1");
            exit;

        } catch (PDOException $erro) {
            error_log("Erro ao salvar transportador: " . $erro->getMessage());
            $mensagem = "Não foi possível salvar o transportador. Verifique os dados e o banco de dados.";
            $tipoMensagem = "danger";
        }
    }

    // Manter os dados preenchidos se ocorrer erro
    if ($tipoMensagem === "danger") {
        $transportadorEditar = [
            "id" => $id ?: "",
            "nome" => $nome,
            "documento" => $documento,
            "telefone" => $telefone,
            "email" => $email,
            "veiculo" => $veiculo,
            "placa" => $placa
        ];
    }
}

if (isset($_GET["sucesso"])) {
    $mensagem = "Transportador salvo com sucesso!";
}

// Buscar transportador para edição
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $idEditar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

    if ($idEditar) {
        $comando = $conexao->prepare(
            "SELECT * FROM transportadores WHERE id = ?"
        );
        $comando->execute([$idEditar]);
        $transportadorEditar = $comando->fetch(PDO::FETCH_ASSOC);
    }
}

// Listar transportadores
$comando = $conexao->query(
    "SELECT * FROM transportadores ORDER BY id DESC"
);
$transportadores = $comando->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transportadores | OrtegaLog</title>

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

        <!-- MENU LATERAL -->
        <nav class="col-md-3 col-lg-2 sidebar">
            <h2>🚚 OrtegaLog</h2>

            <a href="index.php">🏠 Painel Inicial</a>
            <a href="produtos.php">📦 Produtos</a>
            <a href="enderecos.php">📍 Endereços</a>
            <a href="transportadores.php" class="ativo">🚛 Transportadores</a>
            <a href="fretes.php">📋 Fretes</a>
            <a href="relatorios.php">📊 Relatórios</a>
        </nav>

        <!-- CONTEÚDO -->
        <main class="col-md-9 col-lg-10 conteudo">

            <h1 class="fw-bold">Cadastro de Transportadores</h1>

            <p class="text-muted mb-4">
                Gerencie os transportadores responsáveis pelos fretes.
            </p>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo escapar($tipoMensagem); ?>">
                    <?php echo escapar($mensagem); ?>
                </div>
            <?php endif; ?>

            <!-- FORMULÁRIO -->
            <div class="painel">

                <h4 class="mb-4">
                    <?php echo $transportadorEditar
                        ? "Editar Transportador"
                        : "Novo Transportador"; ?>
                </h4>

                <form method="POST" action="transportadores.php">
                    <input
                        type="hidden"
                        name="id"
                        value="<?php echo escapar($transportadorEditar["id"] ?? ""); ?>">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nome / Razão Social</label>
                            <input
                                type="text"
                                name="nome"
                                class="form-control"
                                maxlength="150"
                                required
                                value="<?php echo escapar($transportadorEditar["nome"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">CPF / CNPJ</label>
                            <input
                                type="text"
                                name="documento"
                                class="form-control"
                                maxlength="20"
                                required
                                placeholder="CPF ou CNPJ"
                                value="<?php echo escapar($transportadorEditar["documento"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Telefone</label>
                            <input
                                type="text"
                                name="telefone"
                                class="form-control"
                                maxlength="20"
                                placeholder="(45) 99999-9999"
                                value="<?php echo escapar($transportadorEditar["telefone"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">E-mail</label>
                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                maxlength="150"
                                value="<?php echo escapar($transportadorEditar["email"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Veículo</label>
                            <input
                                type="text"
                                name="veiculo"
                                class="form-control"
                                maxlength="100"
                                placeholder="Ex.: Caminhão Baú"
                                value="<?php echo escapar($transportadorEditar["veiculo"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Placa</label>
                            <input
                                type="text"
                                name="placa"
                                class="form-control"
                                maxlength="10"
                                placeholder="ABC1D23"
                                value="<?php echo escapar($transportadorEditar["placa"] ?? ""); ?>">
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            Salvar Transportador
                        </button>

                        <a
                            href="transportadores.php"
                            class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <!-- TABELA -->
            <div class="painel">

                <h4 class="mb-4">Transportadores Cadastrados</h4>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>CPF/CNPJ</th>
                                <th>Telefone</th>
                                <th>Veículo</th>
                                <th>Placa</th>
                                <th>Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($transportadores as $transportador): ?>
                                <tr>
                                    <td><?php echo escapar($transportador["id"]); ?></td>
                                    <td><?php echo escapar($transportador["nome"]); ?></td>
                                    <td><?php echo escapar($transportador["documento"]); ?></td>
                                    <td><?php echo escapar($transportador["telefone"]); ?></td>
                                    <td><?php echo escapar($transportador["veiculo"]); ?></td>
                                    <td><?php echo escapar($transportador["placa"]); ?></td>
                                    <td>
                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="transportadores.php?editar=<?php echo (int)$transportador["id"]; ?>">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($transportadores) === 0): ?>
                                <tr>
                                    <td
                                        colspan="7"
                                        class="text-center text-muted py-4">
                                        Nenhum transportador cadastrado.
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
