<?php
// PayHere Sandbox Configuration for ASAA Healthcare
class PayHereConfig
{

    // SANDBOX CREDENTIALS (Replace with your actual credentials)
    const MERCHANT_ID = "1232593";
    const MERCHANT_SECRET = "MjUxMDEyMjE2MTMyMDk5ODY1NTMxOTMyNzQxNDMxNzA0MzMwNjky";

    // URLs (localhost compatible)
    const PAYHERE_URL = "https://sandbox.payhere.lk/pay/checkout";
    const RETURN_URL = "http://localhost/asaa_healthcare/views/payments/success.php";
    const CANCEL_URL = "http://localhost/asaa_healthcare/views/payments/cancel.php";
    const NOTIFY_URL = "http://localhost/asaa_healthcare/api/payment_notify.php";

    // Currency
    const CURRENCY = "LKR";

    /**
     * Generate PayHere hash for security
     */
    public static function generateHash($orderId, $amount, $currency = self::CURRENCY)
    {
        return strtoupper(
            md5(
                self::MERCHANT_ID .
                    $orderId .
                    number_format($amount, 2, '.', '') .
                    $currency .
                    strtoupper(md5(self::MERCHANT_SECRET))
            )
        );
    }

    /**
     * Validate PayHere response hash
     */
    public static function validateResponseHash($merchantId, $orderId, $amount, $currency, $statusCode, $hash)
    {
        $localHash = strtoupper(
            md5(
                $merchantId .
                    $orderId .
                    $amount .
                    $currency .
                    $statusCode .
                    strtoupper(md5(self::MERCHANT_SECRET))
            )
        );

        return strtoupper($hash) === $localHash;
    }

    /**
     * Get PayHere test cards for development
     */
    public static function getTestCards()
    {
        return [
            'visa_success' => [
                'number' => '4916217501611292',
                'expiry' => '12/25',
                'cvv' => '123',
                'result' => 'Success'
            ],
            'visa_decline' => [
                'number' => '4000000000000002',
                'expiry' => '12/25',
                'cvv' => '123',
                'result' => 'Decline'
            ],
            'mastercard' => [
                'number' => '5307732125531191',
                'expiry' => '12/25',
                'cvv' => '123',
                'result' => 'Success'
            ]
        ];
    }
}
