<?php

namespace App\Services\SpinPremia;

use App\Models\SpinPremia\UserAccumulation;
use App\Services\Exceptions\ServiceException;
use Caliente\Common\Casino\Casino;
use Caliente\Common\Genadmin\Genadmin;
use Caliente\Common\Webapi\Webapi;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\SimpleCache\InvalidArgumentException;
use Psr\Log\LoggerAwareTrait;

class Spin
{


    /**
     *  Implementation for LoggerAwareInterface
     */
    use LoggerAwareTrait;

    /**
     * Key name
     *
     * @var mixed|string
     */
    protected $key = "spin-premia";

    /**
     * Api key Spin Premia Client
     *
     * @var string
     */
    protected $apiKeyClient = "";

    /**
     * Api key Spin Premia Server
     *
     * @var string
     */
    protected $apiKeyServer = "";

    /**
     * Source partner id Spin Premia
     *
     * @var string[]
     */
    protected $source = [];

    /**
     * Api Url Spin Premia
     *
     * @var string
     */
    protected $url = "";

    /**
     * Version Url Spin Premia
     *
     * @var string
     */
    protected $versionUrl = "/caliente";

    /**
     * Endpoints Spin Premia
     *
     * @var string[]
     */
    protected $endpoints = [
        "getOTP" => "/server/v1/otp/send", // Get OTP from SPIN
        "validateOTP" => "/server/v1/otp/validate", // Validate OTP from SPIN
        "addMember" => "/client/v1/member", // Add new member to SPIN
        "link" => "/client/v1/link", // Link Caliente account to SPIN
        "unlink" => "/server/v1/link/remove", // Unlink Caliente account to Spin
        "accumulation" => "/server/v1/accrual", // Registering a transaction and calculating the user points
        "balance" => "/server/v1/balance", // User balance inquiry
        "redeem" => "/client/v1/redeem", // Redemption points of user
        "redeemSimulation" => "/client/v1/redeem/simulation", // Simulation of point redemption to Spin
    ];

    /**
     * Errors
     *
     * @var array[]
     */
    protected $errors = [
        'invalid_api_key' => ["code" => "SYS-110", "message" => "Something went wrong"],
        'internal_server_error' => ["code" => "SYS-110", "message" => "Something went wrong"],
        'member_not_found' => ["code" => "SYS-111", "message" => "Member not found"],
        'invalid_medium_code' => ["code" => "SYS-112", "message" => "Invalid medium code"],
        'duplicated_mobile_phone' => ["code" => "SYS-113", "message" => "Duplicated mobile phone"],
        'otp_invalid_or_expired' => ["code" => "SYS-114", "message" => "OTP invalid or expired"],
        'SPOP-401-1' => ["code" => "SYS-114", "message" => "OTP invalid or expired"],
        'ticket_id_already_exists' => ["code" => "SYS-115", "message" => "Ticket already exists"],
        'transaction_blocked' => ["code" => "SYS-115", "message" => "Transaction blocked"],
        'SPOP-403-2' => ["code" => "SYS-116", "message" => "Send back the start, OTP tries exceeded"],
        'Send_back_the_start_OTP_tries_exceeded' => ["code" => "SYS-116", "message" => "OTP attempts exceeded"],
        'member_has_no_cards' => ["code" => "SYS-117", "message" => "Member has no valid card"],
        'benefit_not_available' => ["code" => "SYS-118", "message" => "Benefit not available"]
    ];

    /**
     * Client Guzzler
     *
     * @var null
     */
    protected $client = null;
    private Webapi $webapi;

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->apiKeyClient = config('spinpremia.apiKey');
        $this->apiKeyServer = config('spinpremia.apiKey');
        $this->url = config('spinpremia.spinUrl');
        $this->webapi = new Webapi();

        $this->source = [
            'pos_id' => "1",
            'cashier_id' => "WEB",
            'store_id' => config('spinpremia.storeId'),
            'channel_id' => config('spinpremia.channelId'),
            "partner_id" => (int)config('spinpremia.partnerId')
        ];

        $this->init();
    }

    /**
     * Get OTP
     * This endpoint allows you to obtain a code
     *
     * @param string $phoneNumber Phone number
     * @param string $mediumCode
     * @return Response
     * @throws ServiceException
     */
    public function getOTP(string $phoneNumber, string $mediumCode): Response
    {
        if (!empty($phoneNumber)) {
            $phoneNumber = $this->validatePhoneNumber($phoneNumber);
            if (!$phoneNumber) {
                throw new ServiceException("number is not a valid mobile number", "SYS-104");
            }

            $data = [
                "phoneNumber" => $phoneNumber //+525635684806
            ];
        } else {
            $data = [
                "medium_code" => $mediumCode //13579246
            ];
        }

        return $this->request("POST", $this->endpoints['getOTP'], $data);
    }

    /**
     * Validate OTP
     * This endpoint allows you to validate the code obtain from text message
     *
     * @param string|null $phoneNumber Phone number
     * @param string $mediumCode PlayerCode
     * @param string $otp OTP obtain from text message
     *
     * @return string Token
     * @throws ServiceException
     */
    private function validateOTP(?string $phoneNumber, string $mediumCode, string $otp): string
    {

        if (!empty($phoneNumber)) {
            $phoneNumber = $this->validatePhoneNumber($phoneNumber);
            if (!$phoneNumber) {
                throw new ServiceException("number is not a valid mobile number", "SYS-104");
            }

            $data = [
                "phoneNumber" => $phoneNumber, //+525635684806
                "otp" => trim($otp) //1234
            ];
        } else {
            $data = [
                "medium_code" => $mediumCode, //13579246
                "otp" => trim($otp) //1234
            ];
        }

        $response = $this->request("POST", $this->endpoints['validateOTP'], $data);

        if ($response->isError())
            throw new ServiceException($response->getMessage(), $response->getCode());

        return $response->get('accessToken');
    }

    /**
     * Link account
     * This endpoint allows you to link Caliente account with SPIN Premia
     *
     * @param string $mediumCode PlayerCode
     *
     * @return response
     * @throws ServiceException
     */
    public function linkAccount(string $phoneNumber, string $mediumCode, string $otp)
    {
        // Call validateOTP to get the access token
        $accessToken = $this->validateOTP($phoneNumber, $mediumCode, $otp);

        $data = [
            'medium_code' => $mediumCode,
            'source' => ["partner_id" => $this->source["partner_id"]],
            'ticket_id' => hash_hmac('md5', uniqid(), $this->key)
        ];

        return $this->request("POST", $this->endpoints['link'], $data, $accessToken);
    }

    /**
     * Unlink account
     * This endpoint allows you to unlink Caliente account with SPIN Premia
     *
     * @param string $phoneNumber Phone number
     * @param string $mediumCode PlayerCode
     * @param string $otp OTP obtain from text message
     *
     * @return response
     * @throws ServiceException
     */
    public function unlinkAccount(string $phoneNumber, string $mediumCode, string $otp)
    {
        $phoneNumber = $this->validatePhoneNumber($phoneNumber);
        if (!$phoneNumber) {
            throw new ServiceException("number is not a valid mobile number", "SYS-104");
        }

        // Call validateOTP to get the access token
        $accessToken = $this->validateOTP($phoneNumber, $mediumCode, $otp);


        $data = [
            'mobile_phone' => $phoneNumber,
            'medium_code' => $mediumCode,
            'source' => ["partner_id" => $this->source["partner_id"]],
            'ticket_id' => hash_hmac('md5', uniqid(), $this->key)
        ];

        return $this->request("POST", $this->endpoints['unlink'], $data, $accessToken);
    }

    /**
     * Redeem simulation
     * This endpoint allows you to carry out a simulation of redemption points on the total.
     *
     * @param array $receipt Receipt (may one or more):
     *                        [
     *                        'price' => 3630, //This is equal $36.30 (The last two chars are decimals)
     *                        'product_id' => "11223",
     *                        'quantity' => 1,
     *                        'category' => "",
     *                        'subcategory' => ""
     *                        ]
     *
     * @return response
     * @throws ServiceException
     */
    public function redeemSimulation(string $phoneNumber, string $mediumCode, string $otp, array $receipt)
    {
        // Call validateOTP to get the access token
        $accessToken = $this->validateOTP($phoneNumber, $mediumCode, $otp);

        $data = [
            "receipt" => $receipt,
            "source" => $this->source
        ];

        return $this->request("POST", $this->endpoints['redeemSimulation'], $data, $accessToken);
    }

    /**
     * Redeem
     * This endpoint allows you to carry out of redemption points on the total.
     *
     * @param string $phoneNumber Phone number
     * @param string $mediumCode PlayerCode
     * @param string $otp OTP obtain from text message
     * @param string $payWithPoints Pay with points (3630 is equal 36.30 (The last two chars are decimals))
     *
     * @return response
     * @throws ServiceException
     */
    public function redeem(string $phoneNumber, string $mediumCode, string $otp, string $payWithPoints)
    {

        // Call validateOTP to get the access token
        $accessToken = $this->validateOTP($phoneNumber, $mediumCode, $otp);

        if (intval($payWithPoints) < 50) {
            throw new ServiceException("Minimum redemption limit is 50 points", "SYS-104");
        }

        $data = [
            "pay_with_points" => intval($payWithPoints) * 10, //Multiply by 10 to round cents.
            "source" => $this->source,
            "ticket_id" => hash_hmac('md5', uniqid(), $this->key)
        ];

        return $this->request("POST", $this->endpoints['redeem'], $data, $accessToken);
    }

    /**
     * Accumulation
     * This endpoint allows you registering a transaction and calculating the user's points accumulation
     *
     * @param array $payment Payment details:
     *                           [
     *                           'payment_method' => "CASH", // Payment method
     *                           'amount' => 5 // Amount (field without decimals)
     *                           ]
     * @param array $receipt Receipt:
     *                           [
     *                           'price' => 500, // (500 is equal 5 (The last two chars are decimals))
     *                           'product_id' => "13579", // SKU
     *                           'quantity' => 1, // Quantity
     *                           'category' => "Juego 1", // Category of product
     *                           'subcategory' => "Apuesta 1", // Subcategory of product
     *                           ]
     * @param string $mediumCode PlayerCode
     * @param array $metadata Metadata (Additional information)
     *                           [
     *                           'field' => "Casino"
     *                           ]
     *
     * @return response
     */
    public function accumulation(array $payment, array $receipt, string $mediumCode, array $metadata = [])
    {

        $data = [
            "payment_details" => $payment,
            "receipt" => $receipt,
            "source" => $this->source,
            "medium_code" => $mediumCode,
            "ticket_id" => hash_hmac('md5', uniqid(), $this->key),
            "metadata" => $metadata
        ];

        return $this->request("POST", $this->endpoints['accumulation'], $data);
    }

    /**
     * Balance inquiry
     * This endpoint allows you to user balance inquiry (Ex. 2934 points is equal $293.4)
     *
     * @param string $mediumCode PlayerCode
     *
     * @return response
     */
    public function balance($mediumCode)
    {

        return $this->request("GET", $this->endpoints['balance'] . '/' . $mediumCode);
    }

    /**
     * Payback
     * This endpoint allows you to pay back of redemption points on the total
     *
     * @param string $payWithPoint Pay with points (3630 is equal 36.30 (The last two chars are decimals))
     * @param string $mediumCode PlayerCode
     *
     * @return response
     */
    public function payBack(string $payWithPoint, string $mediumCode)
    {
        //TODO: Validate this function (pay with points)
        $payment = [
            'payment_method' => "CASH", // Payment method
            'amount' => intval($payWithPoint) // SpinPoints
        ];
        $receipt = [
            [
                'price' => $this->paybackPricePoints($payWithPoint),
                'product_id' => "1",
                'quantity' => 1,
                'category' => "Retorno",
                'subcategory' => "Bono no aplicado",
            ]
        ];

        return $this->accumulation($payment, $receipt, $mediumCode);
    }

    /**
     * Validate phone number of client
     *
     * @param string $phoneNumber
     *
     * @return array|string|string[]|null
     */
    protected function validatePhoneNumber(string $phoneNumber)
    {
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        $pattern = "/^(\+52|52)?(\d{10})$/"; // Format: +528105679238, 528105679238 or 8105679238

        if (preg_match($pattern, $phoneNumber)) {
            if (preg_match('/^\+52\d{10}$/', $phoneNumber)) {
                return $phoneNumber;
            } elseif (strlen($phoneNumber) == 10) {
                return "+52" . $phoneNumber;
            }

            return "+" . $phoneNumber;
        } else {
            return false;
        }
    }

    /**
     * Payback price in points of client
     *
     * @param string $points
     *
     * @return int|string
     */
    protected function paybackPricePoints(string $points)
    {
        if (preg_match('/^\d+(\.\d{1,2})?$/', $points)) {
            $multiplier = 1000; //Use to convert points in accumulation points
            $points = $points * $multiplier;
        }

        return $points;
    }


    /**
     * Init request using Guzzler
     *
     * @return void
     */
    protected function init(): void
    {
        $this->client = new Client([
            'base_uri' => $this->url,
            'timeout' => 15.0,
            'http_errors' => true, // http errors
        ]);
    }

    /**
     * Get a response
     *
     * @param string $method Method to use
     * @param string $uri URI to send the request
     * @param array|null $params The params to send
     * @param string $token Token JWT
     *
     * @return Response
     */
    protected function request(string $method = 'POST', string $uri = '', array $params = null, string $token = ''): Response
    {
        try {
            $apiKey = str_contains($uri, 'client') ? $this->apiKeyClient : $this->apiKeyServer;

            $header = [
                'apikey' => trim($apiKey),
                'Content-Type' => 'application/json'
            ];

            if (!empty($token)) {
                $header['Authorization'] = 'Bearer ' . $token;
            } else {
                unset($header['Authorization']);
            }
            $response = $this->client->request($method, $this->versionUrl . $uri, [
                RequestOptions::JSON => $params,
                RequestOptions::HEADERS => $header,
            ]);

            $body = $response->getBody();
            $desc = json_decode($body->getContents());
            $data = $desc;

            $desc = $this->request_error($desc);
            if (empty($desc["code"]) && empty($desc["message"])) {
                $status = "success";
                $desc = "Request was successfully";
            } else {
                $status = "error";
                $data = ["code" => $desc["code"], "message" => $desc["message"]];
                $desc = $desc["message"];
            }

            $result = [
                'status' => $status,
                'desc' => $desc,
                'data' => $data
            ];

        } catch (\Exception|GuzzleException $exception) {
            if (method_exists($exception, 'getResponse')) {
                $response = $exception->getResponse();

                $body = $response->getBody();
                $desc = json_decode($body->getContents());
                $desc = $this->request_error($desc);

                $result = [
                    'status' => "error",
                    'desc' => $desc["message"],
                    'data' => ["code" => $desc["code"], "message" => $desc["message"]]
                ];
            } else {
                $result = [
                    'status' => 'error',
                    'desc' => $exception->getMessage(),
                    'data' => ["code" => "SYS-105", "message" => $exception->getMessage()]
                ];
            }
        }

        return new Response($result);
    }

    /**
     * Get error
     *
     * @param string $response
     *
     * @return array
     */
    protected function request_error($response): array
    {
        $response = (array)$response;

        $desc = "";
        $code = "";
        if (!empty($response['fault'])) {
            $response = (array)$response['fault'];
            $desc = $response['faultstring'] ?? "Server Error in Spin Premia";
            $code = "SYS-110";
        } elseif (!empty($response['errors'])) {
            $response = (array)$response['errors'][0];
            $desc = $response['message'] ?? "Invalid request";
            $code = $response['code'] ?? "SYS-110";
        } elseif (!empty($response['errorCode'])) {
            $desc = $response['message'] ?? "Unauthorized";
            $code = $response['errorCode'];
        } elseif (!empty($response[0])) {
            $response = (array)$response[0];
            $desc = $response['message'];
            $code = $response['code'];
        }

        if (!empty($this->errors[$code])) {
            $error = $this->errors[$code];
            $code = $error["code"];
            $desc = $error["message"];
        }

        return ["code" => $code, "message" => $desc];
    }
}

    /**
     * User spin premia linked tag
     *
     * @param string $username
     *
     * @return Response
     */
    public function setUserTagSpin($username){
        $tagName = "SpinPremiaLinked";

        $tag = [['name' => $tagName, 'type' => 'text', 'value' => '1']];

        $playerTag = $this->setPlayerTags($username, $tag);

        $response = new Response([
            "status"  => $playerTag->isSuccess() ? "success" : "error",
            "message" => $playerTag->isSuccess() ? "Request was successfully" : "Something went wrong: linked",
            "data"    => ["code" => "SYS-110"]
        ]);

        return $response;
    }


    /**
     * setPlayerTags wrapper
     *
     * @param string $username
     * @param array  $tags
     *
     * @return \Caliente\Common\Webapi\Response
     */
    protected function setPlayerTags($username, $tags = []){
        $res = $this->webapi->setPlayerTags($username, $tags);

        return $res;
    }

    /**
     * Redeem bonus
     *
     * @param string $username
     * @param string $product
     * @param integer|string $amount
     *
     * @return bool
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function redeemBonus(string $username, string $product, int|string $amount)
    {

        if (strtolower($product) == "casino") {
            $bonusId = "129005";
            $bonusType = "0";

            $playerBonus = $this->webapi->searchPlayerBonuses($username, ["templateCode" => $bonusId], ["statusFilter" => "current"], null);

            if ($playerBonus->isError() || !empty($playerBonus->get('bonuses'))) {
                return false;
            }

            $bonus = $this->giveManualBonus($username, "", $bonusId, $bonusType, "SPIN Premia Bonus",
                uniqid(), "", $amount, "MXN", "", 1);

            return (!empty($bonus->getData()) && $bonus->isSuccess());

        } elseif (strtolower($product) == "sports") {

            $startDate = "2023-12-01";
            $endDate = date('Y-m-d', time() + 86400);
            $bonusCode = "CalienteSpin";

            //TODO: Check login();
            $genAPI = new Genadmin(Casino::instance(), cache());

            $playerBonus = $genAPI->bonuses($username, $startDate, $endDate);

            if ($playerBonus) {
                return false;
            }

            $genAPI = new Genadmin(Casino::instance(), cache());
            //TODO: Check this response;
            return $genAPI->issueBonus($username, $bonusCode, $amount, "SPIN Premia Bonus");
        }

        return false;
    }

    /**
     * Redeem insert
     *
     * @param string $playerCode
     * @param float $point
     * @param float $amount
     * @param string $product
     *
     * @return void
     */
    public function setUserRedeem(string $playerCode, float $point, float $amount, string $product)
    {
        $monthName = date('F');

        /**
         * Save operation to db
         * TODO: Check db connection
         */
        try {
            $created = UserAccumulation::create([
                "user_id"         => $playerCode,
                "month_name"      => $monthName,
                "amount"          => $amount,
                "accumulate"      => $amount,
                "points"          => $point,
                "product"         => $product,
                "is_accumulation" => 0
            ]);
        } catch (Exception $dbException) {
            $this->logger->info('[SPIN] ' . $dbException->getCode() . ':' . $dbException->getMessage());
        }

    }

    /**
     * Check if a user has bonus related flags
     *
     * We consider a bonus abuser if user has at least one of the 2:
     * VIP Level 1
     * Has "bonus seeker" ticked
     * Has "do not allow bonuses" ticked
     *
     * @param string $username
     *
     * @return bool
     */
    public function isBonusAbuser($username){
        $playerInfo = $this->webapi->getPlayerInfo($username, ['noBonus', 'vipLevel', 'markAsBonusSeeker']);

        $noBonus           = $playerInfo->get('noBonus');
        $vipLevel          = $playerInfo->get('vipLevel');
        $markAsBonusSeeker = $playerInfo->get('markAsBonusSeeker');

        return (intval($noBonus) === 1 || intval($vipLevel) === 1 || intval($markAsBonusSeeker) === 1);
    }


    /**
     * giveManualBonus wrapper
     *
     * @param string $username
     * @param string $clientType
     * @param string $bonusId
     * @param integer $bonusType
     * @param string $description
     * @param string $remoteBonusId
     * @param string $clientPlatform
     * @param numeric $amount
     * @param string $currency
     * @param string $goldenChips
     * @param integer $adminCode
     *
     * @return \Caliente\Common\Webapi\Response
     * @throws Exception
     */
    protected function giveManualBonus(
        string $username, string $clientType, string $bonusId, int $bonusType, string $description, string $remoteBonusId,
        string $clientPlatform, float|int|string $amount, string $currency, string $goldenChips, int $adminCode
    ){
        return $this->webapi->giveManualBonus($username, $clientType, $bonusId, $bonusType, $description,
            $remoteBonusId, $clientPlatform, $amount, $currency, $goldenChips, $adminCode);
    }

}
