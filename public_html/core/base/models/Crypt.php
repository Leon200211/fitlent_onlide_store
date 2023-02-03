<?php


namespace core\base\models;


use core\base\controllers\Singleton;


// класс для шифрования данных
class Crypt
{

    use Singleton;


    private $cryptMethod = 'AES-128-CBC';
    private $hashAlgoritm = 'sha256';
    private $hashLength = 32;


    // метод для шифрования
    public function encrypt($str){

        // openssl_cipher_iv_length — Получает длину инициализирующего вектора шифра
        $ivlen = openssl_cipher_iv_length($this->cryptMethod);

        // openssl_random_pseudo_bytes — Генерирует псевдослучайную последовательность байт
        $iv = openssl_random_pseudo_bytes($ivlen);

        // openssl_encrypt — Шифрует данные
        $cipherText = openssl_encrypt($str, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);

        // hash_hmac — Генерация хеш-кода на основе ключа, используя метод HMAC
        $hmac = hash_hmac($this->hashAlgoritm, $cipherText, CRYPT_KEY, true);

        return base64_encode($iv . $hmac . $cipherText);

    }


     // метод для дешифрования
    public function decrypt($str){

        $crypt_str = base64_decode($str);

        $ivlen = openssl_cipher_iv_length($this->cryptMethod);

        // получаем вектор шифрования
        $iv = substr($crypt_str, 0, $ivlen);

        $hmac = substr($crypt_str, $ivlen, $this->hashLength);

        $cipher_text = substr($crypt_str, $ivlen + $this->hashLength);

        // openssl_decrypt — Расшифровывает данные
        $original_plaintext = openssl_decrypt($cipher_text, $this->cryptMethod, CRYPT_KEY, $options=OPENSSL_RAW_DATA, $iv);

        $calcmac = hash_hmac($this->hashAlgoritm, $cipher_text, CRYPT_KEY, $as_binary=true);

        // hash_equals — Сравнение строк, нечувствительное к атакам по времени
        if(hash_equals($hmac, $calcmac)){
            return $original_plaintext;
        }

        return false;

    }



}