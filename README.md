Yii2 Payme
==========

Payme to'lov tizimi bilan integratsiya qilish uchun Yii2 framework-i uchun kengaytma

O'rnatish
---------

Ushbu kengaytmani o'rnatishning afzal usuli - [composer](http://getcomposer.org/download/) orqali.

O'rnatish uchun quyidagi buyruqni ishga tushiring:

```
php composer require --prefer-dist uzdevid/yii2-payme "2.0"
```

Agar siz composer global o'rnatgan bo'lsangiz, quyidagi buyruqni ishga tushiring:

```
composer require --prefer-dist uzdevid/yii2-payme "2.0"
```

Yoki quyidagi qatorni `composer.json` faylga qo'shing:

```
"uzdevid/yii2-payme": "^2.0"
```

## Merchant API protokoli

**Bir martalik hisob** ([foydalanish yo'riqnimasi](https://github.com/uzdevid/yii2-payme/tree/main/merchant/disposable)) - Buyurtma yoki xizmatlar uchun to'lov

**Jamg'arma hisobi** ([foydalanish yo'riqnimasi](https://github.com/uzdevid/yii2-payme/tree/main/merchant/savings)) - Foydalanuvchi tizimda o'z balansini to'ldirish uchun

---

### Subscribe API protokoli

- Tez kunda