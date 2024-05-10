<?php

namespace AdityaDarma\LaravelDuitku;

use AdityaDarma\LaravelDuitku\Enums\ResponseCode;
use AdityaDarma\LaravelDuitku\Exceptions\DuitkuResponseException;
use AdityaDarma\LaravelDuitku\Exceptions\InvalidSignatureException;
use AdityaDarma\LaravelDuitku\Exceptions\MissingParamaterException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class LaravelDuitkuPOP
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
            ? 'https://api-prod.duitku.com'
            : 'https://api-sandbox.duitku.com';
    }

    /**
     * Create payment transaction
     *
     * @param array $data
     * @return object
     * @throws DuitkuResponseException
     * @throws InvalidSignatureException
     * @throws MissingParamaterException
     * @throws RequestException
     */
    public function createTransaction(array $data): object
    {
        // Validate data input
        $validator = Validator::make($data, [
            'merchantOrderId'   => ['required', 'string', 'max:50'],
            'customerVaName'    => ['nullable', 'string', 'max:20'],
            'email'             => ['required', 'email', 'max:255'],
            'paymentAmount'     => ['required', 'numeric'],
            'paymentMethod'     => ['required', 'string', 'max:2'],
            'productDetails'    => ['required', 'string', 'max:255'],
            'expiryPeriod'      => ['nullable', 'numeric'],
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
        $response = Http::post($this->url.'/api/merchant/createInvoice', array_merge($data, [
                'merchantcode'  => $this->merchantCode,
                "returnUrl"     => $this->returnUrl,
                "callbackUrl"   => $this->callbackUrl,
                'signature'     => md5($this->merchantCode . $data['merchantOrderId'] . $data['paymentAmount'] . $this->apiKey),
            ]))->throw(function ($response) {
                if (str_contains($response->body(), 'Wrong Signature')) {
                    throw new InvalidSignatureException();
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
                'statusMessage' => $response->statusMessage,
                'statusCode'    => $response->statusCode,
            ];
        }

        return (object)[
            'success'          => false,
            'statusMessage'    => $response->statusMessage,
            'statusCode'       => $response->statusCode,
        ];
    }

    /**
     * Capture callback notification payment
     *
     * @return object
     * @throws InvalidSignatureException
     * @throws RequestException
     */
    public function getNotificationTransaction(): object
    {
        $params = request()->merchantCode . request()->amount . request()->merchantOrderId . $this->apiKey;
        $calcSignature = md5($params);

        if(request()->signature == $calcSignature)
        {
            return (object) [
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
}
