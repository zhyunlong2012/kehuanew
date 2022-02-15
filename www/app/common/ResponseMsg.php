<?php
namespace app\common;

trait ResponseMsg
{
    /**
     * App接口业务异常时的返回
     */
    public function JsonData($code,$msg,$data=[])
    {
        return $this->JsonResponse($code,$msg,$data);
    }

     /**
     * App接口业务异常时的返回(接收数组)
     */
    public function JsonDataArr($array,$data=[])
    {
        return $this->JsonResponse($array[0],$array[1],$data);
    }

    /**
     * App接口请求成功时的返回
     */
    public function JsonSuccess($data=[])
    {
        return $this->JsonResponse(ApiMsg::SUCCESS[0],ApiMsg::SUCCESS[1],$data);
    }

    /**
     * App接口请求失败时的返回(通用)
     */
    public function JsonErr($data=[])
    {
        return $this->JsonResponse(ApiMsg::ERR[0],ApiMsg::ERR[1],$data);
    }

    /**
     * App接口请求通用接口
     */
    public function JsonCommon($res,$data=[])
    {
        if($res==true){
            return $this->JsonSuccess($data);
        }else{
            return $this->JsonErr($data);
        }
        
    }

    /**
     * 返回json
     */
    private function JsonResponse($code,$msg,$data){
        $content = [
            'code' => $code,
            'msg' => $msg,
            'data' =>$data
        ];

        return json($content);
    }
}