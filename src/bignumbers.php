<?php

function sumBigNumbers(string $a, string $b): ?string
{

    if (!preg_match('/^\d+$/', $a) || !preg_match('/^\d+$/', $b)) {
        return null;
    }

    $maxLength = max(strlen($a), strlen($b));

    $format = '%0' . $maxLength . 's';
    $a = sprintf($format, $a);
    $b = sprintf($format, $b);

    $aArr = str_split($a);
    $bArr = str_split($b);

    $result = array_fill(0, $maxLength + 1, 0);
    for ($i = $maxLength - 1; $i >= 0; --$i) {
        $sum = $aArr[$i] + $bArr[$i];
        $result[$i+1] += $sum % 10;
        if ($sum > 10) {
            ++$result[$i];
        }
    }

    return ltrim(implode('', $result), 0);
}