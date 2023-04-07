<?php

namespace uzdevid\payme\merchant;

/**
 * Class CheckoutUrl
 * @package uzdevid\yii2-payme
 * @category Yii2 Extension
 * @version 1.0.0
 * @author UzDevid - Ibragimov Diyorbek
 * @license MIT
 */
class CheckoutUrl {
    public const TEST_ENDPOINT = 'https://test.paycom.uz/';
    public const PRODUCTION_ENDPOINT = 'https://checkout.paycom.uz/';

    /**
     * @param string $merchant_id
     * @param array $account
     * @param int $amount
     * @param array $params
     * @return string
     */
    public static function generate(string $merchant_id, array $account, int $amount, array $params = []): string {
        $params = array_merge([
            'm' => $merchant_id,
            'ac' => $account,
            'a' => $amount
        ], $params);

        $params = http_build_query($params, '', ';');

        $params_str = str_replace(["%5B", "%5D"], ['.', ''], $params);

        $token = base64_encode($params);
        $url = YII_ENV_DEV ? self::TEST_ENDPOINT : self::PRODUCTION_ENDPOINT;

        return $url . $token;
    }
}