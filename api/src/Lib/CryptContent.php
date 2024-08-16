<?php
declare(strict_types=1);
namespace App\Lib;

final class CryptContent {
    private $key, $method;

    public function __construct($id) {
        $private_key = 'TXVsdGlDaGF0UmFlbFJhZWxpdG8xOTgz';
        $this->key = $id . '==' . $private_key . '++' . $id;
        $this->method = "AES-256-CBC";
    }

    public function encrypt(string $data): string {
        // Define the secret key
        $key = $this->key;

        // Define the encryption method
        $method = $this->method;

        // Generate a random initialization vector (IV)
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

        // Encrypt the data
        $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);

        // Concatenate the IV and the encrypted data
        return base64_encode($iv.$encrypted);
    }

    public function decrypt($data) {
        if ($data !== null) {
            // Define the secret key
            $key = $this->key;

            // Define the encryption method
            $method = $this->method;

            // Decode the encrypted data
            $encrypted = base64_decode($data);

            // Extract the IV and the encrypted data
            $iv = substr($encrypted, 0, openssl_cipher_iv_length($method));
            $encrypted = substr($encrypted, openssl_cipher_iv_length($method));

            // Decrypt the data
            return openssl_decrypt($encrypted, $method, $key, 0, $iv);
        }

        return false;
    }
}