<?php

echo 'Gerador de livros contábeis', PHP_EOL;
echo 'Copyright 2026 (c) Everton da Rosa <everton3x@gmail.com>', PHP_EOL;
echo '----------------------------------------------------------', PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;

echo 'Qual livro deseja? [D]iário | [R]azão', PHP_EOL;
$livro = strtolower(trim(fgets(STDIN)));

echo 'Qual período deseja gerar? [AAAAMM]', PHP_EOL;
$remessa = trim(fgets(STDIN));

echo 'De qual entidade? [A]gregado | [C]âmara | [P]refeitura | [R]pps', PHP_EOL;
$entidade = strtolower(trim(fgets(STDIN)));

switch($entidade){
    case 'a':
        $cod_entidade = 'mun';
        $entidades = "IN ('pm', 'cm', 'fpsm')";
        $cnpj = '87.612.826/0001-90';
        $razao_social = 'Município de Independência - RS';
        $contador = [
            'nome' => 'XXXXXXXXXX',
            'cargo' => 'Contador',
            'doc' => 'CRC nº RS-99999999999'
        ];
        $administrador = [
            'nome' => 'XXXXXXXX',
            'cargo' => 'Prefeito Municipal',
            'doc' => 'CPF nº 999999999999'
        ];
        break;
    case 'c':
        $cod_entidade = 'cm';
        $entidades = "IN ('cm')";
        $cnpj = '12.292.535/0001-62';
        $razao_social = 'Câmara de Vereadores de Independência - RS';
        $contador = [
            'nome' => 'XXXXXXXX',
            'cargo' => 'Contador',
            'doc' => 'CRC nº RS-9999999999'
        ];
        $administrador = [
            'nome' => 'XXXXXXXXXX',
            'cargo' => 'Presidente da Câmara de Vereadores',
            'doc' => 'CPF nº 9999999999999'
        ];
        break;
    case 'p':
        $cod_entidade = 'pm';
        $entidades = "IN ('pm')";
        $cnpj = '87.612.826/0001-90';
        $razao_social = 'Prefeitura Municipal de Independência - RS';
        $contador = [
            'nome' => 'XXXXXXXXXXXX',
            'cargo' => 'Contador',
            'doc' => 'CRC nº RS-99999999999'
        ];
        $administrador = [
            'nome' => 'XXXXXXXXXXX',
            'cargo' => 'Prefeito Municipal',
            'doc' => 'CPF nº 9999999999999'
        ];
        break;
    case 'r':
        $cod_entidade = 'fpsm';
        $entidades = "IN ('fpsm')";
        $cnpj = '12.091.144/0001-80';
        $razao_social = 'Fundo de Previdência dos Servidores do Município de Independência - RS';
        $contador = [
            'nome' => 'XXXXXXXXXX',
            'cargo' => 'Contador',
            'doc' => 'CRC nº RS-999999999999'
        ];
        $administrador = [
            'nome' => 'XXXXXXXXX',
            'cargo' => 'Prefeito Municipal',
            'doc' => 'CPF nº 9999999999999'
        ];
        break;
    default:
        exit('Entidade selecionada inválida. Preste mais atenção às opções disponíveis!');
}

$db = new \PDO('pgsql:host=localhost;port=5432;dbname=xxxxx', 'xxxxx', 'xxxxx');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

switch($livro){
    case 'd':
        require 'diario.php';
        break;
    case 'r':
        require 'razao.php';
        break;
    default:
        exit('Livro errado! Leia as mensagens com mais atenção.');
}