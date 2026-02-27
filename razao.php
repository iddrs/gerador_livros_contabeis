<?php

use Livros\Razao;

ini_set('memory_limit', -1);

$inicio = new DateTimeImmutable();

require_once 'vendor/autoload.php';

cli_set_process_title('Gerandor de livro razão');

$data_inicial = date_create_from_format('Ymd', "{$remessa}01");
$data_final = date_create_from_format('Ymd', $remessa.cal_days_in_month(CAL_GREGORIAN, (int) substr($remessa, 4, 2), (int) substr($remessa, 0, 4)));

echo "Gerando razão para remessa $remessa e entidade $razao_social", PHP_EOL;

echo "Buscando contas contábeis e saldos iniciais...", PHP_EOL;
$sql = "select conta_contabil, especificacao_conta_contabil, sum(saldo_inicial::decimal) as saldo_inicial from pad.bal_ver where remessa = $remessa and entidade $entidades and escrituracao like 'S' group by conta_contabil, especificacao_conta_contabil order by conta_contabil asc;";
echo $sql, PHP_EOL;
$contas = $db->query($sql, PDO::FETCH_ASSOC);
echo 'Pico de memória utilizada pelo PHP até agora: '. format_bytes(memory_get_peak_usage(true)), PHP_EOL;

echo 'Preparando FPDF...', PHP_EOL;
$pdf = new Razao();
$pdf->razaoSocial = $razao_social;
$pdf->cnpj = $cnpj;
$pdf->dataInicial = $data_inicial;
$pdf->dataFinal = $data_final;
$pdf->AliasNbPages();

echo 'Gerando termo de abertura...', PHP_EOL;
$pdf->AddPage('P', 'A4');
$termo_abertura = "Contém o presente livro {nb} páginas tipograficamente numeradas, compondo o livro razão número 1 da entidade $razao_social sita à Rua Senador Pinheiro, nº 1348, Centro, cidade de Independência, Estado do Rio Grande do Sul, inscrita no CNPJ sob número $cnpj.";
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, mb_convert_encoding('TERMO DE ABERTURA', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 6, mb_convert_encoding($termo_abertura, 'ISO-8859-1', 'UTF-8'), 0, 'J');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$data_atual = date('d/m/Y');
$pdf->Cell(0, 6, mb_convert_encoding("Independência, RS, $data_atual", 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');

echo 'Gerando dados no PDF...', PHP_EOL;
$pdf->AddPage('L', 'A4');

foreach($contas as $row){
    $cc = format_cc($row['conta_contabil']);
    $especificacao = mb_convert_encoding($row['especificacao_conta_contabil'], 'ISO-8859-1', 'UTF-8');
    echo "$cc $especificacao", PHP_EOL;
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->MultiCell(0, 6, "$cc $especificacao", 'T', 'L');
    $pdf->Ln();
    switch((int) $cc[0]){
        case 1:
        case 3:
        case 5:
        case 7:
            if($row['saldo_inicial'] > 0){
                $natureza_saldo = 'D';
            }elseif($row['saldo_inicial'] < 0){
                $natureza_saldo = 'C';
            }else{
                $natureza_saldo = '';
            }
            break;
        case 2:
        case 4:
        case 6:
        case 8:
            if($row['saldo_inicial'] > 0){
                $natureza_saldo = 'C';
            }elseif($row['saldo_inicial'] < 0){
                $natureza_saldo = 'D';
            }else{
                $natureza_saldo = '';
            }
            break;
    }
    $saldo_inicialf = number_format(abs($row['saldo_inicial']), 2, ',', '.')." $natureza_saldo";
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, "Saldo inicial: $saldo_inicialf", 0, 1, 'R');

    $sql = "select data_lancamento, valor_lancamento::decimal, historico_lancamento, nr_lote, nr_lancamento, tipo_lancamento from pad.tce_4111 where remessa = $remessa and entidade $entidades and conta_contabil like '{$row['conta_contabil']}' order by data_lancamento asc;";
    $lancamentos = $db->query($sql, PDO::FETCH_ASSOC);

    $w = [47, 47, 47, 47, 47, 52];//287
    $pdf->SetWidths($w);
    $pdf->SetFont('Arial', 'B', 10);
    $header = ['Data', 'Lote', mb_convert_encoding('Lançamento', 'ISO-8859-1', 'UTF-8'), 
    mb_convert_encoding('Débito', 'ISO-8859-1', 'UTF-8'), mb_convert_encoding('Crédito', 'ISO-8859-1', 'UTF-8'), 'Saldo'];
    foreach($header as $i => $col) {
        $pdf->Cell($w[$i], 6, $col, 1);
    }
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 10);
    $total_debitos = 0.0;
    $total_creditos = 0.0;
    $saldo_atual = $row['saldo_inicial'];
    foreach($lancamentos as $item){
        $pdf->MultiCell(0, 6, mb_convert_encoding($item['historico_lancamento'], 'ISO-8859-1', 'UTF-8'), 0, 'J');
        $pdf->Cell($w[0], 6, date_create_from_format('Y-m-d', $item['data_lancamento'])->format('d/m/Y'), 1, null, 'L');
        $pdf->Cell($w[1], 6, $item['nr_lote'], 1, null, 'L');
        $pdf->Cell($w[2], 6, $item['nr_lancamento'], 1, null, 'L');
        switch(strtolower($item['tipo_lancamento'])){
            case 'd':
                $debito = $item['valor_lancamento'];
                $credito = 0.0;
                break;
            case 'c':
                $debito = 0.00;
                $credito = $item['valor_lancamento'];
                break;
        }
        $total_debitos += $debito;
        $total_creditos += $credito;
        $pdf->Cell($w[3], 6, number_format($debito, 2, ',', '.'), 1, null, 'R');
        $pdf->Cell($w[4], 6, number_format($credito, 2, ',', '.'), 1, null, 'R');
        switch((int) $cc[0]){
            case 1:
            case 3:
            case 5:
            case 7:
                $saldo_atual += $debito;
                $saldo_atual -= $credito;
                if($saldo_atual > 0){
                    $natureza_saldo = 'D';
                }elseif($saldo_atual < 0){
                    $natureza_saldo = 'C';
                }else{
                    $natureza_saldo = '';
                }
                break;
            case 2:
            case 4:
            case 6:
            case 8:
                $saldo_atual += $credito;
                $saldo_atual -= $debito;
                if($saldo_atual > 0){
                    $natureza_saldo = 'C';
                }elseif($saldo_atual < 0){
                    $natureza_saldo = 'D';
                }else{
                    $natureza_saldo = '';
                }
                break;
        }
        $saldo_atualf = number_format(abs($saldo_atual), 2, ',', '.')." $natureza_saldo";
        $pdf->Cell($w[4], 6, $saldo_atualf, 1, 1, 'R');
    }
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell($w[0]+$w[1]+$w[2], 6, 'Totais', 0, 0, 'R');
    $pdf->Cell($w[3], 6, number_format($total_debitos, 2, ',', '.'), 0, 0, 'R');
    $pdf->Cell($w[4], 6, number_format($total_creditos, 2, ',', '.'), 0, 0, 'R');
    $pdf->Cell($w[5], 6, $saldo_atualf, 0, 1, 'R');
}
echo 'Pico de memória utilizada pelo PHP até agora: '. format_bytes(memory_get_peak_usage(true)), PHP_EOL;

echo 'Gerando assinaturas...', PHP_EOL;
$pdf->AddPage('P', 'A4');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, mb_convert_encoding('ASSINATURAS', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 6, mb_convert_encoding($administrador['nome'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Cell(0, 6, mb_convert_encoding($administrador['cargo'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Cell(0, 6, mb_convert_encoding($administrador['doc'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Cell(0, 6, mb_convert_encoding($contador['nome'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Cell(0, 6, mb_convert_encoding($contador['cargo'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
$pdf->Cell(0, 6, mb_convert_encoding($contador['doc'], 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');

echo 'Gerando termo de encerramento...', PHP_EOL;
$pdf->AddPage('P', 'A4');
$termo_encerramento = "Contém o presente livro {nb} páginas tipograficamente numeradas, compondo o livro razão número 1 das operações compreendidas no período de {$data_inicial->format('d/m/Y')} a {$data_final->format('d/m/Y')} da entidade $razao_social sita à Rua Senador Pinheiro, nº 1348, Centro, cidade de Independência, Estado do Rio Grande do Sul, inscrita no CNPJ sob número $cnpj.";
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 6, mb_convert_encoding('TERMO DE ENCERRAMENTO', 'ISO-8859-1', 'UTF-8'), 0, 0, 'C');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$pdf->SetFont('Arial', '', 12);
$pdf->MultiCell(0, 6, mb_convert_encoding($termo_encerramento, 'ISO-8859-1', 'UTF-8'), 0, 'J');
$pdf->Ln();
$pdf->Ln();
$pdf->Ln();
$data_atual = date('d/m/Y');
$pdf->Cell(0, 6, mb_convert_encoding("Independência, RS, $data_atual", 'ISO-8859-1', 'UTF-8'), 0, 0, 'R');

echo 'Pico de memória utilizada pelo PHP até agora: '. format_bytes(memory_get_peak_usage(true)), PHP_EOL;

$arquivo = "razao-$remessa-$cod_entidade.pdf";
echo "Salvando resultado em $arquivo...", PHP_EOL;
$pdf->Output('F', $arquivo);
echo 'Pico de memória utilizada pelo PHP até agora: '. format_bytes(memory_get_peak_usage(true)), PHP_EOL;

$fim = new DateTimeImmutable();

$intervalo = date_diff($inicio, $fim, true);
$tempo = $intervalo->format('%H horas, %i minutos, %s segundos');
echo "Processamento terminado em $tempo", PHP_EOL;