<?php

namespace App\Core;

class Auth
{
    private static function b64e_ahjr($data_ahjr)
    {
        return rtrim(strtr(base64_encode($data_ahjr), '+/', '-_'), '=');
    }
    private static function b64d_ahjr($data_ahjr)
    {
        return base64_decode(strtr($data_ahjr, '-_', '+/'));
    }
    private static function secret_ahjr()
    {
        $s_ahjr = getenv('JWT_SECRET');
        if (!$s_ahjr) {
            Response::serverError_ahjr('JWT_SECRET no configurado');
        }
        return $s_ahjr;
    }
    public static function issue_ahjr(array $claims_ahjr, int $ttl_ahjr = 7200)
    {
        $header_ahjr = ['typ' => 'JWT', 'alg' => 'HS256'];
        $now_ahjr = time();
        $payload_ahjr = $claims_ahjr + ['iat' => $now_ahjr, 'exp' => $now_ahjr + $ttl_ahjr];
        $hb_ahjr = self::b64e_ahjr(json_encode($header_ahjr));
        $pb_ahjr = self::b64e_ahjr(json_encode($payload_ahjr));
        $sig_ahjr = hash_hmac('sha256', $hb_ahjr . '.' . $pb_ahjr, self::secret_ahjr(), true);
        $sb_ahjr = self::b64e_ahjr($sig_ahjr);
        return $hb_ahjr . '.' . $pb_ahjr . '.' . $sb_ahjr;
    }
    public static function verify_ahjr(string $jwt_ahjr)
    {
        $parts_ahjr = explode('.', $jwt_ahjr);
        if (count($parts_ahjr) !== 3) {
            return null;
        }
        [$hb_ahjr, $pb_ahjr, $sb_ahjr] = $parts_ahjr;
        $sig_ahjr = self::b64e_ahjr(hash_hmac('sha256', $hb_ahjr . '.' . $pb_ahjr, self::secret_ahjr(), true));
        if (!hash_equals($sig_ahjr, $sb_ahjr)) {
            return null;
        }
        $payload_ahjr = json_decode(self::b64d_ahjr($pb_ahjr), true);
        if (!$payload_ahjr || !isset($payload_ahjr['exp']) || $payload_ahjr['exp'] < time()) {
            return null;
        }
        return $payload_ahjr;
    }
    public static function bearer_ahjr()
    {
        $h_ahjr = null;
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $h_ahjr = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (!$h_ahjr && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $h_ahjr = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        if (!$h_ahjr && isset($_SERVER['Authorization'])) {
            $h_ahjr = $_SERVER['Authorization'];
        }
        if (!$h_ahjr && function_exists('getallheaders')) {
            $all_ahjr = getallheaders();
            if (isset($all_ahjr['Authorization'])) {
                $h_ahjr = $all_ahjr['Authorization'];
            }
        }
        if (!$h_ahjr) {
            return null;
        }
        if (stripos($h_ahjr, 'Bearer ') === 0) {
            return trim(substr($h_ahjr, 7));
        }
        return null;
    }
}
