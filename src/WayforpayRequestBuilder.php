<?php
/**
 * User: nemishkor
 * Date: 28.03.20
 */

declare(strict_types=1);


namespace Nemishkor\Wayforpay;


use Nemishkor\Wayforpay\ObjectValues\Order\Product;
use Nemishkor\Wayforpay\ObjectValues\PurchaseRequestParams;

class WayforpayRequestBuilder {

    private $merchantSecret;

    public function __construct(
        string $merchantSecret
    ) {
        $this->merchantSecret = $merchantSecret;
    }

    public function getPurchaseRequestContent(PurchaseRequestParams $params): string {

        $body = [
            'merchantAccount' => $params->getMerchant()->getAccount(),
            'merchantAuthType' => $params->getMerchant()->getAuthType(),
            'merchantDomainName' => $params->getMerchant()->getDomainName(),
            'merchantTransactionType' => $params->getMerchant()->getTransactionType(),
            'merchantTransactionSecureType' => $params->getMerchant()->getTransactionSecureType(),
            'language' => $params->getLanguage(),
            'orderReference' => $params->getOrder()->getReference(),
            'orderDate' => $params->getOrder()->getDate(),
            'amount' => $params->getOrder()->getAmount(),
            'currency' => $params->getOrder()->getCurrency(),
            'holdTimeout' => $params->getOrder()->getHoldTimeout(),
            'productName' => array_map(
                static function(Product $product) {
                    return $product->getName();
                },
                $params->getOrder()->getProducts()
            ),
            'productPrice' => array_map(
                static function(Product $product) {
                    return $product->getPrice();
                },
                $params->getOrder()->getProducts()
            ),
            'productCount' => array_map(
                static function(Product $product) {
                    return $product->getCount();
                },
                $params->getOrder()->getProducts()
            ),
        ];

        if ($params->getReturnUrl() !== null) {
            $body['returnUrl'] = $params->getReturnUrl();
        }

        if ($params->getServiceUrl() !== null) {
            $body['serviceUrl'] = $params->getServiceUrl();
        }

        if ($params->getPaymentSystems() !== null) {
            $body['paymentSystems'] = implode(';', $params->getPaymentSystems());
        }

        if ($params->getDefaultPaymentSystem() !== null) {
            $body['defaultPaymentSystem'] = $params->getDefaultPaymentSystem();
        }

        if ($params->getOrder()->getAlternativeAmount() !== null) {
            $body['alternativeAmount'] = $params->getOrder()->getAlternativeAmount();
        }

        if ($params->getOrder()->getAlternativeCurrency() !== null) {
            $body['alternativeCurrency'] = $params->getOrder()->getAlternativeCurrency();
        }

        if ($params->getOrder()->getOrderTimeout() !== null) {
            $body['orderTimeout'] = $params->getOrder()->getOrderTimeout();
        }

        if ($params->getOrder()->getRecToken() !== null) {
            $body['recToken'] = $params->getOrder()->getRecToken();
        }

        if ($params->getOrder()->getClientAccountId() !== null) {
            $body['clientAccountId'] = $params->getOrder()->getClientAccountId();
        }

        if ($params->getOrder()->getSocialUri() !== null) {
            $body['socialUri'] = $params->getOrder()->getSocialUri();
        }

        if ($params->getOrder()->getClient() !== null) {
            $body['clientFirstName'] = $params->getOrder()->getClient()->getFirstName();
            $body['clientLastName'] = $params->getOrder()->getClient()->getLastName();
            $body['clientAddress'] = $params->getOrder()->getClient()->getAddress();
            $body['clientCity'] = $params->getOrder()->getClient()->getCity();
            $body['clientState'] = $params->getOrder()->getClient()->getState();
            $body['clientZipCode'] = $params->getOrder()->getClient()->getZipCode();
            $body['clientCountry'] = $params->getOrder()->getClient()->getCountry();
            $body['clientEmail'] = $params->getOrder()->getClient()->getEmail();
            $body['clientPhone'] = $params->getOrder()->getClient()->getPhone();
        }

        if ($params->getOrder()->getDelivery() !== null) {
            $body['deliveryFirstName'] = $params->getOrder()->getDelivery()->getFirstName();
            $body['deliveryLastName'] = $params->getOrder()->getDelivery()->getLastName();
            $body['deliveryAddress'] = $params->getOrder()->getDelivery()->getAddress();
            $body['deliveryCity'] = $params->getOrder()->getDelivery()->getCity();
            $body['deliveryState'] = $params->getOrder()->getDelivery()->getState();
            $body['deliveryZipCode'] = $params->getOrder()->getDelivery()->getZipCode();
            $body['deliveryCountry'] = $params->getOrder()->getDelivery()->getCountry();
            $body['deliveryEmail'] = $params->getOrder()->getDelivery()->getEmail();
            $body['deliveryPhone'] = $params->getOrder()->getDelivery()->getPhone();
        }

        if ($params->getOrder()->getAvia() !== null) {
            $body['aviaDepartureDate'] = $params->getOrder()->getAvia()->getDepartureDate();
            $body['aviaLocationNumber'] = $params->getOrder()->getAvia()->getLocationNumber();
            $body['aviaLocationCodes'] = $params->getOrder()->getAvia()->getLocationCodes();
            $body['aviaFirstName'] = $params->getOrder()->getAvia()->getFirstName();
            $body['aviaLastName'] = $params->getOrder()->getAvia()->getLastName();
            $body['aviaReservationCode'] = $params->getOrder()->getAvia()->getReservationCode();
        }

        $paramsForSignature = array_merge(
            [
                $params->getMerchant()->getAccount(),
                $params->getMerchant()->getDomainName(),
                $params->getOrder()->getReference(),
                $params->getOrder()->getDate()->getTimestamp(),
                $params->getOrder()->getAmount(),
                $params->getOrder()->getCurrency(),
            ],
            array_map(
                static function(Product $product): string {
                    return $product->getName();
                },
                $params->getOrder()->getProducts()
            ),
            array_map(
                static function(Product $product): string {
                    return number_format($product->getCount(), 0, '', '');
                },
                $params->getOrder()->getProducts()
            ),
            array_map(
                static function(Product $product): string {
                    return number_format($product->getPrice(), 2, '.', '');
                },
                $params->getOrder()->getProducts()
            )
        );

        $body['merchantSignature'] = $this->calculateSignature($paramsForSignature);

        return http_build_query($body);
    }

    /**
     * @param array $params
     * @return string
     */
    private function calculateSignature(array $params): string {
        return hash_hmac("md5", implode(';', $params), $this->merchantSecret);
    }

}