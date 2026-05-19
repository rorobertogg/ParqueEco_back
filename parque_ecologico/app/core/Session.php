<?php

class Session {

    public static function start() {

        if (session_status() === PHP_SESSION_NONE) {

            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');

            $secure = (
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            );

            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
        }
    }
}