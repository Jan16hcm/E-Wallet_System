<?php
function formatMoney(float $amount)
{
    return number_format($amount, 0, '.', ',') . ' VND';
}
?>