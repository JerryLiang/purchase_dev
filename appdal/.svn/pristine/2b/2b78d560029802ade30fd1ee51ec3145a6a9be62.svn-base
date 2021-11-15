<?php

/**
 * 获取 OA 系统 API访问令牌
 * @author Jolon
 * @return mixed
 */
function getOASystemAccessToken()
{
    $CI = &get_instance();
    $CI->load->config('api_config', FALSE, TRUE);
    $access_taken = $CI->rediss->getData('ACCESS_TOKEN');
    if(empty($access_taken)) {
        $url    = $CI->config->item('oa_system')['access_token'];
      
        $result = getCurlData($url, '','post','',TRUE);
        $result = json_decode($result, true);
         //防止返回 有效期 为0 造成access_token值及java不一致
        if($result['expires_in']==0){ 
             sleep(1);
             $result = getCurlData($url, '','post','',TRUE);
             $result = json_decode($result, true);
        }
        $CI->rediss->setData('ACCESS_TOKEN', $result['access_token'], $result['expires_in']); //10分钟有效期
        $access_taken = $result['access_token'];
    }

    return $access_taken;
}