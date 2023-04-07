<?php

namespace uzdevid\payme\merchant;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Class Merchant
 * @package uzdevid\yii2-payme
 * @category Yii2 Extension
 * @version 1.0.0
 * @author UzDevid - Ibragimov Diyorbek
 * @license MIT
 *
 * @method checkPerformTransaction()
 * @method createTransaction()
 * @method performTransaction()
 * @method cancelTransaction()
 * @method checkTransaction()
 * @method getStatement()
 */
class Merchant extends Controller {
    protected array $payload = [];

    public function init(): void {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->payload = json_decode(Yii::$app->request->rawBody, true);
        parent::init();
    }

    public function actionIndex(): array {
        if (($error = $this->checkAuthorization()) !== true) {
            return $error;
        }

        if (($error = $this->checkAccount($this->payload)) !== true) {
            return $error;
        }

        return $this->callMethod($this->payload['method']);
    }

    protected function checkAuthorization(): bool|array {
        $headers = getallheaders();

        if (!$headers || !isset($headers['Authorization']) || !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches) || base64_decode($matches[1]) != $this->login . ":" . $this->key) {
            return $this->error(MerchantOptions::ERROR_INSUFFICIENT_PRIVILEGE, 'Insufficient privilege to perform this method');
        }

        return true;
    }

    protected function callMethod(string $method): array {
        return match ($method) {
            'CheckPerformTransaction' => $this->checkPerformTransaction(),
            'CreateTransaction' => $this->createTransaction(),
            'PerformTransaction' => $this->performTransaction(),
            'CancelTransaction' => $this->cancelTransaction(),
            'CheckTransaction' => $this->checkTransaction(),
            'GetStatement' => $this->getStatement(),

            default => $this->error(MerchantOptions::ERROR_METHOD_NOT_FOUND, 'Method not found'),
        };
    }

    protected function success($result): array {
        return [
            'id' => $this->payload['id'] ?? null,
            'jsonrpc' => '2.0',
            'result' => $result,
            'error' => null,
        ];
    }

    protected function error(int $code, string $message): array {
        return [
            'id' => $this->payload['id'] ?? null,
            'jsonrpc' => '2.0',
            'result' => null,
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => null,
            ],
        ];
    }
}