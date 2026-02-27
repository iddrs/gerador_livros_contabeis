<?php

use Livros\Diario;

ini_set('memory_limit', -1);

$inicio = new DateTimeImmutable();

require_once 'vendor/autoload.php';

cli_set_process_title('Gerandor de livro diário');

$data_inicial = date_create_from_format('Ymd', "{$remessa}01");
$data_final = date_create_from_format('Ymd', $remessa.cal_days_in_month(CAL_GREGORIAN, (int) substr($remessa, 4, 2), (int) substr($remessa, 0, 4)));

echo "Gerando diário para remessa $remessa e entidade $razao_social", PHP_EOL;

$sql = "SELECT d.data_lancamento, d.conta_contabil, bv.especificacao_conta_contabil, d.valor_lancamento, d.tipo_lancamento, d.nr_lote, d.nr_lancamento, d.historico_lancamento FROM pad.tce_4111 d INNER JOIN pad.bal_ver bv ON d.conta_contabil = bv.conta_contabil AND bv.remessa = $remessa AND bv.entidade $entidades WHERE d.remessa = $remessa AND d.entidade $entidades ORDER BY d.data_lancamento ASC, d.nr_lote ASC, d.nr_lancamento ASC;";
echo $sql, PHP_EOL;

$data = $db->query($sql, PDO::FETCH_ASSOC);
echo 'Pico de memória utilizada pelo PHP até agora: '. format_bytes(memory_get_peak_usage(true)), PHP_EOL;

echo 'Preparando FPDF...', PHP_EOL;
$pdf = new Diario();
$pdf->razaoSocial = $razao_social;
$pdf->cnpj = $cnpj;
$pdf->dataInicial = $data_inicial;
$pdf->dataFinal = $data_final;
$pdf->AliasNbPages();

echo 'Gerando termo de abertura...', PHP_EOL;
$pdf->AddPage('P', 'A4');
$termo_abertura = "Contém o presente livro {nb} páginas tipograficamente numeradas, compondo o livro diário número 1 da entidade $razao_social sita à Rua Senador Pinheiro, nº 1348, Centro, cidade de Independência, Estado do Rio Grande do Sul, inscrita no CNPJ sob número $cnpj.";
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


$w = [147, 40, 10, 40, 40];
$pdf->SetWidths($w);
$pdf->AddPage('L', 'A4');
$pdf->SetFont('Arial', 'B', 10);
$header = [mb_convert_encoding('Conta contábil', 'ISO-8859-1', 'UTF-8'), 'Valor', 'D/C', 'Lote', mb_convert_encoding('Lançamento', 'ISO-8859-1', 'UTF-8')];
foreach($header as $i => $col) {
    $pdf->Cell($w[$i], 7, $col, 1);
}
$pdf->Ln();

echo 'Gerando dados no PDF...', PHP_EOL;
$data_lancamento = '';
$pdf->SetFont('Arial', '', 10);
foreach($data as $row){
    if($data_lancamento !== $row['data_lancamento']){
        $dataf = date_create_from_format('Y-m-d', $row['data_lancamento'])->format('d/m/Y');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 6, $dataf, 1);
        $pdf->Ln();
        $data_lancamento = $row['data_lancamento'];
        $pdf->SetFont('Arial', '', 10);
    }
    $especificacao = mb_convert_encoding($row['especificacao_conta_contabil'], 'ISO-8859-1', 'UTF-8');
    $historico = mb_convert_encoding($row['historico_lancamento'], 'ISO-8859-1', 'UTF-8');
    $cc = format_cc($row['conta_contabil']);
    $pdf->Row([
        "$cc $especificacao",
        $row['valor_lancamento'],
        $row['tipo_lancamento'],
        $row['nr_lote'],
        $row['nr_lancamento'],
    ]);
    $pdf->MultiCell(0, 6, $historico, 1);
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
$termo_encerramento = "Contém o presente livro {nb} páginas tipograficamente numeradas, compondo o livro diário número 1 das operações compreendidas no período de {$data_inicial->format('d/m/Y')} a {$data_final->format('d/m/Y')} da entidade $razao_social sita à Rua Senador Pinheiro, nº 1348, Centro, cidade de Independência, Estado do Rio Grande do Sul, inscrita no CNPJ sob número $cnpj.";
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

$arquivo = "diario-$remessa-$cod_entidade.pdf";
echo "Salvando resultado em $arquivo...", PHP_EOL;
$pdf->Output('F', $arquivo);
echo 'Pico de memória utilizada pelo PHP até agora: '. format_bytes(memory_get_peak_usage(true)), PHP_EOL;

$fim = new DateTimeImmutable();

$intervalo = date_diff($inicio, $fim, true);
$tempo = $intervalo->format('%H horas, %i minutos, %s segundos');
echo "Processamento terminado em $tempo", PHP_EOL;