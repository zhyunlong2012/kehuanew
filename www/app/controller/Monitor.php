<?php
namespace app\controller;
/**
 * 系统监控数据
 */
class Monitor
{
    use \app\common\ResponseMsg;

    /**
     * 打包监控信息
     *
     * @return void
     */
    public function pack(){
        $time = input('time');
        $monitor_mode = new \app\model\Monitor();
        $res = $monitor_mode->packmonitor($time);
        return $this->JsonSuccess($res);
    }

    /**
     * 随车单监控
     *
     * @return void
     */
    public function bill(){
        $monitor_mode = new \app\model\Monitor();
        $res = $monitor_mode->billmonitor();
        return $this->JsonSuccess($res);
    }

    /**
     * 出入库监控
     *
     * @return void
     */
    public function sto(){
        $monitor_mode = new \app\model\Monitor();
        $res = $monitor_mode->stomonitor();
        return $this->JsonSuccess($res);
    }

     /**
     * 车辆监控
     *
     * @return void
     */
    public function car(){
        $monitor_mode = new \app\model\Monitor();
        $res = $monitor_mode->carmonitor();
        return $this->JsonSuccess($res);
    }


}