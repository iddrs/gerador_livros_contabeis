<?php

function format_bytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return (string) (round($bytes, $precision) . ' ' . $units[$pow]);
}

function format_cc(string $cc): string
{
    return sprintf('%s.%s.%s.%s.%s.%s%s.%s%s.%s%s.%s%s.%s%s', ...str_split($cc, 1));
}