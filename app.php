<?php

require __DIR__ . '/vendor/autoload.php';

use Cart\Cart;

try {
    $cart = new Cart();
} catch (Exception $e) {
    die('Error: ' . $e->getMessage() . PHP_EOL);
}

$action = $argv[1] ?? '';

switch ($action) {
    case 'add':
        if (count($argv) !== 7) {
            echo "Usage: $action " . $cart->getHelp()[$action] . PHP_EOL;
            break;
        }

        try {
            $cart->addToCart($argv[2], $argv[4], (int)$argv[3], (float)$argv[5], $argv[6]);
        } catch (Exception $e) {
            echo 'Error: ' .  $e->getMessage() . PHP_EOL;
            break;
        }

        echo "Entry by SKU '" . $argv[2] . "' successfully added" . PHP_EOL;
        echo "Current cart total: " . $cart->countTotal() . PHP_EOL;
        break;
    case 'remove':
        if ( count($argv) !== 4 ) {
            echo "Usage: $action " . $cart->getHelp()[$action] . PHP_EOL;
            break;
        }

        try {
            $cart->removeFromCart($argv[2], (int)$argv[3]);
        } catch (Exception $e) {
            echo 'Error: ' .  $e->getMessage() . PHP_EOL;
            break;
        }
        
        echo "Entry by SKU '" . $argv[2] . "' successfully removed" . PHP_EOL;
        echo "Current cart total: " . $cart->countTotal() . PHP_EOL;
        break;
    case 'view':
        echo "Current cart total: " . $cart->countTotal() . PHP_EOL;
        break;
    default:
        echo 'Usage: ' . PHP_EOL;
        foreach( $cart->getHelp() as $a => $h ) {
            echo "   $a $h" . PHP_EOL;
        }
        break;
}