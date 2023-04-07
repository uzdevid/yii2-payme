Merchant API protokoli - Jamg'arma hisobi
----------------------------------------

### Foydalanuvchi tizimdagi o'z balansini to'ldirish uchun ishlatilinadi.

---

1. **Ma'lumotlarni saqlash uchun `user`, `payme_transaction` va `transaction` jadvalini yaratish.**

Quyida keltirilgan jadvallarning ma'lumotlarini tizimingizning texnik ehtiyojiga moslashtirishingiz mumkin. 
Ammo `shart` deb belgilangan maydonlarni yaratish shart. Aks holda kutilmagan xatoliklar yuz beradi. Qo'shimcha ma'lumotlar uchun maydonlar yaratishingiz mumkin.

---

Foydalanuvchidan eng kamida kerak bo'ladigan ma'lumotlar.

- `id` | `[shart]` - Foydalanuvchi identifikatori.
- `name` | `[ixtiyoriy]` - Foydalanuvchi ismi.

```sql
CREATE TABLE IF NOT EXISTS `user` (
  `id` INT UNIQUE NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NULL,
  PRIMARY KEY (`id`));
```

----

Payme orqali kelayotgan tranzaksiyalar jadvali. Ushbu jadvalning nomini o'zgartirishingiz mumkin, 
ammo ma'lumotlarining nomini o'zgartirsangiz kengaytmada kutilmagan xatoliklar yuz beradi.

- `id` | `[shart]` - Tranzaksiya ID.
- `user_id` | `[shart]` - Foydalanuvchi ID.
- `transaction_id` | `[shart]` - Payme tomonidan berilgan tranzaksiya ID.
- `state` | `[shart]` - Tranzaksiya holati.
- `amount` | `[shart]` - Tranzaksiya summasi (tiyinda).
- `reason` | `[shart]` - Tranzaksiya sababi.
- `perform_time` | `[shart]` - Tranzaksiya amalga oshirilgan vaqti.
- `cancel_time` | `[shart]` - Tranzaksiya bekor qilingan vaqti.
- `create_time` | `[shart]` - Tranzaksiya yaratilgan vaqti.

```sql
CREATE TABLE IF NOT EXISTS `payme_transaction` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NULL DEFAULT NULL,
  `transaction_id` VARCHAR(24) NOT NULL,
  `state` TINYINT(2) NOT NULL,
  `amount` INT NOT NULL,
  `reason` TINYINT(2) NULL DEFAULT NULL,
  `perform_time` BIGINT(16) NULL DEFAULT NULL,
  `cancel_time` BIGINT(16) NULL DEFAULT NULL,
  `create_time` BIGINT(16) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC),
  UNIQUE INDEX `transaction_id_UNIQUE` (`transaction_id` ASC));
```

----

Foydalanuvchilarning tranzaksiyalari saqlanadigan jadval. 
Ushbu jadval kengaytma bilan to'g'ridan to'g'ri bog'lik emas, balki u callback methodlar orqali ishlatilinadi, 
shu bois to'liq ixtiyoriy, bu jadvalni yaratishdan maqsad foydalanuvchilarni jamg'arma hisobiga mablag'larini to'ldirish, 
chiqarish, qaytarish. 
Bu jadvalni o'rniga o'zingizga moslashtirilgan jadval yaratishingiz mumkin. 
Quyida keltiriladigan misollar uchun aynan shu jadval ishlatilinadi.

- `id` | `[ixtiyoriy]` - Tranzaksiya ID.
- `user_id` | `[ixtiyoriy]` - Foydalanuvchi ID.
- `source` | `[ixtiyoriy]` - Tranzaksiya manbai (Payme, Click, Uzumbank va h.k.z).
- `source_id` | `[ixtiyoriy]` - Tranzaksiya manbai ID.
- `amount` | `[ixtiyoriy]` - Tranzaksiya summasi (tiyinda).
- `type` | `[ixtiyoriy]` - Tranzaksiya turi (kirim, chiqib, to'lov, qaytarilgan va h.k.z).
- `details` | `[ixtiyoriy]` - Tranzaksiya haqida qo'shimcha ma'lumot.
- `create_time` | `[ixtiyoriy]` - Tranzaksiya yaratilgan vaqti.

```sql
CREATE TABLE IF NOT EXISTS `transaction` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `source` VARCHAR(24) NOT NULL,
    `source_id` INT(11) NOT NULL,
    `amount` INT NOT NULL,
    `type` VARCHAR(24) NOT NULL,
    `details` LONGTEXT NULL DEFAULT NULL,
    `create_time` BIGINT(16) NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `fk_transaction_user_id_idx` (`user_id` ASC),
    CONSTRAINT `fk_transaction_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);
```

2. **Controller yaratish va unga `SavingsAccount` klassidan me'ros olish va `SavingsControllerInterface` interfeysini qo'shish.**

- `{payme kaliti}` - Payme tomonidan beriladigan kalit.

```php
namespace app\controllers;

use uzdevid\payme\merchant\savings\SavingsAccount;
use uzdevid\payme\merchant\savings\SavingsControllerInterface;

class PaymeController extends SavingsAccount implements SavingsControllerInterface {
    public function init(): void {
        $this->key = "{payme kaliti}";
        parent::init();
    }
}
```

3. `SavingsControllerInterface` interfeysi talab qilgan methodlarni yozish

- `userClass()` - Foydalanuvchi modeli klassini qaytarishi lozim. Misol uchun `app\models\User:class`
- `transactionClass()` - Foydalanuvchi jamg'arma hisobi transaksiyasi modeli klassi qaytarishi lozim.
- `checkAmount()` - Foydalanuvchi to'lov qilmoqchi bo'lgan summasini tekshirish (tiyinda).
- `allowTransaction()` - Foydalanuvchi to'lov qilishga ruxsat berish. Agar ruxsat berilmagan bo'lsa `false` qaytaradi aks holda `true`.
- `transactionCreated()` - Foydalanuvchi to'lov qilgandan so'ng ishga tushadigan method. Bu methodda foydalanuvchi jamg'arma hisobiga to'lov summasini 'kirim' turi bilan saqlash lozim.
- `allowRefund()` - Foydalanuvchi to'lovni qaytarishga ruxsat berish. Agar ruxsat berilmagan bo'lsa `false` qaytaradi aks holda `true`.
- `userBalance()` - Foydalanuvchi balansini (tiyinda) qaytarishi lozim. To'lov ortga qaytarilish jarayonida ushbu balans bilan qaytariladigan summa tekshiriladi. Agar balans yetarli bo'lsa foydalanuvchiga to'lov qaytariladi.
- `refund()` - Foydalanuvchiga to'lov qaytarilganidan so'ng ishga tushadigan method. Bu methodda foydalanuvchi jamg'arma hisobidan qaytarilgan summani 'qaytarildi' turi bilan saqlash lozim.


```php
namespace app\controllers;

use uzdevid\payme\merchant\savings\SavingsAccount;
use uzdevid\payme\merchant\savings\SavingsControllerInterface;

class PaymeController extends SavingsAccount implements SavingsControllerInterface {
    
    public function init(): void {
        $this->key = "{payme kaliti}";
        parent::init();
    }

    public function userClass(): string {
        // TODO: Implement userClass() method.
    }

    public function transactionClass(): string {
        // TODO: Implement transactionClass() method.
    }

    public function userBalance(int $userId): int {
        // TODO: Implement userBalance() method.
    }

    public function checkAmount(int $amount): bool {
        // TODO: Implement checkAmount() method.
    }

    public function allowTransaction(array $payload): bool {
        // TODO: Implement allowTransaction() method.
    }

    public function transactionCreated($transaction): void {
        // TODO: Implement transactionCreated() method.
    }

    public function allowRefund($transaction): bool {
        // TODO: Implement allowRefund() method.
    }

    public function refund($transaction): void {
        // TODO: Implement refund() method.
    }
}
```

4. To'liq misol:

```php
namespace app\controllers;

use uzdevid\payme\merchant\savings\SavingsAccount;
use uzdevid\payme\merchant\savings\SavingsControllerInterface;

class PaymeController extends SavingsAccount implements SavingsControllerInterface {

    public function init(): void {
        $this->key = "{payme kaliti}";
        parent::init();
    }

    public function userClass(): string {
        return User::class;
    }

    function transactionClass(): string {
        return PaymeTransaction::class;
    }

    function checkAmount(int $amount): bool {
        return $amount >= 100000 && $amount <= 100000000;
    }

    function allowTransaction(array $payload): bool {
        return true;
    }

    function transactionCreated($transaction): void {
         /** @var PaymeTransaction $transaction */

        $model = new Transaction();
        $model->source = Transaction::SOURCE_PAYME;
        $model->source_id = $transaction->id;
        $model->user_id = $transaction->user_id;
        $model->amount = $transaction->amount / 100;
        $model->type = Transaction::TYPE_TOP_UP;
        $model->save();
    }

    function allowRefund($transaction): bool {
        /** @var PaymeTransaction $transaction */
        return true;
    }
    
    function userBalance(int $userId): int {
        // transaction jadvalidan foydalanuvchi balansini hisoblash,
        // misol sifatida ushbu TransactionService klassi beriladi.
        return TransactionService::userBalance($userId) * 100;
    }

    function refund($transaction): void {
        /** @var PaymeTransaction $transaction */

        $model = new Transaction();
        $model->user_id = $transaction->user_id;
        $model->amount = $transaction->amount / 100;
        $model->type = Transaction::TYPE_REFUND;
        $model->save();
    }
}
```

----

5. **Foydalanuvchi jamg'armasidagi balansni hisoblash uchun yordamchi klass**

```php
<?php

namespace app\models\service;

use app\models\Transaction;

class TransactionService {
    public const TYPE_TOP_UP = 'top-up';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_EXPENSE = 'expense';

    public const INCREMENT = [
        self::TYPE_TOP_UP,
        self::TYPE_BONUS,
    ];

    public const DECREMENT = [
        self::TYPE_EXPENSE,
    ];

    public static function userBalance(int $user_id): int {
        $balance = 0;

        /** @var Transaction $transactions */
        $transactions = Transaction::find()->where(['user_id' => $user_id])->all();

        foreach ($transactions as $transaction) {
            if (in_array($transaction->type, self::INCREMENT)) {
                $balance += $transaction->amount;
            } elseif (in_array($transaction->type, self::DECREMENT)) {
                $balance -= $transaction->amount;
            }
        }

        return self::inUZS($balance);
    }

    protected static function inUZS($amount): int {
        return $amount / 100;
    }
}
```

----

**Integratsiya bo'yicha batafsil ma'lumotlarni https://developer.help.paycom.uz saytidan ko'rishingiz mumkin.**

- Taklif va shikoyatlar uchun: https://devid.uz

- Moddiy taraflama qo'llab quvvatlash uchun: https://payme.uz/@uzdevid