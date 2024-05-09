# Laravel Duitku
Laravel Duitku is package to provide payment gateway Duitku.

### Laravel Installation Instructions
1. From your projects root folder in terminal run:

   ```bash
   composer require adityadarma/laravel-duitku
   ```

2. Install config:

   ```bash
   php artisan laravel-duitku:install
   ```

### Configuration
Laravel Duitku can be configured in directly in `/config/duitku.php`. Add variables to your `.env` file.


##### Environment File
Here are the `.env` file variables available:

```dotenv
DUITKU_ENV=development
DUITKU_MERCHANT_CODE=xxxxxxx
DUITKU_API_KEY=xxxxxx
DUITKU_APDUITKU_CALLBACK_URLI_KEY=https://example.com/callback
DUITKU_RETURN_URL=https://example.com/to-page-payment
```

### Usage

##### Get Payment Method
example:

```php
use AdityaDarma\LaravelDuitku\Facades\Duitku;

$listPayments = Duitku::getPaymentMethod(1000000);
```

##### Create Payment
example:

```php
use AdityaDarma\LaravelDuitku\Facades\Duitku;

Duitku::createTransaction([
    'merchantOrderId'   => 10000,
    'customerVaName'    => 'Aditya Darma',
    'email'             => 'email@example.com',
    'paymentAmount'     => 100000,
    'paymentMethod'     => 'VC',
    'productDetails'    => 'Buy Company',
    'expiryPeriod'      => 10,  // optional (minute)
    'phoneNumber'       => '08123456789', // optional
    'itemDetails'       => [ // optional
        [
            'name' => 'Test Item 1',
            'price' => 10000,
            'quantity' => 1
        ],[
            'name' => 'Test Item 2',
            'price' => 10000,
            'quantity' => 1
        ]
    ],
    'customerDetail'    => [ // optional
        'firstName'         => 'Aditya',
        'lastName'          => 'Darma',
        'email'             => 'email@example.com',
        'phoneNumber'       => $phoneNumber,
        'billingAddress'    => $address,
        'shippingAddress'   => $address
    ],
    'additionalParam'   => '', // optional
    'merchantUserInfo'  => '', // optional
]);
```
[List Payment Method](https://docs.duitku.com/api/id/#metode-pembayaran)

##### Check Transaction
example:

```php
use AdityaDarma\LaravelDuitku\Facades\Duitku;

Duitku::checkTransactionStatus(1000000);
```

##### Handle Callback Transaction
example:

```php
use AdityaDarma\LaravelDuitku\Facades\Duitku;

$payment = Duitku::getNotificationTransaction();
```

## License

This Package is licensed under the MIT license. Enjoy!
