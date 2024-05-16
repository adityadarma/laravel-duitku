<?php

namespace AdityaDarma\LaravelDuitku;

use AdityaDarma\LaravelDuitku\Enums\ResponseCode;
use AdityaDarma\LaravelDuitku\Exceptions\DuitkuResponseException;
use AdityaDarma\LaravelDuitku\Exceptions\InvalidSignatureException;
use AdityaDarma\LaravelDuitku\Exceptions\MissingParamaterException;
use AdityaDarma\LaravelDuitku\Exceptions\PaymentMethodUnavailableException;
use AdityaDarma\LaravelDuitku\Exceptions\TransactionNotFoundException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class LaravelDuitkuAPI
{
    public string $merchantCode;
    public string $apiKey;
    public string $callbackUrl;
    public string $returnUrl;
    public string $url;
    public string $env;
    public string $datetime;

    /**
     * Constructor
     */
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

    /**
     * Set url domain
     *
     * @return void
     */
    public function setUrl(): void
    {
        $this->url = config('duitku.env') === 'production'
            ? 'https://passport.duitku.com'
            : 'https://sandbox.duitku.com';
    }

    /**
     * Get payment method available
     *
     * @param int $paymentAmount
     * @return array
     * @throws DuitkuResponseException
     * @throws RequestException
     */
    public function getPaymentMethods(int $paymentAmount): array
    {
        // Request data to API
        $response = Http::post($this->url.'/webapi/api/merchant/paymentmethod/getpaymentmethod', [
                'merchantcode'  => $this->merchantCode,
                'amount'        => $paymentAmount,
                'datetime'      => $this->datetime,
                'signature'     => hash('sha256', $this->merchantCode . $paymentAmount . $this->datetime . $this->apiKey)
            ])->throw(function () {
                throw new DuitkuResponseException();
            })->object();

        // Return data payment method
        if ($response && $response->responseCode === ResponseCode::Success) {
            $paymentMethod = [];
            foreach ($response->paymentFee as $method) {
                $paymentMethod[] = (object)[
                    'paymentMethod' => $method->paymentMethod,
                    'paymentName'   => $method->paymentName,
                    'paymentImage'  => $method->paymentImage,
                    'totalFee'      => (int)($method->totalFee)
                ];
            }
            return $paymentMethod;
        }

        throw new DuitkuResponseException();
    }

    /**
     * Create payment transaction
     *
     * @param array $data
     * @return object
     * @throws DuitkuResponseException
     * @throws InvalidSignatureException
     * @throws MissingParamaterException
     * @throws PaymentMethodUnavailableException
     * @throws RequestException
     */
    public function createTransaction(array $data): object
    {
        // Validate data input
        $validator = Validator::make($data, [
            'paymentAmount'     => ['required', 'numeric'],
            'merchantOrderId'   => ['required', 'string', 'max:50'],
            'productDetails'    => ['required', 'string', 'max:255'],
            'email'             => ['required', 'email', 'max:255'],
            'additionalParam'   => ['nullable'],
            'paymentMethod'     => ['required', 'string', 'max:2'],
            'merchantUserInfo'  => ['nullable'],
            'customerVaName'    => ['required', 'string', 'max:20'],
            'phoneNumber'       => ['nullable', 'string', 'max:50'],
            'itemDetails'       => ['nullable', 'array'],
            'customerDetail'    => ['nullable', 'array'],
            'expiryPeriod'      => ['nullable', 'numeric'],
        ]);
        if ($validator->fails()) {
            throw new MissingParamaterException();
        }

        // Request data to API
        $response = Http::post($this->url.'/webapi/api/merchant/v2/inquiry', array_merge($data, [
                'merchantcode'  => $this->merchantCode,
                'returnUrl'     => $this->returnUrl,
                'callbackUrl'   => $this->callbackUrl,
                'signature'     => md5($this->merchantCode . $data['merchantOrderId'] . $data['paymentAmount'] . $this->apiKey),
            ]))->throw(function ($response) {
                if (str_contains($response->body(), 'Wrong Signature')) {
                    throw new InvalidSignatureException();
                }
                if (str_contains($response->body(), 'Payment channel not available')) {
                    throw new PaymentMethodUnavailableException();
                }
                throw new DuitkuResponseException();
            })->object();

        // Return data new transaction
        if ($response && $response->statusCode === ResponseCode::Success) {
            return (object)[
                'success'       => true,
                'merchantCode'  => $response->merchantCode,
                'reference'     => $response->reference,
                'paymentUrl'    => $response->paymentUrl,
                'vaNumber'      => $response->vaNumber ?? null,
                'qrString'      => $response->qrString ?? null,
                'amount'        => (int)($response->amount ?? 0),
                'statusCode'    => $response->statusCode,
                'statusMessage' => $response->statusMessage,
            ];
        }

        return (object)[
            'success'          => false,
            'statusCode'       => $response->statusCode,
            'statusMessage'    => $response->statusMessage,
        ];
    }

    /**
     * Check payment transaction
     *
     * @param string $merchantOrderId
     * @return object
     * @throws DuitkuResponseException
     * @throws InvalidSignatureException
     * @throws TransactionNotFoundException
     * @throws RequestException
     */
    public function checkTransactionStatus(string $merchantOrderId): object
    {
        // Check status transaction
        $response = Http::post($this->url.'/webapi/api/merchant/transactionStatus', [
                'merchantcode'      => $this->merchantCode,
                'merchantOrderId'   => $merchantOrderId,
                'signature'         => md5($this->merchantCode . $merchantOrderId . $this->apiKey),
            ])->throw(function ($response) {
                if (str_contains($response->body(), 'Wrong Signature')) {
                    throw new InvalidSignatureException();
                }
                if (str_contains($response->body(), 'Transaction not found')) {
                    throw new TransactionNotFoundException();
                }
                throw new DuitkuResponseException();
            })->object();

        // Return data transaction
        if ($response && $response->statusCode === ResponseCode::Success) {
            return (object)[
                'success'           => true,
                'merchantOrderId'   => $response->merchantOrderId,
                'reference'         => $response->reference,
                'amount'            => (int)($response->amount),
                'statusCode'        => $response->statusCode,
                'statusMessage'     => $response->statusMessage,
            ];
        }

        return (object)[
            'success'           => false,
            'statusCode'        => $response->statusCode,
            'statusMessage'     => $response->statusMessage,
        ];
    }

    /**
     * Capture callback notification payment
     *
     * @return object
     * @throws InvalidSignatureException
     */
    public function getNotificationTransaction(): object
    {
        if (isset(request()->merchantCode) && isset(request()->amount) && isset(request()->merchantOrderId) && isset(request()->signature)) {
            $calcSignature = md5(request()->merchantCode . request()->amount . request()->merchantOrderId . $this->apiKey);

            if(request()->signature == $calcSignature)
            {
                return (object) [
                    'merchantCode'      => request()->merchantCode,
                    'amount'            => request()->amount,
                    'merchantOrderId'   => request()->merchantOrderId,
                    'productDetail'     => request()->productDetail,
                    'additionalParam'   => request()->additionalParam,
                    'paymentCode'       => request()->paymentCode,
                    'resultCode'        => request()->resultCode,
                    'merchantUserId'    => request()->merchantUserId,
                    'reference'         => request()->reference,
                    'publisherOrderId'  => request()->publisherOrderId,
                    'spUserHash'        => request()->spUserHash,
                    'settlementDate'    => request()->settlementDate,
                    'issuerCode'        => request()->issuerCode,
                ];
            }

            throw new InvalidSignatureException('Bad Parameter');
        }

        throw new InvalidSignatureException('Bad Parameter');
    }
}
