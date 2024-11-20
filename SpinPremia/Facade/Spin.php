<?php

namespace Caliente\Common\Spin\Facade;

use Caliente\Common\SpinPrermia\Response;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for the account management provider
 *
 * @method static Response getOTP(string $phoneNumber, string $mediumCode)
 * @method static Response linkAccount(string $phoneNumber, string $mediumCode, string $otp)
 * @method static Response unlinkAccount(string $phoneNumber, string $mediumCode, string $otp)
 * @method static Response redeemSimulation(string $phoneNumber, string $mediumCode, string $otp, array $receipt)
 * @method static Response redeem(string $phoneNumber, string $mediumCode, string $otp, string $payWithPoints)
 * @method static Response accumulation(array $payment, array $receipt, string $mediumCode, array $metadata = [])
 * @method static Response balance(string $mediumCode)
 * @method static Response payBack(string $payWithPoint, string $mediumCode)
 *
 * Protected Methods
 * @method static string validateOTP(string|null $phoneNumber, string $mediumCode, string $otp)
 * @method static mixed validatePhoneNumber(string $phoneNumber)
 * @method static int paybackPricePoints(string $points)
 * @method static void init()
 * @method static Response request(string $method, string $uri, array|null $params = [], string $token = '')
 */
class Spin extends Facade{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(){
        return 'spin';
    }
}