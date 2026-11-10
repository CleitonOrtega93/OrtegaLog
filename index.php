
<?php
require_once __DIR__ . "/config/conexao.php";

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function contarRegistros($conexao, $tabela) {
    try {
        $tabelasPermitidas = [
            "produtos",
            "enderecos",
            "transportadores",
            "fretes"
        ];

        if (!in_array($tabela, $tabelasPermitidas, true)) {
            return null;
        }

        $consulta = $conexao->query(
            "SELECT COUNT(*) FROM " . $tabela
        );

        return (int)$consulta->fetchColumn();
    } catch (PDOException $erro) {
        error_log($erro->getMessage());
        return null;
    }
}

$totalProdutos = contarRegistros($conexao, "produtos");
$totalEnderecos = contarRegistros($conexao, "enderecos");
$totalTransportadores = contarRegistros($conexao, "transportadores");
$totalFretes = contarRegistros($conexao, "fretes");

function mostrarTotal($total) {
    return $total === null ? "—" : (string)$total;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Painel Inicial | OrtegaLog</title>

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

        .cartao {
            background: white;
            border-radius: 12px;
            padding: 25px;
            height: 100%;
            box-shadow: 0 3px 12px rgba(0,0,0,0.05);
        }

        .cartao h3 {
            font-size: 32px;
            font-weight: bold;
            color: #142b49;
        }

        .cartao p {
            color: #64748b;
            margin-bottom: 0;
        }

        .atalho {
            text-decoration: none;
            display: block;
            color: inherit;
            transition: transform 0.2s;
        }

        .atalho:hover {
            transform: translateY(-3px);
        }

        .atalho .painel {
            border-left: 4px solid #285a91;
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

            <a href="index.php" class="ativo">
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

            <a href="relatorios.php">
                📊 Relatórios
            </a>

        </nav>

        <!-- CONTEÚDO PRINCIPAL -->
        <main class="col-md-9 col-lg-10 conteudo">

            <h1 class="fw-bold">
                Painel Inicial
            </h1>

            <p class="text-muted mb-4">
                Bem-vindo ao OrtegaLog.
                Gerencie produtos, endereços,
                transportadores e fretes.
            </p>

            <!-- INDICADORES -->
            <div class="row g-4 mb-4">

                <div class="col-md-6 col-xl-3">
                    <div class="cartao">
                        <h3>
                            <?php echo mostrarTotal($totalProdutos); ?>
                        </h3>
                        <p>📦 Produtos cadastrados</p>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="cartao">
                        <h3>
                            <?php echo mostrarTotal($totalEnderecos); ?>
                        </h3>
                        <p>📍 Endereços cadastrados</p>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="cartao">
                        <h3>
                            <?php echo mostrarTotal($totalTransportadores); ?>
                        </h3>
                        <p>🚛 Transportadores</p>
                    </div>
                </div>

                <div class="col-md-6 col-xl-3">
                    <div class="cartao">
                        <h3>
                            <?php echo mostrarTotal($totalFretes); ?>
                        </h3>
                        <p>📋 Fretes cadastrados</p>
                    </div>
                </div>

            </div>

            <!-- ACESSO RÁPIDO -->
            <div class="painel">
                <h4 class="fw-bold mb-3">
                    Acesso rápido
                </h4>

                <p class="text-muted">
                    Selecione uma funcionalidade
                    para gerenciar os dados do sistema.
                </p>

                <div class="row g-3 mt-2">

                    <div class="col-md-6">
                        <a href="produtos.php"
                           class="btn btn-primary w-100 p-3">
                            📦 Gerenciar Produtos
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="enderecos.php"
                           class="btn btn-primary w-100 p-3">
                            📍 Gerenciar Endereços
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="transportadores.php"
                           class="btn btn-outline-primary w-100 p-3">
                            🚛 Gerenciar Transportadores
                        </a>
                    </div>

                    <div class="col-md-6">
                        <a href="fretes.php"
                           class="btn btn-outline-primary w-100 p-3">
                            📋 Gerenciar Fretes
                        </a>
                    </div>

                    <div class="col-md-12">
                        <a href="relatorios.php"
                           class="btn btn-outline-secondary w-100 p-3">
                            📊 Consultar Relatórios
                        </a>
                    </div>

                </div>
            </div>

            <!-- INFORMAÇÕES DO PROJETO -->
            <div class="painel">
                <h4 class="fw-bold">
                    Sobre o sistema
                </h4>

                <p>
                    O OrtegaLog é um sistema web
                    desenvolvido para gerenciamento
                    de operações logísticas.
                </p>

                <p class="mb-0">
                    Tecnologias utilizadas:
                    PHP, MySQL, HTML5, CSS3,
                    JavaScript e Bootstrap.
                </p>
            </div>

        </main>
    </div>
</div>
</body>
</html>
