
<?php
// OrtegaLog - Relatórios de Fretes

ob_start();
require_once __DIR__ . "/config/conexao.php";
ob_end_clean();

function relEscapar($valor) {
    return htmlspecialchars(
        (string)($valor ?? ""),
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

function relDataValida($data) {
    if (!is_string($data)) {
        return false;
    }

    $obj = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        $data
    );

    return $obj !== false
        && $obj->format("Y-m-d") === $data;
}

function relFormatarData($data) {
    $obj = DateTimeImmutable::createFromFormat(
        "!Y-m-d",
        (string)$data
    );

    return $obj ? $obj->format("d/m/Y") : "";
}

function relSeguroCSV($valor) {
    $texto = (string)($valor ?? "");

    if (preg_match('/^\s*[=+\-@]/u', $texto)) {
        return "'" . $texto;
    }

    return $texto;
}

// Data selecionada
$dataSelecionada = $_GET["data"] ?? date("Y-m-d");
$erro = "";

if (!relDataValida($dataSelecionada)) {
    $erro = "Informe uma data válida.";
    $dataSelecionada = date("Y-m-d");
}

// Consulta de fretes
$sql = "
    SELECT
        f.id,
        f.data_frete,
        f.status,
        f.observacoes,
        t.nome AS transportadora,

        CONCAT(
            eo.logradouro, ', ',
            eo.numero, ' - ',
            eo.cidade, '/', eo.estado
        ) AS origem,

        CONCAT(
            ed.logradouro, ', ',
            ed.numero, ' - ',
            ed.cidade, '/', ed.estado
        ) AS destino,

        p.nome AS produto,
        p.codigo AS codigo_produto,
        fp.quantidade,
        (fp.quantidade * p.peso) AS peso_total

    FROM fretes f

    INNER JOIN transportadores t
        ON t.id = f.transportador_id

    INNER JOIN enderecos eo
        ON eo.id = f.origem_id

    INNER JOIN enderecos ed
        ON ed.id = f.destino_id

    LEFT JOIN frete_produtos fp
        ON fp.frete_id = f.id

    LEFT JOIN produtos p
        ON p.id = fp.produto_id

    WHERE f.data_frete = ?

    ORDER BY f.id DESC, p.nome
";

$comando = $conexao->prepare($sql);
$comando->execute([$dataSelecionada]);

$registros = $comando->fetchAll(PDO::FETCH_ASSOC);

// Organizar fretes e indicadores
$fretes = [];
$totalUnidades = 0;
$pesoTotal = 0;

foreach ($registros as $registro) {
    $id = (int)$registro["id"];

    if (!isset($fretes[$id])) {
        $fretes[$id] = [
            "id" => $id,
            "status" => $registro["status"],
            "transportadora" => $registro["transportadora"],
            "origem" => $registro["origem"],
            "destino" => $registro["destino"],
            "produtos" => []
        ];
    }

    if ($registro["produto"] !== null) {
        $quantidade = (int)$registro["quantidade"];
        $peso = (float)$registro["peso_total"];

        $fretes[$id]["produtos"][] = [
            "nome" => $registro["produto"],
            "codigo" => $registro["codigo_produto"],
            "quantidade" => $quantidade,
            "peso_total" => $peso
        ];

        $totalUnidades += $quantidade;
        $pesoTotal += $peso;
    }
}

$totalFretes = count($fretes);

// Exportar CSV
if (($_GET["exportar"] ?? "") === "csv") {

    if ($erro !== "") {
        http_response_code(400);
        exit($erro);
    }

    $nomeArquivo = "relatorio_fretes_"
        . $dataSelecionada . ".csv";

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header("Content-Type: text/csv; charset=UTF-8");
    header(
        'Content-Disposition: attachment; filename="'
        . $nomeArquivo . '"'
    );
    header("Cache-Control: no-store");

    $saida = fopen("php://output", "wb");

    if ($saida === false) {
        exit("Não foi possível gerar o CSV.");
    }

    // BOM UTF-8: ajuda o Excel a reconhecer acentos.
    fwrite($saida, "\xEF\xBB\xBF");

    // Primeira linha do CSV: cabeçalhos.
    fputcsv($saida, [
        "Frete",
        "Data",
        "Transportadora",
        "Origem",
        "Destino",
        "Status",
        "Código do produto",
        "Produto",
        "Quantidade",
        "Peso total (kg)",
        "Observações"
    ], ";", '"', "");

    foreach ($registros as $registro) {
        fputcsv($saida, [
            (int)$registro["id"],
            relFormatarData($registro["data_frete"]),
            relSeguroCSV($registro["transportadora"]),
            relSeguroCSV($registro["origem"]),
            relSeguroCSV($registro["destino"]),
            relSeguroCSV($registro["status"]),
            relSeguroCSV($registro["codigo_produto"]),
            relSeguroCSV($registro["produto"]),
            (int)($registro["quantidade"] ?? 0),
            number_format(
                (float)($registro["peso_total"] ?? 0),
                2,
                ",",
                ""
            ),
            relSeguroCSV($registro["observacoes"] ?? "")
        ], ";", '"', "");
    }

    fclose($saida);
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Relatórios | OrtegaLog</title>

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

        .resumo {
            border-left: 4px solid #0d6efd;
        }

        .numero-resumo {
            font-size: 28px;
            font-weight: bold;
            color: #142b49;
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

            <a href="index.php">
                🏠 Painel Inicial
            </a>

            <a href="produtos.php">
                📦 Produtos
            </a>

            <a href="enderecos.php">
                📍 Endereços
            </a>

            <a href="transportadores.php">
                🚛 Transportadores
            </a>

            <a href="fretes.php">
                📋 Fretes
            </a>

            <a href="relatorios.php" class="ativo">
                📊 Relatórios
            </a>

        </nav>

        <main class="col-md-9 col-lg-10 conteudo">

            <h1 class="fw-bold">
                Relatórios de Fretes
            </h1>

            <p class="text-muted mb-4">
                Consulte os fretes por data e exporte
                os resultados para Excel.
            </p>

            <?php if ($erro !== ""): ?>
                <div class="alert alert-danger">
                    <?= relEscapar($erro) ?>
                </div>
            <?php endif; ?>

            <div class="painel">
                <h4 class="mb-4">
                    Consultar Fretes por Data
                </h4>

                <form method="GET" action="relatorios.php">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-5">
                            <label class="form-label">
                                Data do Frete
                            </label>

                            <input
                                type="date"
                                name="data"
                                class="form-control"
                                required
                                value="<?= relEscapar(
                                    $dataSelecionada
                                ) ?>">
                        </div>

                        <div class="col-md-7">

                            <button
                                type="submit"
                                class="btn btn-primary">
                                Consultar
                            </button>

                            <a
                                class="btn btn-success"
                                href="relatorios.php?data=<?= urlencode(
                                    $dataSelecionada
                                ) ?>&amp;exportar=csv">
                                📥 Baixar Relatório CSV
                            </a>

                        </div>
                    </div>
                </form>
            </div>

            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="painel resumo h-100">
                        <div class="text-muted">
                            Total de Fretes
                        </div>

                        <div class="numero-resumo">
                            <?= $totalFretes ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="painel resumo h-100">
                        <div class="text-muted">
                            Unidades Transportadas
                        </div>

                        <div class="numero-resumo">
                            <?= $totalUnidades ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="painel resumo h-100">
                        <div class="text-muted">
                            Peso Total (kg)
                        </div>

                        <div class="numero-resumo">
                            <?= number_format(
                                $pesoTotal,
                                2,
                                ",",
                                "."
                            ) ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="painel">
                <h4 class="mb-3">
                    Fretes de
                    <?= relEscapar(
                        relFormatarData($dataSelecionada)
                    ) ?>
                </h4>

                <?php if ($totalFretes === 0): ?>

                    <div class="alert alert-info mb-0">
                        Nenhum frete encontrado para esta data.
                    </div>

                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Frete</th>
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
                                            #<?= (int)$frete["id"] ?>
                                        </td>

                                        <td>
                                            <?= relEscapar(
                                                $frete["transportadora"]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= relEscapar(
                                                $frete["origem"]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= relEscapar(
                                                $frete["destino"]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= relEscapar(
                                                $frete["status"]
                                            ) ?>
                                        </td>

                                        <td>
                                            <?php foreach (
                                                $frete["produtos"] as $produto
                                            ): ?>

                                                <div class="mb-2">
                                                    <strong>
                                                        <?= relEscapar(
                                                            $produto["nome"]
                                                        ) ?>
                                                    </strong>

                                                    <div class="small text-muted">
                                                        Código:
                                                        <?= relEscapar(
                                                            $produto["codigo"]
                                                        ) ?>

                                                        |
                                                        Quantidade:
                                                        <?= (int)$produto["quantidade"] ?>

                                                        |
                                                        Peso:
                                                        <?= number_format(
                                                            $produto["peso_total"],
                                                            2,
                                                            ",",
                                                            "."
                                                        ) ?> kg
                                                    </div>
                                                </div>

                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                    </div>

                <?php endif; ?>
            </div>

        </main>
    </div>
</div>
</body>
</html>
