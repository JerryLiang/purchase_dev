<?php

/**
 * 是否需要中转
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getIsTransferWarehouse($type = null){
    $types = [
        '0'  => '否',
        '1'  => '是',
    ];

    return isset($type) ? $types[$type] : $types;
}