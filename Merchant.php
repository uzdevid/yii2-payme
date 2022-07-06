<?php

namespace uzdevid\payme;

use Yii;
use yii\web\Controller;
use yii\web\Response;

class Merchant extends Controller {
    public $payload;

    const ERROR_INTERNAL_SYSTEM = -32400;
    const ERROR_INSUFFICIENT_PRIVILEGE = -32504;
    const ERROR_INVALID_JSON_RPC_OBJECT = -32600;
    const ERROR_METHOD_NOT_FOUND = -32601;
    const ERROR_INVALID_AMOUNT = -31001;
    const ERROR_TRANSACTION_NOT_FOUND = -31003;
    const ERROR_INVALID_ACCOUNT = -31050;
    const ERROR_COULD_NOT_CANCEL = -31007;
    const ERROR_COULD_NOT_PERFORM = -31008;

    const STATE_CREATED = 1;
    const STATE_COMPLETED = 2;
    const STATE_CANCELLED = -1;
    const STATE_CANCELLED_AFTER_COMPLETE = -2;

    const REASON_RECEIVERS_NOT_FOUND = 1;
    const REASON_PROCESSING_EXECUTION_FAILED = 2;
    const REASON_EXECUTION_FAILED = 3;
    const REASON_CANCELLED_BY_TIMEOUT = 4;
    const REASON_FUND_RETURNED = 5;
    const REASON_UNKNOWN = 10;

    const TIMEOUT = 43200000;

    public function actionIndex() {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $this->payload = json_decode(file_get_contents('php://input'), true);

        if (($auth = $this->authorization()) !== true)
            return $auth;

        if (in_array($this->payload['method'], ['CheckPerformTransaction', 'CreateTransaction', 'ChangePassword']))
            if (($checkUser = $this->checkUser()) !== true)
                return $checkUser;

        switch ($this->payload['method']) {
            case 'CheckPerformTransaction':
                $this->checkPerformTransaction($this->payload);
                break;
            case 'CreateTransaction':
                $this->createTransaction($this->payload);
                break;
            case 'PerformTransaction':
                $this->performTransaction($this->payload);
                break;
            case 'CancelTransaction':
                $this->cancelTransaction($this->payload);
                break;
            case 'CheckTransaction':
                $this->checkTransaction($this->payload);
                break;
            case 'ChangePassword':
                $this->changePassword($this->payload);
                break;
            case 'GetStatement':
                $this->getStatement($this->payload);
                break;
            default:
                //error
                break;
        }
    }

    protected function authorization() {
        $headers = getallheaders();

        if (!$headers || !isset($headers['Authorization']) || !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $headers['Authorization'], $matches) || base64_decode($matches[1]) != $this->login . ":" . $this->key) {
            $data = [
                'id' => isset($this->payload['id']) ? $this->payload['id'] : null,
                'jsonrpc' => '2.0',
                'result' => null,
                'error' => [
                    'code' => self::ERROR_INSUFFICIENT_PRIVILEGE,
                    'message' => Yii::t('content', 'insufficient privilege to perform this method'),
                    'data' => null
                ]
            ];
            return $this->asJson($data);
        }

        return true;
    }

    protected function checkUser() {
        $user = $this->userFind->where([$this->user_pk => (int)$this->payload['params']['account']['user_id']])->one();
        if ($user === null) {
            $data = [
                'id' => isset($this->payload['id']) ? $this->payload['id'] : null,
                'jsonrpc' => '2.0',
                'result' => null,
                'error' => [
                    'code' => self::ERROR_INVALID_ACCOUNT,
                    'message' => Yii::t('content', 'user not found'),
                    'data' => null
                ]
            ];
            return $this->asJson($data);
        }

        return true;
    }

    public function checkPerformTransaction($result) {
        $result['jsonrpc'] = '2.0';
        return $this->asJson($result);
    }

    public function createTransaction($result) {
        $result['jsonrpc'] = '2.0';
        $result['id'] = isset($this->payload['id']) ? $this->payload['id'] : null;
        return $this->asJson($result);
    }

    public function performTransaction($result) {
        $result['jsonrpc'] = '2.0';
        $result['id'] = isset($this->payload['id']) ? $this->payload['id'] : null;
        return $this->asJson($result);
    }

    public function cancelTransaction($result) {
        $result['jsonrpc'] = '2.0';
        $result['id'] = isset($this->payload['id']) ? $this->payload['id'] : null;
        return $this->asJson($result);
    }

    public function checkTransaction($result) {
        $result['jsonrpc'] = '2.0';
        $result['id'] = isset($this->payload['id']) ? $this->payload['id'] : null;
        return $this->asJson($result);
    }

    public function getStatement($result) {
        $result['jsonrpc'] = '2.0';
        $result['id'] = isset($this->payload['id']) ? $this->payload['id'] : null;
        return $this->asJson($result);
    }

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }
}