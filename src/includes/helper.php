<?php

if ( ! class_exists( 'BA_Helper' ) ) {
    class BA_Helper {

        private $plugin_key;

        public function __construct( $plugin_key ) {
            $this->plugin_key      = $plugin_key;
        }

        # Basic functions
        public function get($key, $default = false) {
            return get_option($this->key($key), $default);
        }

        public function update($key, $update) {
            update_option($this->key($key), $update);
            return true;
        }

        public function clear($key) {
            delete_option($this->key($key));
        }

        private function key($key) {
            return $this->plugin_key . "_" . $key;
        }



        # Special functions
        public function get_decrypted($key, $default = false) {
            try {
                $value = $this->get($key);
                if (!$value) return $default;
                return BA_Encryption::decrypt($value);
            } catch (RuntimeException) {
                return $default;
            }
        }

        public function update_encrypted($key, $update) {
            return $this->update($key, BA_Encryption::encrypt($update));
        }


        public function get_or_update_post($key, $default = false, $sanitize = 'sanitize_text_field') {
            if (!isset($_POST[$key]))
                return $this->get($key, $default);
            
            $value = $sanitize ? $sanitize($_POST[$key]) : $_POST[$key];
            $this->update($key, $value);
            return $value;
        }

        public function get_or_update_post_encrypted($key, $default = false, $sanitize = 'sanitize_text_field') {
            if (!isset($_POST[$key])) {
                $value = $this->get($key, $default);
                if ($value) {
                    try {
                        return BA_Encryption::decrypt($value);
                    } catch (RuntimeException) {
                        return $default;
                    }
                }
                return $default;
            }

            $value = $sanitize ? $sanitize($_POST[$key]) : $_POST[$key];
            $this->update($key, BA_Encryption::encrypt($value));
            return $value;
        }



        # Global functions
        public static function get_post($name, $replacement = false)
        {
            return $_POST[$name] ?? $replacement;
        }

        public static function is_post($name)
        {
            return isset($_POST[$name]);
        }
    }
}