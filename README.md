Payme
=====
Integration with payme payment services

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist uzdevid/yii2-payme "dev-main"
```

or add

```
"uzdevid/yii2-payme": "dev-main"
```

to the require section of your `composer.json` file.

Usage
=====

Create controller
-----------------

```php
use app\models\Account;
use app\models\Payme;
use app\models\User;
use uzdevid\payme\Merchant;
use Yii;

class PaymeController extends Merchant{
    public $login = 'Paycom';
    public $key = '<key>';
    public $userFind; // User model
    public $user_pk = 'id';
    
    public function __construct($id, $module, $config = []) {
        $this->userFind = User::find();
        parent::__construct($id, $module, $config);
    }
}
```

Method: [checkPerformTransaction](https://developer.help.paycom.uz/ru/metody-merchant-api/checkperformtransaction)

```php
public function checkPerformTransaction($payload) {
    $user_id = (int)$payload['params']['account']['user_id'];

    $isUser = User::findOne($user_id);

    if ($isUser === null)
        $data = ['result' => null, 'error' => ['code' => self::ERROR_INVALID_ACCOUNT, 'message' => Yii::t('content', 'user not found'), 'data' => null]];
    else
        $data = ['result' => ['allow' => true], 'error' => null];

    return parent::checkPerformTransaction($data);
}
 ```

Method: [createTransaction](https://developer.help.paycom.uz/ru/metody-merchant-api/createtransaction)

```php
public function createTransaction($payload) {
    $transaction = Payme::findOne(['transaction_id' => $payload['params']['id']]);

    if ($transaction === null) {
        $payme = new Payme;
        $payme->transaction_id = $payload['params']['id'];
        $payme->user_id = $payload['params']['account']['user_id'];
        $payme->state = self::STATE_CREATED;
        $payme->amount = $payload['params']['amount'];
        $payme->send_time = $payload['params']['time'];

        if ($payme->validate() && $payme->save())
            $data = ['result' => ['transaction' => (string)$payme->id, 'state' => $payme->state, 'create_time' => $payme->send_time]];
        else
            $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_PERFORM, 'message' => Yii::t('content', 'operation cannot be performed'), 'data' => null]];
    } else {
        if ($transaction->state != self::STATE_CREATED)
            $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_PERFORM, 'message' => Yii::t('content', 'transaction exist but is not active'), 'data' => null]];
        else
            $data = ['result' => ['transaction' => (string)$transaction->id, 'state' => $transaction->state, 'create_time' => $transaction->send_time]];
    }

    return parent::createTransaction($data);
}
 ```

Method: [performTransaction](https://developer.help.paycom.uz/ru/metody-merchant-api/performtransaction)

```php
public function performTransaction($payload) {
    $transaction = Payme::findOne(['transaction_id' => $payload['params']['id']]);

    if ($transaction === null) {
        $data = ['result' => null, 'error' => ['code' => self::ERROR_TRANSACTION_NOT_FOUND, 'message' => Yii::t('content', 'transaction not found'), 'data' => null]];
    } else {
        if ($transaction->state == self::STATE_CREATED) {
            if (time() * 1000 - strtotime($transaction->create_time) * 1000 > self::TIMEOUT) {
                $transaction->state = self::STATE_CANCELLED;
                $transaction->reason = self::REASON_CANCELLED_BY_TIMEOUT;
                $transaction->save();
            } else {
                $transaction->perform_time = time() * 1000;
                $transaction->state = self::STATE_COMPLETED;
                $transaction->save();

                $account = new Account;
                $account->user_id = $transaction->user_id;
                $account->transfer_id = $transaction->id;
                $account->amount = $transaction->amount / 100;
                $account->currency = 'UZS';
                $account->type = 'topup-payme';
                $account->status = 1;
                if ($account->save()) {
                    $data = ['result' => ['transaction' => (string)$transaction->id, 'perform_time' => strtotime($transaction->update_time) * 1000, 'state' => $transaction->state]];
                } else {
                    $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_PERFORM, 'message' => Yii::t('content', 'could not perform this operation'), 'data' => null]];
                }
            }
        } elseif ($transaction->state == self::STATE_COMPLETED) {
            $data = ['result' => ['transaction' => (string)$transaction->id, 'perform_time' => strtotime($transaction->update_time) * 1000, 'state' => $transaction->state]];
        } else {
            $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_PERFORM, 'message' => Yii::t('content', 'could not perform this operation'), 'data' => null]];
        }
    }

    return parent::performTransaction($data);
}
 ```

Method: [cancelTransaction](https://developer.help.paycom.uz/ru/metody-merchant-api/canceltransaction)

```php
public function cancelTransaction($payload) {
    $transaction = Payme::findOne(['transaction_id' => $payload['params']['id']]);

    if ($transaction === null) {
        $data = ['result' => null, 'error' => ['code' => self::ERROR_TRANSACTION_NOT_FOUND, 'message' => Yii::t('content', 'transaction not found'), 'data' => null]];
    } else {
        if (in_array($transaction->state, [self::STATE_CANCELLED, self::STATE_CANCELLED_AFTER_COMPLETE])) {
            $data = ['result' => ['transaction' => (string)$transaction->id, 'cancel_time' => $transaction->cancel_time, 'state' => $transaction->state]];
        } elseif ($transaction->state == self::STATE_CREATED) {
            $transaction->state = self::STATE_CANCELLED;
            $transaction->cancel_time = time() * 1000;
            $transaction->reason = (int)$payload['params']['reason'];

            if ($transaction->save()) {
                $data = ['result' => ['transaction' => (string)$transaction->id, 'cancel_time' => $transaction->cancel_time, 'state' => $transaction->state]];
            } else {
                $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_PERFORM, 'message' => Yii::t('content', 'could not perform this operation'), 'data' => null]];
            }
        } elseif ($transaction->state == self::STATE_COMPLETED) {
            $balance = $transaction->user->getBalance(false);

            if ($balance < ($transaction->amount / 100)) {
                $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_CANCEL, 'message' => Yii::t('content', 'there are not enough funds on the balance to cancel the transaction'), 'data' => null]];
            } else {
                $transaction->state = self::STATE_CANCELLED_AFTER_COMPLETE;
                $transaction->cancel_time = time() * 1000;
                $transaction->reason = (int)$payload['params']['reason'];

                if ($transaction->save()) {
                    $account = Account::findOne(['transfer_id' => $transaction->id]);
                    $account->status = -1;
                    if ($account->save())
                        $data = ['result' => ['transaction' => (string)$transaction->id, 'cancel_time' => $transaction->cancel_time, 'state' => $transaction->state]];
                    else
                        $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_PERFORM, 'message' => Yii::t('content', 'could not perform this operation'), 'data' => null]];
                } else {
                    $data = ['result' => null, 'error' => ['code' => self::ERROR_COULD_NOT_PERFORM, 'message' => Yii::t('content', 'could not perform this operation'), 'data' => null]];
                }
            }
        } else {
            $data = ['result' => ['transaction' => (string)$transaction->id, 'cancel_time' => $transaction->cancel_time, 'state' => $transaction->state]];
        }
    }

    return parent::cancelTransaction($data);
}
 ```

Method: [checkTransaction](https://developer.help.paycom.uz/ru/metody-merchant-api/checktransaction)

```php
public function checkTransaction($payload) {
    $transaction = Payme::findOne(['transaction_id' => $payload['params']['id']]);

    if ($transaction === null) {
        $data = ['result' => null, 'error' => ['code' => self::ERROR_TRANSACTION_NOT_FOUND, 'message' => Yii::t('content', 'transaction not found'), 'data' => null]];
    } else {
        $data = [
            'result' => [
                'create_time' => $transaction->send_time,
                'perform_time' => $transaction->perform_time,
                'cancel_time' => $transaction->cancel_time,
                'transaction' => (string)$transaction->id,
                'state' => $transaction->state,
                'reason' => $transaction->reason
            ]
        ];
    }

    return parent::checkTransaction($data);
}
 ```

Method: [getStatement](https://developer.help.paycom.uz/ru/metody-merchant-api/getstatement)

```php
public function getStatement($payload) {
    $transaction_models = Payme::find()->andFilterWhere(["<=", 'send_time', $payload['params']['from']])->andFilterWhere(["<=", 'send_time', $payload['params']['to']])->all();

    $transactions = [];
    foreach ($transaction_models as $transaction) {
        $transactions[] = [
            'id' => $transaction->transaction_id,
            'time' => $transaction->send_time,
            'amount' => $transaction->amount,
            'account' => [
                'user_id' => $transaction->user_id
            ],
            'create_time' => $transaction->send_time,
            'perform_time' => $transaction->perform_time,
            'cancel_time' => $transaction->cancel_time,
            'transaction' => (string)$transaction->id,
            'state' => $transaction->state,
            'reason' => $transaction->reason,
            'receivers' => null
        ];
    }

    $data = ['result' => ['transactions' => $transactions]];
    return parent::getStatement($data);
}
 ```
