<?php

namespace AdityaDarma\LaravelDuitku;

use AdityaDarma\LaravelDuitku\Enums\ResponseCode;
use AdityaDarma\LaravelDuitku\Excepstions\DuitkuResponseException;
use AdityaDarma\LaravelDuitku\Excepstions\InvalidSignatureException;
use AdityaDarma\LaravelDuitku\Excepstions\MissingParamaterException;
use AdityaDarma\LaravelDuitku\Excepstions\PaymentMethodUnavailableException;
use AdityaDarma\LaravelDuitku\Excepstions\TransactionNotFoundException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class LaravelDuitku
{
    public string $merchantCode;
    public string $apiKey;
    public string $callbackUrl;
    public string $returnUrl;
    public string $url;
    public string $env;
    public string $datetime;

    public function __construct()
    {
        $this->merchantCode     = config('duitku.merchant_code');
        $this->apiKey           = config('duitku.api_key');
        $this->callbackUrl      = config('duitku.callback_url');
        $this->returnUrl        = config('duitku.return_url');
        $this->env              = config('duitku.env');
        $this->datetime         = now()->format('Y-m-d H:i:s');

        $this->setUrl();
    }

    public function setUrl(): void
    {
        $this->url = config('duitku.env') === 'production'
            ? config('duitku.url.prod')
            : config('duitku.url.dev');
    }

    /**
     * @param int $paymentAmount
     * @return object
     * @throws DuitkuResponseException
     */
    public function getPaymentMethod(int $paymentAmount): object
    {
        // Request data to API
        $response = Http::post($this->url.'/webapi/api/merchant/paymentmethod/getpaymentmethod', [
            'json' => [
                'merchantcode' => $this->merchantCode,
                'amount' => $paymentAmount,
                'datetime' => $this->datetime,
                'signature' => hash(
                    'sha256',
                    $this->merchantCode . $paymentAmount . $this->datetime . $this->apiKey
                )
            ]
        ]);

        // Response status
        if ($response->failed()) {
            throw new DuitkuResponseException();
        }

        // Return data payment method
        $data = $response->object();
        if ($data && $data->responseCode === ResponseCode::Success) {
            $paymentMethod = [];
            foreach ($data->paymentFee as $method) {
                $paymentMethod[] = [
                    'code' => $method->paymentMethod,
                    'name' => $method->paymentName,
                    'image' => $method->paymentImage,
                    'fee' => (int)($method->totalFee)
                ];
            }
            return (object)$paymentMethod;
        }

        throw new DuitkuResponseException();
    }

    /**
     * @param array $data
     * @return object
     * @throws DuitkuResponseException
     * @throws InvalidSignatureException
     * @throws MissingParamaterException
     * @throws PaymentMethodUnavailableException
     */
    public function createTransaction(array $data): object
    {
        // Validate data input
        $validator = Validator::make($data, [
            'merchantOrderId'   => ['required', 'string', 'max:50'],
            'customerVaName'    => ['required', 'string', 'max:20'],
            'email'             => ['required', 'email', 'max:255'],
            'paymentAmount'     => ['required', 'numeric'],
            'paymentMethod'     => ['required', 'string', 'max:2'],
            'productDetails'    => ['required', 'string', 'max:255'],
            'expiryPeriod'      => ['required', 'numeric'],
            'phoneNumber'       => ['nullable', 'string', 'max:50'],
            'itemDetails'       => ['nullable', 'array'],
            'customerDetail'    => ['nullable', 'array'],
            'additionalParam'   => ['nullable'],
            'merchantUserInfo'  => ['nullable'],
        ]);
        if ($validator->fails()) {
            throw new MissingParamaterException();
        }

        // Request data to API
        $response = Http::post($this->url.'/webapi/api/merchant/v2/inquiry', [
            'json' => array_merge($data, [
                'merchantcode'  => $this->merchantCode,
                "returnUrl"     => $this->returnUrl,
                "callbackUrl"   => $this->callbackUrl,
                'signature'     => hash(
                    'sha256',
                    $this->merchantCode . $data['paymentAmount'] . $this->datetime . $this->apiKey
                ),
            ])
        ]);

        // Response status
        if ($response->failed()) {
            if (str_contains($response->body(), 'Invalid Signature')) {
                throw new InvalidSignatureException();
            }
            if (str_contains($response->body(), 'Payment channel not available')) {
                throw new PaymentMethodUnavailableException();
            }
            throw new DuitkuResponseException();
        }

        // Return data transaction
        $data = $response->object();
        if ($data && $data->responseCode === ResponseCode::Success) {
            return (object)[
                'success'       => true,
                'reference'     => $data->reference,
                'payment_url'   => $data->paymentUrl,
                'va_number'     => $data->vaNumber,
                'amount'        => (int)($data->amount),
                'message'       => $data->statusMessage
            ];
        }

        return (object)[
            'success' => false,
            'message' => $data->statusMessage
        ];
    }

    /**
     * @param string $merchantOrderId
     * @return object
     * @throws DuitkuResponseException
     * @throws InvalidSignatureException
     * @throws TransactionNotFoundException
     */
    public function checkTransactionStatus(string $merchantOrderId): object
    {
        // Check status transaction
        $response = Http::post($this->url.'/webapi/api/merchant/transactionStatus', [
            'json' => [
                'merchantcode'      => $this->merchantCode,
                "merchantOrderId"   => $merchantOrderId,
                'signature'         => md5($this->merchantCode . $merchantOrderId . $this->apiKey),
            ]
        ]);

        // Response status
        if ($response->failed()) {
            if (str_contains($response->body(), 'Invalid Signature')) {
                throw new InvalidSignatureException();
            }
            if (str_contains($response->body(), 'Transaction not found')) {
                throw new TransactionNotFoundException();
            }
            throw new DuitkuResponseException();
        }

        // Return data transaction
        $data = $response->object();
        if ($data && $data->responseCode === ResponseCode::Success) {
            return (object)[
                'success'       => true,
                'reference'     => $data->reference,
                'amount'        => (int)($data->amount),
                'message'       => $data->statusMessage,
                'code'          => $data->statusCode,
            ];
        }

        return (object)[
            'success' => false,
            'message' => $data->statusMessage,
            'code'    => $data->statusCode,
        ];
    }

    /**
     * @return object
     * @throws TransactionNotFoundException
     * @throws DuitkuResponseException
     * @throws InvalidSignatureException
     */
    public function getNotificationTransaction(): object
    {
        $merchantOrderId = request()->merchantOrderId;
        if (!$merchantOrderId) {
            throw new TransactionNotFoundException();
        }

        // Validate transaction
        return $this->checkTransactionStatus($merchantOrderId);
    }
}