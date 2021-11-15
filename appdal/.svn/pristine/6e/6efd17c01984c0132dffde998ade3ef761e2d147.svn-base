<?php

/**
 * 是否含税
 * @param null $type
 * @return mixed
 */
function get_enum($type = null,$status="PUR_ALTERNATIVE_CHANGE_TYPE")
{
    $types= _getStatusList($status);
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}