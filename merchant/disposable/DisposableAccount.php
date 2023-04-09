<?php

namespace uzdevid\payme\merchant\disposable;

use app\models\uysavdo\PaymeTransaction;
use uzdevid\payme\merchant\Merchant;
use uzdevid\payme\merchant\MerchantOptions;
use Yii;

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
 * @method allowPay($order)
 * @method transactionCreated($order, $transaction)
 * @method transactionPerformed($order, $transaction)
 * @method allowRefund($order, $transaction)
 * @method refund($order, $transaction)
 */
class DisposableAccount extends Merchant {
    private $_order;

    protected function checkAccount(array $payload): bool|array {
        if (in_array($payload['method'], ['CheckPerformTransaction', 'CreateTransaction'])) {
            $orderExist = $this->orderClass()::find()->where(['id' => $payload['params']['account']['order_id']])->exists();
            if (!$orderExist) {
                return $this->error(MerchantOptions::ERROR_INVALID_ACCOUNT, 'Order not found');
            }
            $this->_order = $this->orderClass()::find()->where(['id' => $payload['params']['account']['order_id']])->one();
        }

        return true;
    }

    final protected function checkPerformTransaction(): array {
        if (!$this->allowPay($this->_order)) {
            return $this->error(MerchantOptions::ERROR_INVALID_ACCOUNT, 'The order is not available for payment');
        }

        if ($this->_order->amount != $this->payload['params']['amount']) {
            return $this->error(MerchantOptions::ERROR_INVALID_AMOUNT, 'Amount is not valid');
        }

        return $this->success(['allow' => true]);
    }

    final protected function createTransaction(): array {
        if ($this->_order->amount != $this->payload['params']['amount']) {
            return $this->error(MerchantOptions::ERROR_INVALID_AMOUNT, 'Amount is not valid');
        }

        $transactionId = $this->payload['params']['id'];
        $transaction = $this->transactionClass()::find()->where(['transaction_id' => $transactionId])->one();

        if ($transaction) {
            if ($transaction->state != MerchantOptions::STATE_CREATED) {
                return $this->error(MerchantOptions::ERROR_COULD_NOT_PERFORM, 'Transaction already performed');
            }

            if (((time() * 1000) - $this->payload['params']['time']) > MerchantOptions::TIMEOUT) {
                $transaction->state = MerchantOptions::STATE_CANCELLED;
                $transaction->reason = MerchantOptions::REASON_CANCELLED_BY_TIMEOUT;
                return $this->error(MerchantOptions::ERROR_COULD_NOT_PERFORM, 'Transaction timeout');
            }
        } else {
            if (!$this->allowPay($this->_order)) {
                return $this->error(MerchantOptions::ERROR_INVALID_ACCOUNT, 'The order is not available for payment');
            }

            $transaction = new ($this->transactionClass())();
            $transaction->transaction_id = $transactionId;
            $transaction->order_id = $this->payload['params']['account']['order_id'];
            $transaction->amount = $this->payload['params']['amount'];
            $transaction->create_time = time() * 1000;
            $transaction->state = MerchantOptions::STATE_CREATED;

            if (!$transaction->save()) {
                Yii::error('Transaction could not be saved. Errors: ' . json_encode($transaction->errors), 'uzdevid/yii2-payme');
                return $this->error(MerchantOptions::ERROR_COULD_NOT_PERFORM, 'Transaction could not be saved');
            }

            $this->transactionCreated($this->_order, $transaction);
        }


        return $this->success([
            'state' => $transaction->state,
            'create_time' => $transaction->create_time,
            'transaction' => (string)$transaction->id,
        ]);
    }

    final protected function performTransaction(): array {
        $transactionId = $this->payload['params']['id'];
        $transaction = $this->transactionClass()::find()->where(['transaction_id' => $transactionId])->one();

        if (!$transaction) {
            return $this->error(MerchantOptions::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
        }

        if ($transaction->state == MerchantOptions::STATE_CREATED) {
            if (((time() * 1000) - $transaction->create_time) > MerchantOptions::TIMEOUT) {
                $transaction->state = MerchantOptions::STATE_CANCELLED;
                $transaction->reason = MerchantOptions::REASON_CANCELLED_BY_TIMEOUT;
                $transaction->cancel_time = time() * 1000;

                if (!$transaction->save()) {
                    Yii::error('Transaction could not be saved. Errors: ' . json_encode($transaction->errors), 'uzdevid/yii2-payme');
                }

                return $this->error(MerchantOptions::ERROR_COULD_NOT_PERFORM, 'Transaction timeout');
            }

            $transaction->state = MerchantOptions::STATE_COMPLETED;
            $transaction->perform_time = time() * 1000;

            if (!$transaction->save()) {
                Yii::error('Transaction could not be saved. Errors: ' . json_encode($transaction->errors), 'uzdevid/yii2-payme');
                return $this->error(MerchantOptions::ERROR_COULD_NOT_PERFORM, 'Transaction could not be performed');
            }

            $order = $this->orderClass()::find()->where(['id' => $transaction->order_id])->one();

            $this->transactionPerformed($order, $transaction);
        } elseif ($transaction->state != MerchantOptions::STATE_COMPLETED) {
            return $this->error(MerchantOptions::ERROR_COULD_NOT_PERFORM, 'Transaction could not be performed');
        }

        return $this->success([
            'state' => $transaction->state,
            'perform_time' => (int)$transaction->perform_time,
            'transaction' => (string)$transaction->id,
        ]);
    }

    final protected function cancelTransaction(): array {
        $transactionId = $this->payload['params']['id'];
        $transaction = $this->transactionClass()::find()->where(['transaction_id' => $transactionId])->one();

        $order = $this->orderClass()::find()->where(['id' => $transaction->order_id])->one();

        if (!$transaction) {
            return $this->error(MerchantOptions::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
        }

        if (!$this->allowRefund($order, $transaction)) {
            return $this->error(MerchantOptions::ERROR_COULD_NOT_CANCEL, 'Transaction could not be cancelled');
        }

        if ($transaction->state == MerchantOptions::STATE_CREATED) {
            $transaction->state = MerchantOptions::STATE_CANCELLED;
            $transaction->reason = $this->payload['params']['reason'];
            $transaction->cancel_time = time() * 1000;

            if (!$transaction->save()) {
                Yii::error('Transaction could not be saved. Errors: ' . json_encode($transaction->errors), 'uzdevid/yii2-payme');
                return $this->error(MerchantOptions::ERROR_COULD_NOT_CANCEL, 'Transaction could not be cancelled');
            }

            return $this->success([
                'state' => $transaction->state,
                'cancel_time' => $transaction->cancel_time,
                'transaction' => (string)$transaction->id,
            ]);
        }

        if ($transaction->state != MerchantOptions::STATE_COMPLETED) {
            return $this->success([
                'state' => $transaction->state,
                'cancel_time' => $transaction->cancel_time,
                'transaction' => (string)$transaction->id,
            ]);
        }

        $transaction->state = MerchantOptions::STATE_CANCELLED_AFTER_COMPLETE;
        $transaction->reason = $this->payload['params']['reason'];
        $transaction->cancel_time = time() * 1000;

        if (!$transaction->save()) {
            Yii::error('Transaction could not be saved. Errors: ' . json_encode($transaction->errors), 'uzdevid/yii2-payme');
            return $this->error(MerchantOptions::ERROR_COULD_NOT_CANCEL, 'Transaction could not be cancelled');
        }

        $this->refund($order, $transaction);

        return $this->success([
            'state' => $transaction->state,
            'cancel_time' => $transaction->cancel_time,
            'transaction' => (string)$transaction->id,
        ]);
    }

    final protected function checkTransaction(): array {
        $transactionId = $this->payload['params']['id'];
        $transaction = $this->transactionClass()::find()->where(['transaction_id' => $transactionId])->one();

        if (!$transaction) {
            return $this->error(MerchantOptions::ERROR_TRANSACTION_NOT_FOUND, 'Transaction not found');
        }

        return $this->success([
            'create_time' => $transaction->create_time,
            'perform_time' => (int)$transaction->perform_time,
            'cancel_time' => (int)$transaction->cancel_time,
            'transaction' => (string)$transaction->id,
            'state' => (int)$transaction->state,
            'reason' => $transaction->reason,
        ]);
    }

    final protected function getStatement(): array {
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