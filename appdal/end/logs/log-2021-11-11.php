<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

ERROR - 2021-11-11 13:50:31 --> Severity: error --> Exception: Call to a member function from() on null /home/wwwroot/purchase_dev/appdal/end/controllers/Scree.php 1802
ERROR - 2021-11-11 13:51:12 --> Severity: error --> Exception: Call to a member function last_query() on null /home/wwwroot/purchase_dev/appdal/end/controllers/Scree.php 1803
ERROR - 2021-11-11 15:08:00 --> Query error: Unknown column 'b.user_id' in 'on clause' - Invalid query: SELECT `a`.`user_id`
FROM `pur_purchase_user_relation` `a`
JOIN `pur_purchase_user_group_relation` `b` ON `b`.`user_id`=`a`.`user_id`
WHERE `b`.`group_id` IN(16)
AND `a`.`is_enable` = 1
AND `a`.`is_del` = 0
GROUP BY `a`.`user_id`
ERROR - 2021-11-11 15:20:32 --> Query error: You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ')
AND `apply_time` >= '2021-08-03 15:20:32'
AND `apply_time` <= '2021-11-11 15:2' at line 4 - Invalid query: SELECT COUNT(*) as count, `sku`
FROM `pur_product_scree`
WHERE `sku` IN('YS00903')
AND `apply_user_id` IN()
AND `apply_time` >= '2021-08-03 15:20:32'
AND `apply_time` <= '2021-11-11 15:20:32'
AND `status` = 50
GROUP BY `sku`
ERROR - 2021-11-11 15:20:45 --> Query error: You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ')
AND `apply_time` >= '2021-08-03 15:20:45'
AND `apply_time` <= '2021-11-11 15:2' at line 4 - Invalid query: SELECT COUNT(*) as count, `sku`
FROM `pur_product_scree`
WHERE `sku` IN('YS00903')
AND `apply_user_id` IN()
AND `apply_time` >= '2021-08-03 15:20:45'
AND `apply_time` <= '2021-11-11 15:20:45'
AND `status` = 50
GROUP BY `sku`
