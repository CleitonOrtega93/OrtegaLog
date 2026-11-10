
<?php
require_once __DIR__ . "/config/conexao.php";

$mensagem = "";
$tipoMensagem = "success";
$produtoEditar = null;

// Salvar ou atualizar produto
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
    $nome = trim($_POST["nome"] ?? "");
    $codigo = trim($_POST["codigo"] ?? "");
    $descricao = trim($_POST["descricao"] ?? "");
    $quantidade = filter_input(INPUT_POST, "quantidade", FILTER_VALIDATE_INT);
    $peso = filter_input(INPUT_POST, "peso", FILTER_VALIDATE_FLOAT);

    if (
        $nome === "" || $codigo === "" ||
        $quantidade === false || $quantidade === null ||
        $quantidade < 0 ||
        $peso === false || $peso === null || $peso < 0
    ) {
        $mensagem = "Preencha os campos corretamente.";
        $tipoMensagem = "danger";
    } else {
        try {
            if ($id) {
                $sql = "UPDATE produtos
                        SET nome = ?, codigo = ?, descricao = ?,
                            quantidade = ?, peso = ?
                        WHERE id = ?";
                $dados = [$nome, $codigo, $descricao, $quantidade, $peso, $id];
            } else {
                $sql = "INSERT INTO produtos
                        (nome, codigo, descricao, quantidade, peso)
                        VALUES (?, ?, ?, ?, ?)";
                $dados = [$nome, $codigo, $descricao, $quantidade, $peso];
            }

            $comando = $conexao->prepare($sql);
            $comando->execute($dados);

            header("Location: produtos.php?sucesso=1");
            exit;
        } catch (PDOException $erro) {
            $mensagem = "Não foi possível salvar. Verifique se o código já existe.";
            $tipoMensagem = "danger";
        }
    }
}

if (isset($_GET["sucesso"])) {
    $mensagem = "Produto salvo com sucesso!";
}

// Buscar produto para edição
$idEditar = filter_input(INPUT_GET, "editar", FILTER_VALIDATE_INT);

if ($idEditar) {
    $comando = $conexao->prepare("SELECT * FROM produtos WHERE id = ?");
    $comando->execute([$idEditar]);
    $produtoEditar = $comando->fetch(PDO::FETCH_ASSOC);
}

// Listar produtos
$comando = $conexao->query("SELECT * FROM produtos ORDER BY id DESC");
$produtos = $comando->fetchAll(PDO::FETCH_ASSOC);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos | OrtegaLog</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">

        <nav class="col-md-3 col-lg-2 sidebar">
            <h2>🚚 OrtegaLog</h2>

            
<a href="index.php">🏠 Painel Inicial</a>
<a href="produtos.php" class="ativo">📦 Produtos</a>
<a href="enderecos.php">📍 Endereços</a>
<a href="transportadores.php">🚛 Transportadores</a>
<a href="fretes.php">📋 Fretes</a>
<a href="relatorios.php">📊 Relatórios</a>

        </nav>

        <main class="col-md-9 col-lg-10 conteudo">

            <h1 class="fw-bold">Cadastro de Produtos</h1>
            <p class="text-muted mb-4">
                Gerencie os produtos utilizados nos fretes.
            </p>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo $tipoMensagem; ?>">
                    <?php echo escapar($mensagem); ?>
                </div>
            <?php endif; ?>

            <div class="painel">
                <h4 class="mb-4">
                    <?php echo $produtoEditar ? "Editar Produto" : "Novo Produto"; ?>
                </h4>

                <form method="POST">
                    <input type="hidden" name="id"
                           value="<?php echo escapar($produtoEditar["id"] ?? ""); ?>">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Nome do produto</label>
                            <input type="text" name="nome" class="form-control"
                                   maxlength="150" required
                                   value="<?php echo escapar($produtoEditar["nome"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Código</label>
                            <input type="text" name="codigo" class="form-control"
                                   maxlength="50" required
                                   value="<?php echo escapar($produtoEditar["codigo"] ?? ""); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Quantidade</label>
                            <input type="number" name="quantidade" min="0"
                                   class="form-control" required
                                   value="<?php echo escapar($produtoEditar["quantidade"] ?? "0"); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Peso unitário (kg)</label>
                            <input type="number" name="peso" min="0" step="0.01"
                                   class="form-control" required
                                   value="<?php echo escapar($produtoEditar["peso"] ?? "0"); ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descrição</label>
                            <textarea name="descricao" class="form-control"
                                      rows="3"><?php echo escapar($produtoEditar["descricao"] ?? ""); ?></textarea>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            Salvar Produto
                        </button>

                        <a href="produtos.php" class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <div class="painel">
                <h4 class="mb-4">Produtos Cadastrados</h4>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Código</th>
                                <th>Quantidade</th>
                                <th>Peso (kg)</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td><?php echo escapar($produto["id"]); ?></td>
                                    <td><?php echo escapar($produto["nome"]); ?></td>
                                    <td><?php echo escapar($produto["codigo"]); ?></td>
                                    <td><?php echo escapar($produto["quantidade"]); ?></td>
                                    <td><?php echo escapar($produto["peso"]); ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="produtos.php?editar=<?php echo (int)$produto["id"]; ?>">
                                            Editar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($produtos) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Nenhum produto cadastrado.
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
