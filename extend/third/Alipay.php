<?php
/**
 * 支付宝充值退款处理
 * 说明:
 *  1、APP支付成功后，同步通知只处理明确的支付成功状态
 *  2、服务端异步通知也只处理明确的支付成功状态
 *  3、支付失败状态依赖于充值交易查询
 *  4、退款成功or失败依赖于退款交易查询
 */

namespace third;

use think\Config;
use think\Log;

class Alipay
{

    //基础配置定义
    public $appid = '';
    public $uuid = '';
    protected $gateway = '';
    protected $notify_url = '';
    protected $rsa_public_key = '';
    protected $rsa_private_key = '';
    protected $alipay_rsa_public_key = '';

    protected $fileCharset = "UTF-8";
    public $postCharset = "UTF-8";

    /**
     * 架构函数，读取基础配置
     */
    public function __construct()
    {
        $this->appid = Config::get('alipay.APPID');
        $this->uuid = Config::get('alipay.UUID');
        $this->gateway = Config::get('alipay.GATEWAY');
        $this->notify_url = Config::get('alipay.NOTIFY_URL');
        $this->rsa_public_key = Config::get('alipay.RSA_PUBLIC_KEY');
        $this->rsa_private_key = Config::get('alipay.RSA_PRIVATE_KEY');
        $this->alipay_rsa_public_key = Config::get('alipay.ALIPAY_RSA_PUBLIC_KEY');
    }

    /**
     * 签名函数
     */
    protected function sign($data, $signType = "RSA2")
    {
        if (empty($this->rsa_private_key)) {
            Log::record("私钥内容为空，请检查RSA私钥配置");
            return false;
        }
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($this->rsa_private_key, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        if ("RSA2" == $signType) {
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 组装待签名字符串(urlencode,GET方法获取参数需要)
     * @param $params
     * @return string
     */
    protected function getSignContentUrlencode($params)
    {
        $stringToBeSigned = "";
        ksort($params);
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . urlencode($v);
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . urlencode($v);
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 组装待签名字符串
     * @param $params
     * @return string
     */
    public function getSignContent($params)
    {
        $stringToBeSigned = "";
        ksort($params);
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . $v;
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . $v;
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    protected function characet($data, $targetCharset)
    {
        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     */
    protected function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     * 组装APP支付接口所需参数
     * @param $title
     * @param $desc
     * @param $orderno
     * @param $totalmount
     * @return array
     */
    public function AlipayTradeAppPayRequest($title, $desc, $orderno, $totalmount)
    {
        $request_data = self::getCommonParam();
        //业务参数
        $params = array(
            "body" => $desc, //商品描述
            "subject" => $title, //商品标题
            "out_trade_no" => $orderno, //商户网站唯一订单号
            "timeout_express" => "24h", //超时时间,此处默认24小时
            "total_amount" => $totalmount, //订单总金额
            "seller_id" => $this->uuid, //收款支付宝用户ID
            "product_code" => "QUICK_MSECURITY_PAY", //销售产品码-固定值
            "goods_type" => "1", //商品主类型：0—虚拟类商品，1—实物类商品
        );
        $request_data["biz_content"] = self::getBizContent($params);
        $request_data["method"] = "alipay.trade.app.pay";
        $request_data["sign"] = self::sign(self::getSignContent($request_data));
        return $request_data;
    }

    /**
     * 组装交易查询接口所需参数
     * @param $orderid
     * @param $bankorderid
     * @return array
     */
    public function AlipayTradeQueryRequest($orderid, $bankorderid)
    {
        $request_data = self::getCommonParam();
        //业务参数
        $params = array(
            "out_trade_no" => $orderid, //商户订单号,和支付宝交易号不能同时为空
            "trade_no" => $bankorderid, //支付宝交易号，和商户订单号不能同时为空
        );
        $request_data["biz_content"] = self::getBizContent($params);
        $request_data["method"] = "alipay.trade.query";
        $request_data["sign"] = self::sign(self::getSignContent($request_data));
        return $request_data;
    }

    /**
     * 组装交易退款接口所需参数
     * @param $orderid
     * @param $bankorderid
     * @param $refundmoney
     * @param $refundid
     * @param $refundreason
     * @return array
     */
    public function AlipayTradeRefundRequest($orderid, $bankorderid, $refundmoney, $refundid, $refundreason = '')
    {
        $request_data = self::getCommonParam();
        //业务参数
        $params = array(
            "out_trade_no" => $orderid, //商户订单号,不能和trade_no同时为空
            "trade_no" => $bankorderid, //支付宝交易号,不能和out_trade_no同时为空
            "refund_amount" => $refundmoney, //退款金额,该金额不能大于订单金额
            "refund_reason" => $refundreason, //(可选)退款的原因说明
            "out_request_no" => $refundid, //退款订单号
        );
        $request_data["biz_content"] = self::getBizContent($params);
        $request_data["method"] = "alipay.trade.refund";
        $request_data["sign"] = self::sign(self::getSignContent($request_data));
        return $request_data;
    }

    /**
     * 组装交易退款查询所需参数
     * @param $orderid
     * @param $bankorderid
     * @param $refundid
     * @return array
     */
    public function AlipayTradeRefundQueryRequest($orderid, $bankorderid, $refundid)
    {
        $request_data = self::getCommonParam();
        //业务参数
        $params = array(
            "out_trade_no" => $orderid, //商户订单号,不能和trade_no同时为空
            "trade_no" => $bankorderid, //支付宝交易号,不能和out_trade_no同时为空
            "out_request_no" => $refundid, //退款订单号
        );
        $request_data["biz_content"] = self::getBizContent($params);
        $request_data["method"] = "alipay.trade.refund";
        $request_data["sign"] = self::sign(self::getSignContent($request_data));
        return $request_data;
    }

    /**
     * 获取公共参数
     * @return array
     */
    protected function getCommonParam()
    {
        return array(
            "app_id" => $this->appid,
            "method" => "",
            "format" => "JSON",
            "charset" => $this->postCharset,
            "sign_type" => "RSA2",
            "sign" => "",
            "timestamp" => date("Y-m-d H:i:s"),
            "version" => "1.0",
            "notify_url" => $this->notify_url,
            "biz_content" => "",
        );
    }

    /**
     * 组装业务参数字符串
     */
    protected function getBizContent($params)
    {
        $bizContent = "";
        if (!empty($params)) {
            $allnum = count($params);
            foreach ($params as $k => $v) {
                if (false === $this->checkEmpty($v)) {
                    if ($allnum == 1) {
                        $bizContent .= "\"" . $k . "\"" . ":" . "\"" . $v . "\"";
                    } else {
                        $bizContent .= "\"" . $k . "\"" . ":" . "\"" . $v . "\",";
                    }
                }
                $allnum--;
            }
        }
        $bizContent = "{" . $bizContent . "}";
        return $bizContent;
    }

    /**
     * 验证支付宝返回数据签名
     */
    public function verify($params, $sign, $signType = 'RSA2')
    {

        if (empty($this->alipay_rsa_public_key)) {
            Log::record("支付宝RSA公钥内容为空，请检查支付宝RSA公钥配置");
            return false;
        }
        $res = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($this->alipay_rsa_public_key, 64, "\n", true) . "\n-----END PUBLIC KEY-----";

        //调用openssl内置方法验签，返回bool值
        $sign = base64_decode($sign);
        $data = self::getSignContent($params);

        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, $sign, $res, OPENSSL_ALGO_SHA256);
        } else {
            $result = (bool)openssl_verify($data, $sign, $res);
        }

        return $result;
    }

}