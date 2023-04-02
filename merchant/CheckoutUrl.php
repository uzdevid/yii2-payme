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
    public static function generate(string $merchant_id, array $account, int $amount, array $params = []): string {
        $params = array_merge([
            'm' => $merchant_id,
            'ac' => $account,
            'a' => $amount
        ], $params);

        $token = base64_encode(http_build_query($params, '', ';'));
        return YII_ENV_DEV ? "https://test.paycom.uz/${token}" : "https://checkout.paycom.uz/${token}";
    }
}