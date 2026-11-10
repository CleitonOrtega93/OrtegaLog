
<?php
require_once __DIR__ . "/config/conexao.php";

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

$mensagem = "";
$tipoMensagem = "success";

$dadosFormulario = [
    "transportador_id" => "",
    "origem_id" => "",
    "destino_id" => "",
    "data_frete" => date("Y-m-d"),
    "status" => "Pendente",
    "observacoes" => ""
];

$quantidades = [];

// SALVAR FRETE
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $dadosFormulario = [
        "transportador_id" => $_POST["transportador_id"] ?? "",
        "origem_id" => $_POST["origem_id"] ?? "",
        "destino_id" => $_POST["destino_id"] ?? "",
        "data_frete" => $_POST["data_frete"] ?? "",
        "status" => $_POST["status"] ?? "Pendente",
        "observacoes" => trim($_POST["observacoes"] ?? "")
    ];

    $quantidades = $_POST["quantidades"] ?? [];

    $transportadorId = filter_var(
        $dadosFormulario["transportador_id"],
        FILTER_VALIDATE_INT
    );

    $origemId = filter_var(
        $dadosFormulario["origem_id"],
        FILTER_VALIDATE_INT
    );

    $destinoId = filter_var(
        $dadosFormulario["destino_id"],
        FILTER_VALIDATE_INT
    );

    $dataFrete = $dadosFormulario["data_frete"];
    $status = $dadosFormulario["status"];
    $observacoes = $dadosFormulario["observacoes"];

    $dataValida = false;

    $dataObjeto = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        $dataFrete
    );

    if (
        $dataObjeto &&
        $dataObjeto->format("Y-m-d") === $dataFrete
    ) {
        $dataValida = true;
    }

    $statusPermitidos = [
        "Pendente",
        "Em transporte",
        "Entregue",
        "Cancelado"
    ];

    $itens = [];
    $quantidadeInvalida = false;

    if (!is_array($quantidades)) {
        $quantidades = [];
        $quantidadeInvalida = true;
    }

    foreach ($quantidades as $produtoId => $quantidade) {

        $idProduto = filter_var(
            $produtoId,
            FILTER_VALIDATE_INT
        );

        $qtd = filter_var(
            $quantidade,
            FILTER_VALIDATE_INT
        );

        if (
            $quantidade !== "" &&
            $quantidade !== "0" &&
            $quantidade !== 0
        ) {
            if (
                $idProduto === false ||
                $idProduto <= 0 ||
                $qtd === false ||
                $qtd <= 0
            ) {
                $quantidadeInvalida = true;
            } else {
                $itens[$idProduto] = $qtd;
            }
        }
    }

    if (
        !$transportadorId ||
        !$origemId ||
        !$destinoId ||
        $origemId == $destinoId ||
        !$dataValida ||
        !in_array($status, $statusPermitidos, true) ||
        $quantidadeInvalida ||
        count($itens) === 0
    ) {
        $mensagem = "Confira os campos. Selecione origem e destino diferentes e informe pelo menos um produto com quantidade válida.";
        $tipoMensagem = "danger";
    } else {

        try {
            $conexao->beginTransaction();

            // Confirmar que os produtos existem
            $consultaProduto = $conexao->prepare(
                "SELECT id FROM produtos WHERE id = ?"
            );

            foreach ($itens as $produtoId => $quantidade) {
                $consultaProduto->execute([$produtoId]);

                if (!$consultaProduto->fetchColumn()) {
                    throw new RuntimeException(
                        "Um dos produtos selecionados não existe."
                    );
                }
            }

            // Cadastrar frete
            $sqlFrete = "
                INSERT INTO fretes
                (
                    transportador_id,
                    origem_id,
                    destino_id,
                    data_frete,
                    status,
                    observacoes
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ";

            $comando = $conexao->prepare($sqlFrete);

            $comando->execute([
                $transportadorId,
                $origemId,
                $destinoId,
                $dataFrete,
                $status,
                $observacoes
            ]);

            $freteId = $conexao->lastInsertId();

            // Cadastrar os produtos do frete
            $sqlItem = "
                INSERT INTO frete_produtos
                (frete_id, produto_id, quantidade)
                VALUES (?, ?, ?)
            ";

            $comandoItem = $conexao->prepare($sqlItem);

            foreach ($itens as $produtoId => $quantidade) {
                $comandoItem->execute([
                    $freteId,
                    $produtoId,
                    $quantidade
                ]);
            }

            $conexao->commit();

            header("Location: fretes.php?sucesso=1");
            exit;

        } catch (Throwable $erro) {

            if ($conexao->inTransaction()) {
                $conexao->rollBack();
            }

            error_log(
                "Erro ao cadastrar frete: " .
                $erro->getMessage()
            );

            $mensagem = "Não foi possível salvar o frete. Verifique se os registros selecionados existem e tente novamente.";
            $tipoMensagem = "danger";
        }
    }
}

if (isset($_GET["sucesso"])) {
    $mensagem = "Frete cadastrado com sucesso!";
}

// CARREGAR CADASTROS
$produtos = $conexao->query(
    "SELECT id, nome, codigo, quantidade
     FROM produtos
     ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);

$enderecos = $conexao->query(
    "SELECT id, logradouro, numero, cidade, estado
     FROM enderecos
     ORDER BY cidade, logradouro"
)->fetchAll(PDO::FETCH_ASSOC);

$transportadores = $conexao->query(
    "SELECT id, nome
     FROM transportadores
     ORDER BY nome"
)->fetchAll(PDO::FETCH_ASSOC);

// LISTAR FRETES
$fretes = $conexao->query(
    "SELECT
        f.id,
        f.data_frete,
        f.status,
        f.observacoes,
        t.nome AS transportador,
        CONCAT(
            eo.cidade, '/', eo.estado,
            ' - ', eo.logradouro
        ) AS origem,
        CONCAT(
            ed.cidade, '/', ed.estado,
            ' - ', ed.logradouro
        ) AS destino
     FROM fretes f
     INNER JOIN transportadores t
        ON t.id = f.transportador_id
     INNER JOIN enderecos eo
        ON eo.id = f.origem_id
     INNER JOIN enderecos ed
        ON ed.id = f.destino_id
     ORDER BY f.data_frete DESC, f.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// PRODUTOS VINCULADOS AOS FRETES
$produtosPorFrete = [];

$consultaItens = $conexao->query(
    "SELECT
        fp.frete_id,
        fp.quantidade,
        p.nome,
        p.codigo
     FROM frete_produtos fp
     INNER JOIN produtos p
        ON p.id = fp.produto_id
     ORDER BY fp.frete_id DESC, p.nome"
);

foreach ($consultaItens->fetchAll(PDO::FETCH_ASSOC) as $item) {
    $produtosPorFrete[$item["frete_id"]][] = $item;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Fretes | OrtegaLog</title>

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

        .produto-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
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
            <a href="enderecos.php">📍 Endereços</a>
            <a href="transportadores.php">🚛 Transportadores</a>
            <a href="fretes.php" class="ativo">📋 Fretes</a>
            <a href="relatorios.php">📊 Relatórios</a>
        </nav>

        <main class="col-md-9 col-lg-10 conteudo">

            <h1 class="fw-bold">Gerenciamento de Fretes</h1>

            <p class="text-muted mb-4">
                Cadastre e acompanhe os fretes do OrtegaLog.
            </p>

            <?php if ($mensagem): ?>
                <div class="alert alert-<?php echo escapar($tipoMensagem); ?>">
                    <?php echo escapar($mensagem); ?>
                </div>
            <?php endif; ?>

            <div class="painel">
                <h4 class="mb-4">Novo Frete</h4>

                <?php if (
                    count($produtos) === 0 ||
                    count($enderecos) < 2 ||
                    count($transportadores) === 0
                ): ?>
                    <div class="alert alert-warning">
                        Para cadastrar um frete, é necessário
                        ter produtos, pelo menos dois endereços
                        e uma transportadora cadastrados.
                    </div>
                <?php endif; ?>

                <form method="POST" action="fretes.php">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">
                                Transportadora
                            </label>

                            <select
                                name="transportador_id"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione
                                </option>

                                <?php foreach ($transportadores as $t): ?>
                                    <option
                                        value="<?php echo (int)$t["id"]; ?>"
                                        <?php echo
                                            (string)$dadosFormulario["transportador_id"] ===
                                            (string)$t["id"]
                                            ? "selected"
                                            : "";
                                        ?>>
                                        <?php echo escapar($t["nome"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Data do Frete
                            </label>

                            <input
                                type="date"
                                name="data_frete"
                                class="form-control"
                                required
                                value="<?php echo escapar($dadosFormulario["data_frete"]); ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Endereço de Origem
                            </label>

                            <select
                                name="origem_id"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione a origem
                                </option>

                                <?php foreach ($enderecos as $e): ?>
                                    <option
                                        value="<?php echo (int)$e["id"]; ?>"
                                        <?php echo
                                            (string)$dadosFormulario["origem_id"] ===
                                            (string)$e["id"]
                                            ? "selected"
                                            : "";
                                        ?>>
                                        <?php echo escapar(
                                            $e["cidade"] . "/" .
                                            $e["estado"] . " - " .
                                            $e["logradouro"] . ", " .
                                            $e["numero"]
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Endereço de Destino
                            </label>

                            <select
                                name="destino_id"
                                class="form-select"
                                required>

                                <option value="">
                                    Selecione o destino
                                </option>

                                <?php foreach ($enderecos as $e): ?>
                                    <option
                                        value="<?php echo (int)$e["id"]; ?>"
                                        <?php echo
                                            (string)$dadosFormulario["destino_id"] ===
                                            (string)$e["id"]
                                            ? "selected"
                                            : "";
                                        ?>>
                                        <?php echo escapar(
                                            $e["cidade"] . "/" .
                                            $e["estado"] . " - " .
                                            $e["logradouro"] . ", " .
                                            $e["numero"]
                                        ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                class="form-select"
                                required>

                                <?php
                                $opcoesStatus = [
                                    "Pendente",
                                    "Em transporte",
                                    "Entregue",
                                    "Cancelado"
                                ];

                                foreach ($opcoesStatus as $opcao):
                                ?>
                                    <option
                                        value="<?php echo escapar($opcao); ?>"
                                        <?php echo
                                            $dadosFormulario["status"] === $opcao
                                            ? "selected"
                                            : "";
                                        ?>>
                                        <?php echo escapar($opcao); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Produtos do Frete
                            </label>

                            <p class="text-muted small">
                                Informe a quantidade dos produtos
                                que serão transportados.
                                Deixe 0 para não incluir.
                            </p>

                            <?php foreach ($produtos as $produto): ?>
                                <div class="produto-item">
                                    <div class="row align-items-center g-3">

                                        <div class="col-md-8">
                                            <strong>
                                                <?php echo escapar($produto["nome"]); ?>
                                            </strong>

                                            <div class="text-muted small">
                                                Código:
                                                <?php echo escapar($produto["codigo"]); ?>
                                                |
                                                Quantidade cadastrada:
                                                <?php echo (int)$produto["quantidade"]; ?>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label">
                                                Quantidade no frete
                                            </label>

                                            <input
                                                type="number"
                                                name="quantidades[<?php echo (int)$produto["id"]; ?>]"
                                                class="form-control"
                                                min="0"
                                                step="1"
                                                value="<?php echo escapar(
                                                    $quantidades[$produto["id"]] ?? "0"
                                                ); ?>">
                                        </div>

                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label">
                                Observações
                            </label>

                            <textarea
                                name="observacoes"
                                class="form-control"
                                rows="3"><?php echo escapar($dadosFormulario["observacoes"]); ?></textarea>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button
                            type="submit"
                            class="btn btn-primary"
                            <?php echo (
                                count($produtos) === 0 ||
                                count($enderecos) < 2 ||
                                count($transportadores) === 0
                            ) ? "disabled" : ""; ?>>
                            Salvar Frete
                        </button>

                        <a
                            href="fretes.php"
                            class="btn btn-outline-secondary">
                            Limpar
                        </a>
                    </div>

                </form>
            </div>

            <div class="painel">
                <h4 class="mb-4">Fretes Cadastrados</h4>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Transportadora</th>
                                <th>Origem</th>
                                <th>Destino</th>
                                <th>Status</th>
                                <th>Produtos</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($fretes as $frete): ?>
                                <tr>
                                    <td>
                                        <?php echo (int)$frete["id"]; ?>
                                    </td>

                                    <td>
                                        <?php echo escapar(
                                            date(
                                                "d/m/Y",
                                                strtotime($frete["data_frete"])
                                            )
                                        ); ?>
                                    </td>

                                    <td>
                                        <?php echo escapar($frete["transportador"]); ?>
                                    </td>

                                    <td>
                                        <?php echo escapar($frete["origem"]); ?>
                                    </td>

                                    <td>
                                        <?php echo escapar($frete["destino"]); ?>
                                    </td>

                                    <td>
                                        <?php echo escapar($frete["status"]); ?>
                                    </td>

                                    <td>
                                        <?php
                                        $itensFrete =
                                            $produtosPorFrete[$frete["id"]] ?? [];
                                        ?>

                                        <?php foreach ($itensFrete as $item): ?>
                                            <div class="small mb-1">
                                                <?php echo escapar($item["nome"]); ?>
                                                —
                                                <?php echo (int)$item["quantidade"]; ?>
                                                un.
                                            </div>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (count($fretes) === 0): ?>
                                <tr>
                                    <td
                                        colspan="7"
                                        class="text-center text-muted py-4">
                                        Nenhum frete cadastrado.
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
