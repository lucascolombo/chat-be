<?php
declare(strict_types=1);
namespace App\Lib;

final class Encrypt
{

    public static function encode( $q ) {
        $number = $q . "";

        $digitMap = [
            '0' => ['Z', 'B', 'J', 'C', 'D', 'Q', 'W', 'T', 'P', 'H', 'K', 'X', 'V', 'N', 'M'], 
            '1' => 'E', 
            '2' => '2', 
            '3' => 'F',
            '4' => 'Y',
            '5' => '5', 
            '6' => 'R', 
            '7' => 'L', 
            '8' => '8', 
            '9' => 'G',
        ];
    
        $number = str_pad($number, 8, '0', STR_PAD_LEFT);
        $encryptedCode = '';
    
        for ($i = 0; $i < 8; $i++) {
            $digit = $number[$i];
            if ($digit === '0') {
                $encryptedCode .= $digitMap[$digit][array_rand($digitMap[$digit])];
            } else {
                $encryptedCode .= $digitMap[$digit];
            }
        }
    
        return strtolower($encryptedCode);
    }

    public static function decode( $q ) {
        $code = strtoupper($q . "");

        $digitMap = [
            'Z' => '0',
            'B' => '0',
            'J' => '0',
            'C' => '0',
            'D' => '0',
            'Q' => '0',
            'W' => '0',
            'T' => '0',
            'P' => '0',
            'H' => '0',
            'K' => '0',
            'X' => '0',
            'V' => '0',
            'N' => '0',
            'M' => '0',

            'E' => '1',
            '2' => '2',
            'F' => '3',
            'Y' => '4',
            '5' => '5',
            'R' => '6',
            'L' => '7',
            '8' => '8',
            'G' => '9',
        ];
    
        $decryptedNumber = '';
        for ($i = 0; $i < strlen($code); $i++) {
            $char = $code[$i];
            $decryptedNumber .= $digitMap[$char];
        }
    
        return ltrim($decryptedNumber, '0');
    }
}