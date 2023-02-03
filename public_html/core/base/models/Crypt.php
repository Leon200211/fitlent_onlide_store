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

        // модернизируем шифрование
        return $this->cryptCombine($cipherText, $iv, $hmac);

    }


     // метод для дешифрования
    public function decrypt($str){

        $ivlen = openssl_cipher_iv_length($this->cryptMethod);

        // модернизируем дешифрование
        $crypt_data = $this->cryptUnCombine($str, $ivlen);

        // openssl_decrypt — Расшифровывает данные
        $original_plaintext = openssl_decrypt($crypt_data['str'], $this->cryptMethod, CRYPT_KEY, $options=OPENSSL_RAW_DATA, $crypt_data['iv']);

        $calcmac = hash_hmac($this->hashAlgoritm, $crypt_data['str'], CRYPT_KEY, $as_binary=true);

        // hash_equals — Сравнение строк, нечувствительное к атакам по времени
        if(hash_equals($crypt_data['hmac'], $calcmac)){
            return $original_plaintext;
        }

        return false;

    }



    // метод модернизации системы шифрования
    protected function cryptCombine($str, $iv, $hmac){

        $new_str = '';
        $str_len = strlen($str);

        $counter = (int)ceil(strlen(CRYPT_KEY) / ($str_len + $this->hashLength));

        $progress = 1;

        if($counter >= $str_len){
            $counter = 1;
        }


        // проходимся по всей строке
        for($i = 0; $i < $str_len; $i++){

            if($counter < $str_len){
                // если коретка дошла до нужной позиции, вставить подстроку
                if($counter === $i){
                    $new_str .= substr($iv, $progress - 1, 1);
                    $progress++;
                    $counter += $progress;
                }
            }else{
                break;
            }

            // вставляем один символ из исходной строки
            $new_str .= substr($str, $i, 1);

        }

        // Дополняем строку до полного размера
        $new_str .= substr($str, $i);  // добавляем остатки строки
        $new_str .= substr($iv, $progress - 1);  // добавляем остатки вектора шифрования

        // добавляем hmac в центр строки
        $new_str_half = (int)ceil(strlen($new_str) / 2);
        $new_str = substr($new_str, 0, $new_str_half) . $hmac . $new_str = substr($new_str, $new_str_half);

        return base64_encode($new_str);

    }


    // метод для модернизации дешифрования
    protected function cryptUnCombine($str, $ivlen){

        $crypt_data = [];

        $str = base64_decode($str);

        $hash_position = (int)ceil(strlen($str) / 2 - $this->hashLength / 2);

        $crypt_data['hmac'] = substr($str, $hash_position, $this->hashLength);

        $str = str_replace($crypt_data['hmac'], '', $str);

        $counter = (int)ceil(strlen(CRYPT_KEY) / (strlen($str) - $ivlen + $this->hashLength));

        // если короткая строка
        if($counter >= strlen($str) - $ivlen){
            $counter = 1;
        }

        $progress = 2;

        $crypt_data['str'] = '';
        $crypt_data['iv'] = '';

        for($i = 0; $i < strlen($str); $i++){

            if($ivlen + strlen($crypt_data['str']) < strlen($str)){

                if($i === $counter){
                    $crypt_data['iv'] .= substr($str, $counter, 1);
                    $progress++;
                    $counter += $progress;
                }else{
                    $crypt_data['str'] .= substr($str, $i, 1);
                }

            }else{
                $crypt_data_len = strlen($crypt_data['str']);
                $crypt_data['str'] .= substr($str, $i, strlen($str) - $ivlen - $crypt_data_len);
                $crypt_data['iv'] .= substr($str, $i + (strlen($str) - $ivlen - $crypt_data_len));

                break;
            }

        }

        return $crypt_data;

    }


}