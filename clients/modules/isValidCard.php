<?php 
    include_once("db_connection.php");
    include_once("isValidDate.php");

    define('CREDIT_CARDS', [
        '111111' => ['expire' => '10/10/2022', 'cvv' => '411', 'limit' => null,    'always_fail' => false],
        '222222' => ['expire' => '11/11/2022', 'cvv' => '443', 'limit' => 1000000, 'always_fail' => false],
        '333333' => ['expire' => '12/12/2022', 'cvv' => '577', 'limit' => null,    'always_fail' => true],
    ]);
    define('CARRIERS', [
        'Viettel'  => '11111',
        'Mobifone' => '22222',
        'Vinaphone'=> '33333',
    ]);
    define('CARD_DENOMINATIONS', [10000, 20000, 50000, 100000]);

    function isValidDepositCard(string $card_num, string $expire, string $cvv, float $amount){
    //return error string, empty string if success
    // Format check
        if (!preg_match('/^\d{6}$/', $card_num)) {
            return 'Card number must be exactly 6 digits';
        }
        if (!isValidDate($expire)) {
            return 'Invalid expire date';
        }
        if (!preg_match('/^\d{3}$/', $cvv)) {
            return 'CVV must be exactly 3 digits';
        }

        $cards = CREDIT_CARDS;
        if (!isset($cards[$card_num])) {
            return 'This card is not supported.';
        }
        $card = $cards[$card_num];

        if ($card['expire'] != $expire) {//card with no s
            return 'Invalid expiration date';
        }
        if ($card['cvv'] != $cvv) {
            return 'Invalid CVV code';
        }
        if ($card['always_fail']) {
            return 'This card is out of money';
        }
        if ($card['limit'] != null && $amount > $card['limit']) {
            return 'This card can only be loaded up to ' . formatMoney($card['limit']) . ' per transaction';
        }
        return '';//no error
    }

    function isValidWithdrawCard(string $card_num, string $expire, string $cvv){
        //all for one card
        if ($card_num != '111111') {
            return 'This card is not supported for withdrawal';
        }
        if ($expire != '10/10/2022' || $cvv != '411') {
            return 'Invalid card information';
        }
        return '';
    }
?>