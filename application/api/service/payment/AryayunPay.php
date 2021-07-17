<?php
/**
 * Created by PhpStorm.
 * User: zhangxiaohei
 * Date: 2020/4/22
 * Time: 23:16
 */

namespace app\api\service\payment;


use app\api\service\ApiPayment;
use app\common\library\exception\OrderException;
use think\Log;

class AryayunPay extends ApiPayment
{
    /**
     * 统一下单
     */
    private function pay($order,$type='alipay_qrcode_fix'){
        $url = 'http://sanhu.pantaoyan.vip/api/v1/charges';
        $merkey = '3079297d7c2c4585b230dafe16c47432';
        $data = [
            'uid'   =>  '459840923476426752',
            'money'   =>  sprintf("%.2f",$order["amount"]),
            'channel'   =>  $type,
            'outTradeNo'   =>  $order['trade_no'],
            'notifyUrl'   =>  $this->config['notify_url'],
            'returnUrl'   =>  $this->config['return_url'],
            'timestamp'   =>  time()*1000,
        ];
        $data['sign'] = $this->getSign($data,$merkey);
        $result =  json_decode(self::curlPost($url,$data),true);
        if($result['code'] != '0' )
        {
            Log::error('Create AryayunPay API Error:'.$result['msg']);
            throw new OrderException([
                'msg'   => 'Create AryayunPay API Error:'.$result['msg'],
                'errCode'   => 200009
            ]);
        }
        return $result['data']['payUrl'];
    }



    public function query($notifyData){

        $url = 'http://sh.pantaoyan.vip/api/v1/getOrderByOutTradeNo';

        $merkey = '3079297d7c2c4585b230dafe16c47432';
        $data=array(
            'outTradeNo'=>$notifyData['outTradeNo'],
            'uid'=>'459840923476426752',
            'timestamp'=>msectime(),
        );
        $data['sign'] = $this->getSign($data,$merkey);

        $result =  json_decode(self::curlPost($url,$data),true);

        Log::notice('query AryayunPay  API notice:'.json_encode($result));
        if(  $result['code'] != '0' ){
            Log::error('query AryayunPay  API Error:'.$result['msg']);
            return false;
        }
        if($result['data']['paySucc'] != true ){
            return false;
        }
        return true;
    }




    private function getSign($data,$secret )
    {
        $data['token']  = $secret;

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a = '';
        foreach ($data as $k=>$v) {
            $string_a .= "{$k}={$v}&";
        }
        $string_a = substr($string_a,0,strlen($string_a) - 1);
        //签名步骤三：MD5加密
        $sign = md5($string_a);

        // 签名步骤四：所有字符转为大写
        $result = strtoupper($sign);

        return $result;
    }


    /**
     * @param $params
     * 支付宝
     */
    public function guma_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_qrcode_fix');
        return [
            'request_url' => $url,
        ];
    }
public function h5_zfb($params)
    {
        //获取预下单
        $url = $this->pay($params,'alipay_qrcode_fix');
        return [
            'request_url' => $url,
        ];
    }


    /**
     * @param $params
     * @return array
     *  test
     */
    public function test($params){
        //获取预下单
        $url = $this->pay($params);
        return [
            'request_url' => $url,
        ];
    }





    /**
     * @return mixed
     * 回调
     */
    public function notify()
    {
        $notifyData =$_POST;
        Log::notice("AryayunPay notify data1".json_encode($notifyData));
//        channel=alipay_qrcode_auto&tradeNo=456931005714923520&outTradeNo=115868704409556&money=200&realMoney=200&uid=456844661697282048&sign=CBE1BE600AFEEA6FB5DEA2CC9698F865
        if(isset($notifyData['outTradeNo'])){
            if($this->query($notifyData)) {
                echo "SUCCESS";
                $data['out_trade_no'] = $notifyData['outTradeNo'];
                return $data;
            }
        }
        echo "error";
        Log::error('AryayunPay API Error:'.json_encode($notifyData));
    }

}
