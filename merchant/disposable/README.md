Merchant API protokoli - Bir martalik hisob
----------------------------------------

### Buyurtma yoki xizmatlar uchun to'lov.

---

1. Ma'lumotlarni saqlash uchun `user`, `payme_transaction`, `transaction` va `order` jadvalini yaratish.

Quyida keltirilgan jadvallarning ma'lumotlarini tizimingizning texnik ehtiyojiga moslashtirishingiz mumkin.
Ammo `shart` deb belgilangan maydonlarni yaratish shart. Aks holda kutilmagan xatoliklar yuz beradi. Qo'shimcha ma'lumotlar uchun maydonlar yaratishingiz mumkin.

----

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
- `order_id` | `[shart]` - Buyurtma ID.
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
    `order_id` INT NULL DEFAULT NULL,
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

----

Foydalanuvchilarning buyurtmalari saqlanadigan jadval. 
Ushbu jadval kengaytma bilan to'g'ridan to'g'ri bog'lik emas, 
balki u callback methodlar orqali ishlatilinadi, shu bois to'liq ixtiyoriy. 
Faqatgina kerakli amallarni bajara olsangiz bas.

- `id` | `[ixtiyoriy]` - Tranzaksiya ID.
- `user_id` | `[ixtiyoriy]` - Foydalanuvchi ID.
- `source` | `[ixtiyoriy]` - Tranzaksiya manbai (Payme, Click, Uzumbank va h.k.z).
- `source_id` | `[ixtiyoriy]` - Tranzaksiya manbai ID.
- `amount` | `[ixtiyoriy]` - Buyurtma summasi.
- `status` | `[ixtiyoriy]` - Buyurtma to'lov holati.
- `create_time` | `[ixtiyoriy]` - Buyurtma yaratilgan vaqti.

```sql
CREATE TABLE IF NOT EXISTS `order` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `source` VARCHAR(24) NOT NULL,
  `source_id` INT(11) NOT NULL,
  `amount` INT NOT NULL,
  `status` VARCHAR(24) NOT NULL,
  `create_time` BIGINT(16) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_order_user_id_idx` (`user_id` ASC),
  CONSTRAINT `fk_order_user_id`
    FOREIGN KEY (`user_id`)
    REFERENCES `uysavdo`.`user` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);
```

----

2. **Controller yaratish va unga `DisposableAccount` klassidan me'ros olish va `DisposableControllerInterface` interfeysini qo'shish.**

- `{payme kaliti}` - Payme tomonidan beriladigan kalit.

```php
namespace app\controllers;

use uzdevid\payme\merchant\disposable\DisposableAccount;
use uzdevid\payme\merchant\disposable\SavingsControllerInterface;

class PaymeController extends DisposableAccount implements DisposableControllerInterface {
    public function init(): void {
        $this->key = "{payme kaliti}";
        parent::init();
    }
}
```

----

3. `DisposableControllerInterface` interfeysi talab qilgan methodlarni yozish

- `orderClass()` - Foydalanuvchi modeli klassini qaytarishi lozim. Misol uchun `app\models\Order:class`
- `transactionClass()` - payme_transaction jadvalining model klassini qaytarishi lozim. Misol uchun `app\models\PaymeTransaction:class`
- `allowPay()` - Foydalanuvchi to'lov qilishga ruxsat berish. Agar ruxsat berilmagan bo'lsa `false` qaytaradi aks holda `true`.
- `transactionCreated()` - Transaksiya yaratilganidan so'ng ishga tushadigan method. Bu method ishga tushurilganida buyurtma holatini to'lov jarayonida holatga o'tkazish lozim.
- `transactionPerformed()` - Transaksiya amalga oshirilganidan so'ng ishga tushadigan method. Bu method ishga tushurilganida buyurtma holatini to'lov amalga oshirilgan holatga o'tkazish lozim.
- `allowRefund()` - Foydalanuvchi to'lovni qaytarishga ruxsat berish. Agar ruxsat berilmagan bo'lsa `false` qaytaradi aks holda `true`.
- `refund()` - Foydalanuvchiga to'lov qaytarilganidan so'ng ishga tushadigan method. Bu methodda buyurtma holatini qaytarilgan holatga o'tkazish lozim.

```php
namespace app\controllers;

use uzdevid\payme\merchant\disposable\DisposableAccount;
use uzdevid\payme\merchant\disposable\SavingsControllerInterface;

class PaymeController extends DisposableAccount implements DisposableControllerInterface {
    
    public function init(): void {
        $this->key = "{payme kaliti}";
        parent::init();
    }

   function orderClass(): string {
        // TODO: Implement orderClass() method.
    }

    function transactionClass(): string {
        // TODO: Implement transactionClass() method.
    }

    function transactionCreated($order, $transaction): void {
        // TODO: Implement transactionCreated() method.
    }

    function transactionPerformed($order, $transaction): void {
        // TODO: Implement transactionPerformed() method.
    }

    function allowRefund($order, $transaction): bool {
        // TODO: Implement allowRefund() method.
    }

    function refund($order, $transaction): void {
        // TODO: Implement refund() method.
    }
}
```

----

4. To'liq misol:

```php
namespace app\modules\uysavdo\modules\api\controllers;

use app\models\Order;
use app\models\PaymeTransaction;
use app\models\Transaction;
use uzdevid\payme\merchant\disposable\DisposableAccount;
use uzdevid\payme\merchant\disposable\DisposableControllerInterface;

class PaymeDisposableController extends DisposableAccount implements DisposableControllerInterface {
    public function init(): void {
        $this->key = $_ENV['PAYME_TEST_KEY'];
        parent::init();
    }

    function orderClass(): string {
        return Order::class;
    }

    function transactionClass(): string {
        return PaymeTransaction::class;
    }

    function allowPay($order): bool {
        return $order->status === Order::STATUS_NEW;
    }

    function transactionCreated($order, $transaction): void {
        $order->status = Order::STATUS_PENDING;
        $order->save();
    }

    function transactionPerformed($order, $transaction): void {
        /**
         * @var Order $order
         * @var PaymeTransaction $transaction
         */

        $order->status = Order::STATUS_PAID;
        $order->save();

        $model = new Transaction();
        $model->source = Transaction::SOURCE_PAYME;
        $model->source_id = $transaction->id;
        $model->user_id = $order->user_id;
        $model->amount = $transaction->amount;
        $model->type = Transaction::TYPE_TOP_UP;
        $model->save();

        $model = new Transaction();
        $model->source = Transaction::SOURCE_ORDER;
        $model->source_id = $order->id;
        $model->user_id = $order->user_id;
        $model->amount = $transaction->amount;
        $model->type = Transaction::TYPE_EXPENSE;
        $model->save();
    }

    function allowRefund($order, $transaction): bool {
        return false; // qaytarishga ruxsat berilmagan, buni tizimingizga mos qilib o'zgartiring
    }

    function refund($order, $transaction): void {

    }

}
```

----

**Integratsiya bo'yicha batafsil ma'lumotlarni https://developer.help.paycom.uz saytidan ko'rishingiz mumkin.**

- Taklif va shikoyatlar uchun: https://devid.uz

- Moddiy taraflama qo'llab quvvatlash uchun: https://payme.uz/@uzdevid