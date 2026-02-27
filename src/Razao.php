<?php

namespace Livros;

use DateTime;

class Razao extends \PDF_MC_Table
{
    public string $razaoSocial = '';
    public string $cnpj = '';
    public ?DateTime $dataInicial;
    public ?DateTime $dataFinal;

    public function Header()
    {
        $this->SetFont('Arial', 'B', 16);

        $this->Cell(0, 6, mb_convert_encoding('Livro Razão', 'ISO-8859-1', 'UTF-8'), 0, 2, 'C');
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 6, mb_convert_encoding($this->razaoSocial, 'ISO-8859-1', 'UTF-8'), 0, 2, 'C');
        $this->SetFont('Arial', 'I', 14);
        $this->Cell(0, 6, mb_convert_encoding("CNPJ nº {$this->cnpj}", 'ISO-8859-1', 'UTF-8'), 0, 2, 'C');
        $this->Cell(0, 6, mb_convert_encoding("Período de {$this->dataInicial->format('d/m/Y')} até {$this->dataFinal->format('d/m/Y')}", 'ISO-8859-1', 'UTF-8'), 'B', 2, 'C');
    }

    public function Footer()
    {
        $this->SetFont('Arial', 'I', 12);
        $this->SetY(-15);
        $this->Cell(0, 10, mb_convert_encoding('pág. ', 'ISO-8859-1', 'UTF-8')."{$this->PageNo()} de {nb}", 'T', 0, 'R');
    }
}