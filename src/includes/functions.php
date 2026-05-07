<?php

if ( ! class_exists( 'BA_Helper' ) ) {
    class BA_Helper {

        private $plugin_key;

        public function __construct( $plugin_key ) {
            $this->plugin_key      = $plugin_key;
        }



        # Basic functions
        public function get($key, $default = false) {
            $data = get_option($this->plugin_key);
            if (!$data)
                return false;

            return $data[$key] ?? $default;
        }

        public function update($key, $update) {
            $data = get_option($this->plugin_key);
            if (!$data)
                $data = [];

            $data[$key] = $update;
            update_option($this->plugin_key, $data);
            return true;
        }

        public function clear() {
            delete_option( $this->plugin_key );
        }



        # Special functions
        public function get_or_update_post($key, $default = false, $sanitize = 'sanitize_text_field') {
            if (!isset($_POST[$key]))
                return $this->get($key, $default);
            
            $value = $sanitize ? $sanitize($_POST[$key]) : $_POST[$key];
            $this->update($key, $value);
            return $value;
        }



        # Global functions
        public static function ba_encrypt($data) 
        {
            $key = hash('sha256', AUTH_KEY, true);
            $iv = random_bytes(16);
            $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv);
            return base64_encode($iv . $encrypted);
        }

        public static function ba_decrypt($data) 
        {
            $key = hash('sha256', AUTH_KEY, true);
            $data = base64_decode($data);
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            $result = openssl_decrypt( $encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv);
            if ($result === false) {
                throw new \RuntimeException('Decryption failed');
            }
        }

        public static function ba_get_post($name, $replacement = false)
        {
            return $_POST[$name] ?? $replacement;
        }

        public static function ba_get_post_or_default($name, $default)
        {
            return $_POST[$name] ?? $default;
        }

        public static function ba_is_post($name)
        {
            return isset($_POST[$name]);
        }
    }
}