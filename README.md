# OrtegaLog — Sistema de Gerenciamento Logístico

Projeto acadêmico desenvolvido para a disciplina de Fundamentos de Programação para Internet, do curso de Engenharia de Software da UNIPAR.

## Funcionalidades

- Cadastro e edição de produtos.
- Cadastro e edição de endereços de origem e destino.
- Cadastro e edição de transportadores.
- Registro de fretes com transportadora, origem, destino e múltiplos produtos.
- Consulta de fretes por data.
- Exportação de relatórios em CSV, compatível com Excel.
- Painel inicial com indicadores dos cadastros.

## Tecnologias utilizadas

- PHP
- MySQL/MariaDB
- HTML5 e CSS3
- JavaScript
- Bootstrap
- XAMPP

## Como executar o projeto

1. Instale o XAMPP e inicie Apache e MySQL.
2. Copie os arquivos do projeto para `C:\xampp\htdocs\ortegalog`.
3. Acesse o phpMyAdmin e crie um banco de dados chamado `ortegalog`.
4. Importe o arquivo `ortegalog.sql`.
5. Na pasta `config`, faça uma cópia de `conexao.exemplo.php` e renomeie a cópia para `conexao.php`.
6. Configure o usuário e a senha do MySQL no arquivo `conexao.php`.
7. Acesse `http://localhost/ortegalog/` no navegador.

## Estrutura do projeto

- `index.php` — Painel inicial.
- `produtos.php` — Cadastro de produtos.
- `enderecos.php` — Cadastro de endereços.
- `transportadores.php` — Cadastro de transportadores.
- `fretes.php` — Gerenciamento de fretes.
- `relatorios.php` — Consulta e exportação de relatórios.
- `config/conexao.exemplo.php` — Modelo de conexão com o banco de dados.
- `ortegalog.sql` — Estrutura do banco de dados.

## Autor

Cleiton Ortega Martins  
Curso: Engenharia de Software  
Instituição: Universidade Paranaense — UNIPAR

## Finalidade

Projeto desenvolvido para fins acadêmicos, demonstrando a integração entre interface web, programação PHP e banco de dados relacional.
