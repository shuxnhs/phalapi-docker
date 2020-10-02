<?php
require_once dirname(__FILE__) . '/../public/init.php';
echo date("Y-m-d H:i:s").PHP_EOL;

//  */1 * * * * docker exec phalapi php /var/www/html/phalapi/bin/testCrontab.php >> /var/log/testCrontab.log
