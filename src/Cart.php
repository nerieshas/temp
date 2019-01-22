<?php

namespace Cart;

use Exception;

class Cart
{
    const CART_FILE = 'tmp/cart.txt';
    const DEFAULT_CURRENCY = 'EUR'; 
    const CURRENCIES = [
        'EUR' => 1.00,
        'GBP' => 0.88, 
        'USD' => 1.14
    ];

    private $entries = [];

    function __construct()
    {
        if (!$this->fileExistsAndWritable()) {
            throw new Exception('CART_FILE not found or it is not writable');
        }

        $this->loadData();
    }

    public function fileExistsAndWritable(): bool
    {
        return file_exists(self::CART_FILE) && is_writable(self::CART_FILE); 
    }

    public function isDefaultCurrency(String $currency): bool
    {
        return $currency === self::DEFAULT_CURRENCY;
    }

    public function loadData(): void
    {
        $file = file(self::CART_FILE);

        foreach ($file as $single) {
            if (empty(trim($single))) {
                continue;
            }
            $temp = explode(';', $single);
            $tmp = $temp[1];
            $temp[1] = $temp[2];
            $temp[2] = $tmp;
            $temp = $this->sanitizeEntry($temp);

            $this->addEntry($temp);
        }
    }

    public function convertToDefaultCurrency(Int $amount, $currency) 
    {
        if ($amount <= 0) {
            throw new Exception('Currency parameter invalid');   
        }

        if (!array_key_exists($currency, self::CURRENCIES)) {
            throw new Exception('Currency parameter invalid');   
        }

        if ($this->isDefaultCurrency($currency)) {
            return $amount;
        }

        $currencies = self::CURRENCIES;
        $amount = round($amount * 100 / $currencies[$currency] / 100, 2);

        return $amount;
    }

    public function addToCart($sku, $desc, Int $qty, Float $price, $curr): void
    {
        if ($qty <= 0) {
            throw new Exception('Quantity parameter invalid.');   
        }

        if ($price <= 0) {
            throw new Exception('Price parameter invalid.');   
        }

        $curr = strtoupper($curr);

        if (!array_key_exists($curr, self::CURRENCIES)) {
            throw new Exception('Currency parameter invalid');   
        }
 
        $entry = $this->sanitizeEntry([$sku, $qty, $desc, $price, $curr]);
        $this->addEntry($entry);

        $this->writeToFile( $sku, $qty, $desc, $price, $curr );
 
    }

    public function removeFromCart($sku, Int $qty): void
    {
        if ($qty <= 0) {
            throw new Exception('Quantity parameter invalid.');   
        }

        $entries = $this->getEntriesBySku();

        if (empty($entries[$sku])) {
            throw new Exception('SKU not found.');
        }

        $stock = array_sum(array_column($entries[$sku], 0));

        if ($stock < $qty) {
            throw new Exception('Not enough in stock.');
        }
        
        $qty = -1 * abs($qty);
        
        $entry = $this->sanitizeEntry([$sku, $qty]);

        $this->writeToFile( $sku, $qty );
        $this->addEntry($entry);

    }
    
    public function writeToFile($sku, $qty, $desc = '', $price = '', $curr = '') 
    {
        $file = fopen(self::CART_FILE, 'a');

        fwrite($file, "$sku;$desc;$qty;$price;$curr" . PHP_EOL);
        fclose($file);
        
    }

    public function getEntriesBySku(): array
    {
        $entries = $this->getEntries();
        $output = [];

        foreach ($entries as $entry) {
            $sku = array_shift($entry);
            $output[$sku][] = $entry;
        }

        return $output;
    }
    
    public function getRemainingEntriesBySku(): array
    {
        
        $skus = $this->getEntriesBySku();
        
        $rem = array_combine( array_keys($skus), array_fill( 0, count($skus), 0 ) );
        $return = array_combine( array_keys($skus), array_fill( 0, count($skus), array() ) );
        
        foreach ($skus as $sku => $entries) {
            foreach ($entries as $single) {
                if ( $single[0] < 0 ) {
                    $rem[ $sku ] += abs($single[0]);
                }
            }
        }
        
        foreach ($skus as $sku => $entries) {
            foreach ($entries as $single) {
                
                if ( $single[0] < 0 ) {
                    continue;
                }
                
                if ( $rem[ $sku ] == 0 ) {
                    $return[ $sku ][] = $single;
                    continue;
                }
                
                if ( $rem[ $sku ] >= $single[0] ) {
                    $rem[ $sku ] -= $single[0];
                    continue;
                }
                
                $single[0] -= $rem[ $sku ];
                $return[ $sku ][] = $single;
                $rem[ $sku ] = 0;
            }
        }

        return $return;
        
    }

    public function sanitizeEntry(Array $entry): array
    {
        $entry = array_map('trim', $entry);
        
        if (empty($entry[0])) {
            throw new Exception('No SKU');
        }
        
        if (!isset($entry[1]) || (int)$entry[1] === 0) {
            throw new Exception('Quantity should not be zero');
        }
        
        $entry[1] = (int)$entry[1];
        
        if ($entry[1] <= 0) {
            $entry = array_slice( $entry, 0, 2 );
        } else {

            $entry[3] = (float)$entry[3];
            $entry[4] = strtoupper($entry[4]);
            
            if ( $entry[3] <= 0 ) {
                throw new Exception('Price should not be zero');
            }
            
            if (!array_key_exists( $entry[4], self::CURRENCIES)) {
                throw new Exception('Currency should be one of the following: ' . implode(', ', self::CURRENCIES)); 
            }

            if (!$this->isDefaultCurrency($entry[4]) && $entry[3] > 0) {
                $entry[3] = $this->convertToDefaultCurrency($entry[3], $entry[4]);
                $entry[4] = self::DEFAULT_CURRENCY;
            }
        }

        return $entry;
    }

    public function addEntry(Array $entry): void
    {
        $this->entries[] = $entry;
    }
    
    public function getEntries(): array
    {
        return $this->entries;
    }

    public function countTotal(): float
    {
        $skus = $this->getRemainingEntriesBySku();
        
        $total = 0;
        
        foreach ($skus as $sku => $entries) {
            foreach ($entries as $single) {
                $total += $single[0] * $single[2];
            }
        }

        return $total;
    }
    
    public function getHelp(): array
    {
        return [
            'add' => 'SKU QUANTITY DESCRIPTION PRICE CURRENCY',
            'remove' => 'SKU QUANTITY',
            'view' => '',
            'help' => '',
        ];
    }
}