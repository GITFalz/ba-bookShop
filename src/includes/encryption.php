<?php

if ( ! class_exists( 'BA_Encryption' ) ) {
    class BA_Encryption {

        private static $secret;
        private static $cipher = 'aes-256-gcm';

        public static function init( $secret ) {
            self::$secret = $secret;
        }

        public static function encrypt( $value ) {
            $iv  = random_bytes( openssl_cipher_iv_length( self::$cipher ) );
            $tag = '';

            $encrypted = openssl_encrypt(
                $value,
                self::$cipher,
                self::$secret,
                0,
                $iv,
                $tag  // GCM fills this in
            );

            // Store iv + tag + encrypted together so we can decrypt later
            return base64_encode( $iv . $tag . $encrypted );
        }

        public static function decrypt( $value ) {
            $decoded = base64_decode( $value );

            $iv_len  = openssl_cipher_iv_length( self::$cipher );
            $tag_len = 16;

            $iv        = substr( $decoded, 0, $iv_len );
            $tag       = substr( $decoded, $iv_len, $tag_len );
            $encrypted = substr( $decoded, $iv_len + $tag_len );

            return openssl_decrypt(
                $encrypted,
                self::$cipher,
                self::$secret,
                0,
                $iv,
                $tag
            );
        }
    }
}

BA_Encryption::init(hash('sha256', AUTH_KEY . SECURE_AUTH_KEY, true));