<?php

namespace uzdevid\payme\merchant\disposable;

use app\models\uysavdo\PaymeTransaction;
use uzdevid\payme\merchant\Merchant;
use uzdevid\payme\merchant\MerchantOptions;

/**
 * Class DisposableAccount
 * @package uzdevid\yii2-payme
 * @category Yii2 Extension
 * @version 1.0.0
 * @author UzDevid - Ibragimov Diyorbek
 * @license MIT
 *
 * @method orderClass()
 * @method transactionClass()
 */
class DisposableAccount extends Merchant {
    protected function checkAccount(array $payload): bool|array {
        if (in_array($payload['method'], ['CheckPerformTransaction', 'CreateTransaction'])) {
            $userExist = $this->orderClass()::find()->where(['id' => $payload['params']['account']['order_id']])->exists();
            if (!$userExist) {
                return $this->error(MerchantOptions::ERROR_INVALID_ACCOUNT, 'User not found');
            }
        }

        return true;
    }

    private function checkPerformTransaction(): array {
        return [];
    }

    private function createTransaction(): array {
        return [];
    }

    private function performTransaction(): array {
        return [];
    }

    private function cancelTransaction(): array {
        return [];
    }

    private function checkTransaction(): array {
        return [];
    }

    private function getStatement(): array {
        $from = $this->payload['params']['from'];
        $to = $this->payload['params']['to'];

        $transactions = array_map(function (PaymeTransaction $transaction) {
            return [
                'id' => $transaction->transaction_id,
                'time' => $transaction->create_time,
                'amount' => $transaction->amount,
                'account' => [
                    'order_id' => $transaction->order_id,
                ],
                'create_time' => $transaction->create_time,
                'perform_time' => (int)$transaction->perform_time,
                'cancel_time' => (int)$transaction->cancel_time,
                'transaction' => (string)$transaction->id,
                'state' => (int)$transaction->state,
                'reason' => $transaction->reason,
                'receivers' => [],
            ];
        }, $this->transactionClass()::find()->where(['>=', 'create_time', $from])->andWhere(['<=', 'create_time', $to])->all());

        return $this->success([
            'transactions' => $transactions,
        ]);
    }
}