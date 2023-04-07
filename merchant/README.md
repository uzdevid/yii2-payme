Merchant API protokoli
----------------------

**Jamg'arma hisobi** - Foydalanuvchi tizimda o'z balansini to'ldirish uchun. ([foydalanish yo'riqnimasi](https://github.com/uzdevid/yii2-payme/tree/main/merchant/savings))

**Bir martalik hisob** - Buyurtma yoki xizmatlar uchun to'lov. (tez kunda)

---

## To'lov havolasini yaratish

Foydalanuvchi to'lovni amalga oshirish uchun havola yaratish.

### Kerakli ma'lumotlar

- `$merchant_id` - Tadbirkorligingizni Payme-dan ro'yhatdan o'tkazganingizdan so'ng Payme hodimlari tomonidan beriladi.


- `$account` - Jamg'arma hisobi uchun Foydalanuvchini identifikatori, Bir martalik hisob uchun buyurtma yoki xizmat 
identifikatori. Identifikator nomi Payme hodimlari tomonidan beriladi yoki o'z kabinetingizda kassaning sozlamalaridan olishingiz mumkin.


- `$amount` - To'lov summasi (tiyinda).


- `$params` - To'lovga qo'shimcha ma'lumotlar. Batafsil [shu yerda](https://developer.help.paycom.uz/initsializatsiya-platezhey/otpravka-cheka-po-metodu-get).

### To'lov havolasini yaratish, Yii2 controller misolida.

```php
namespace app\controllers;

use uzdevid\payme\merchant\CheckoutUrl;

class SiteController extends yii\web\Controller {
    public function actionIndex(): yii\web\Response {
        $merchant_id = "{merchant id}";
        
        $account = [
            '{identifikator nomi}' => "<identifikatori qiymati>",
        ];
        
        $amount = 100000;
        
        $params = [
            'l' => 'uz',
            'c' => 'https://example.com/checkout/success',
            'ct' => 15000,
            'cr' => 'uzs'
        ];

        $checkout_url = new CheckoutUrl($merchant_id, $account, $amount, $params);
        return $this->redirect($checkout_url);
    }
}

```

Ushbu to'lov tizimi bilan integratsiyani tekshirish uchun [Payme-ning test serveri](https://test.paycom.uz) mavjud. 
Ushbu serverda to'lovni amalga oshirish mumkin emas, lekin to'lov havolasini yaratish va to'lovni amalga 
oshirishni tekshirish mumkin. To'lovlarni test serverga yo'naltirish uchun `YII_ENV_DEV` ga `true` 
qiymatini berishingiz kerak.

---

## HTML shakl orqali

Batafsil ma'lumotlarni Payme 
[yo'riqnomasidan](https://developer.help.paycom.uz/initsializatsiya-platezhey/otpravka-cheka-po-metodu-post) 
topishingiz mumkin.

```html
<form method="POST" action="https://test.paycom.uz">

    <input type="hidden" name="merchant" value="{Merchant ID}"/>

    <input type="hidden" name="amount" value="{To'lov summasi tiyinda}"/>
    
    <input type="hidden" name="account[{identifikator nomi}]" value="{identifikatori qiymati}"/>

    <button type="submit"><b>Payme</b> orqali to'lash</button>
    
</form>
```

-----

**Integratsiya bo'yicha batafsil ma'lumotlarni https://developer.help.paycom.uz/ saytidan ko'rishingiz mumkin.**

- Taklif va shikoyatlar uchun: https://devid.uz

- Moddiy taraflama qo'llab quvvatlash uchun: https://payme.uz/@uzdevid