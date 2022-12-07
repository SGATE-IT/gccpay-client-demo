<?php
class GCCPay
{
    private $merchant_id = ""; ### merchant id 
    private $merchant_clientid = ""; ### merchant clientid
    private $merchant_key = ""; ### merchant key
    private $merchant_secret = ""; ### merchant secret
    private $environment = "sandbox"; ### environment
    public const ENVIRONMENT_PRODUCT="product";
    public const ENVIRONMENT_SANDBOX="sandbox";
    /**
     * 
     * @param string $merchant_id
     * @param string $merchant_clientid
     * @param string $merchant_key
     * @param string $merchant_secret
     */
    public function __construct($merchant_id,$merchant_clientid,$merchant_key,$merchant_secret,$environment="sandbox")
    {
        $this->merchant_id = $merchant_id;
        $this->merchant_clientid = $merchant_clientid;
        $this->merchant_key = $merchant_key;
        $this->merchant_secret = $merchant_secret;
        if(strtolower($environment) == self::ENVIRONMENT_PRODUCT)
        {
            $this->environment = self::ENVIRONMENT_PRODUCT;
        }
        else 
        {
            $this->environment = self::ENVIRONMENT_SANDBOX;
        }
        
    }
    /**
     * creat New Order
     * 
     * @param string $merchantOrderId
     * @param string $amount
     * @param string $currency
     * @param string $name
     * @param string $notificationUrl
     * @param string $expireAt
     * @return array[]
     */
    public function createNewOrder($merchantOrderId,$amount,$currency="SAR",$name="order desc is empty",$notificationUrl="",$expireAt="")
    {
        $params = [];
        $params["merchantOrderId"] = $merchantOrderId;
        $params["amount"] = $amount;
        $params["currency"] = $currency;
        $params["name"] = $name;
        $params["notificationURL"] = $notificationUrl;
        if(empty($expireAt))
        {
            $params["expiredAt"] = strftime('%Y-%m-%dT%H:%M:%S.000Z',time()+3600*24*15);
        }
        else
        {
            $params["expiredAt"] = expiredAt;
        }
        $uri = "/merchants/" . $this->merchant_id . "/orders" ;
        return $this->submitToGCCPay($uri,"merchant.addOrder","post",$params);
    }
	
    /**
     * get Merchant Detail
     * @return array
     */
    public function getMerchantDetail()
    {
        $uri = "/merchants/" . $this->merchant_id;
        return $this->submitToGCCPay($uri,"merchant.detail");
    }
    /**
     * get Order Info
     * @param string $orderid
     * @return array
     */
    public function getOrderInfo($orderid = "")
    {
        $uri = "/orders/" . $orderid;
        return $this->submitToGCCPay($uri,"order.detail");        
    }
    /**
     * 
     * @param string $uri
     * @param string $method
     * @param string $post
     * @param array $params
     * @return array[]
     */
    private function submitToGCCPay($uri="",$method="",$post="get",$params=[])
    {
        $signArr = [];
        $signArr["uri"] = $uri;
        $signArr["key"] = $this->merchant_key;
        $signArr["timestamp"] = time();
        $signArr["signMethod"] = "HmacSHA256";
        $signArr["signVersion"] = 1;
        $signArr["method"] = $method;
        
        ksort($signArr);
        $signStr = http_build_query($signArr);
        $sign = base64_encode(hash_hmac('sha256',$signStr, $this->merchant_secret, true));
        echo "signStr:".$signStr ."\n";
        echo "sign:" . $sign ."\n";
        
        $headers = [];
        $headers[] = "Content-Type:application/json";
        $headers[] = "x-auth-signature: " . $sign;
        $headers[] = "x-auth-key:". $this->merchant_key;
        $headers[] = "x-auth-timestamp:". $signArr["timestamp"];
        $headers[] = "x-auth-sign-method: HmacSHA256";
        $headers[] = "x-auth-sign-version: 1";
        
        if($this->environment == self::ENVIRONMENT_PRODUCT)
        {
            $url = "https://gateway.gcc-pay.com/api_v1" . $uri;
        }
        else 
        {
            $url = "https://sandbox.gcc-pay.com/api_v1" . $uri;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers );
        
        $data = json_encode($params);
        if(strtolower($post) == "post")
        {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($curl);
        curl_close($curl);
        echo "Return:".$result."\n";
        $ret = json_decode($result,true);
        return $ret;        
    }
}

$merchant_id = "M123456"; ### Merchantid => start with:M  + 6 digital
$merchant_clientid = "CLT9208307"; ### Clientid => start with:CLT + 7 character
$merchant_key = "zS****zQI" ; ### Key => 32 bytes  digital + characters 
$merchant_secret = "Ro****B0Z" ;   ### Secret =>  64  bytes  digital + characters 
$gccpayObj = new GCCPay($merchant_id,$merchant_clientid,$merchant_key,$merchant_secret);
$merchantInfo = $gccpayObj->getMerchantDetail();
var_dump($merchantInfo);
/**
 * @var array $merchantInfo
 * 
 array(19) {
  ["id"]=>
  string(7) "M123456"
  ["name"]=>
  string(14) "GCCPayMerchant"
  ["status"]=>
  string(7) "enabled"
  ["ownerId"]=>
  string(7) "U1234567"
  ["name_en"]=>
  string(4) "null"
  ["CR_file"]=>
  NULL
  ["CR_number"]=>
  string(4) "null"
  ["CR_vatNumber"]=>
  string(4) "null"
  ["CR_expiredAt"]=>
  NULL
  ["address"]=>
  string(4) "null"
  ["telephone"]=>
  string(4) "null"
  ["scope"]=>
  NULL
  ["legalPerson"]=>
  string(4) "null"
  ["mcc"]=>
  string(6) "123333"
  ["agencyId"]=>
  string(7) "A936252"
  ["currencys"]=>
  string(11) "SAR,KWD,USD"
  ["createdAt"]=>
  string(24) "2022-11-08T23:20:19.000Z"
  ["updatedAt"]=>
  string(24) "2022-11-08T23:33:30.000Z"
  ["owner"]=>
  array(3) {
    ["id"]=>
    string(7) "U1234567"
    ["name"]=>
    string(14) "GCCPayMerchant"
    ["mobile"]=>
    string(11) "17610908585"
  }
}
 */
$newOrderInfo = $gccpayObj->createNewOrder("GCCPAY".time(), 10.03,"SAR","Order Desc ");
var_dump($newOrderInfo);
/**
 * @var array $newOrderInfo
 * 
array(13) {
  ["id"]=>
  string(30) "M123456T2022120704054676002823"
  ["clientId"]=>
  string(10) "CLT9208307"
  ["merchantId"]=>
  string(7) "M123456"
  ["status"]=>
  string(7) "pending"
  ["ticket"]=>
  string(64) "ROLM7r6yUhE5Zvq1fSCxPtxSdpuOZV6ydzmf0BPwMVszX7G7XsTtkFR1c9BLaSAV"
  ["name"]=>
  string(10) "Order Desc"
  ["merchantOrderId"]=>
  string(16) "GCCPAY1670385952"
  ["notificationURL"]=>
  string(0) ""
  ["amount"]=>
  float(10.03)
  ["currency"]=>
  string(3) "SAR"
  ["expiredAt"]=>
  string(24) "2022-12-22T04:05:52.000Z"
  ["createdAt"]=>
  string(24) "2022-12-07T04:05:46.758Z"
  ["refundAmount"]=>
  int(0)
}
 */
$OrderInfo = $gccpayObj->getOrderInfo($newOrderInfo["id"]);
var_dump($OrderInfo);
/**
 * @var array $OrderInfo
 * 
 array(19) {
  ["amount"]=>
  float(10.03)
  ["refundAmount"]=>
  int(0)
  ["id"]=>
  string(30) "M123456T2022120704054676002823"
  ["clientId"]=>
  string(10) "CLT9208307"
  ["merchantId"]=>
  string(7) "M123456"
  ["agencyId"]=>
  string(7) "A936252"
  ["status"]=>
  string(7) "pending"
  ["ticket"]=>
  string(64) "ROLM7r6yUhE5Zvq1fSCxPtxSdpuOZV6ydzmf0BPwMVszX7G7XsTtkFR1c9BLaSAV"
  ["name"]=>
  string(10) "Order Desc"
  ["merchantOrderId"]=>
  string(16) "GCCPAY1670385952"
  ["channelOrderId"]=>
  NULL
  ["notificationURL"]=>
  string(0) ""
  ["currency"]=>
  string(3) "SAR"
  ["paidAt"]=>
  NULL
  ["expiredAt"]=>
  string(24) "2022-12-22T04:05:52.000Z"
  ["message"]=>
  NULL
  ["refundTimes"]=>
  NULL
  ["createdAt"]=>
  string(24) "2022-12-07T04:05:46.000Z"
  ["updatedAt"]=>
  string(24) "2022-12-07T04:05:46.000Z"
}
 */


