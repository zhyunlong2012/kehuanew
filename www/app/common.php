<?php
// 应用公共文件
use app\common\JwtAuther;

/**
 * 返回token用户id
 *
 * @return void
 */
function getUid(){
    $jwttoken = JwtAuther::getInstance();
    return $jwttoken->getUid();
}

/**
 * 添加操作日志
 * @param string $content [description]
 * @param int $uid [description]
 */
function addLog($content='',$uid=NULL){
	$log_model = new \app\model\Log();
	return $log_model->add($content,$uid);
}

/**
 * 返回chidren
 *
 * @param array $array
 * @param integer $pid
 * @return void
 */
function recursive(array $array, int $pid = 0)
{
    $result = [];
    foreach ($array as $key => $value) {
        if ($value['pid'] == $pid) { // 如果找到传过来的父级ID
            $value['children'] = recursive($array, $value['id']); // 把该条目的ID做为父类，寻找其下级
            if (!$value['children']) {
                unset($value['children']);
            }
            $result[] = $value;
        }
    }
    return $result;
}
