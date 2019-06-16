<?php
/**
 * Description: 漏斗限流入口文件
 * User: guozhaoran<guozhaoran@cmcm.com>
 * Date: 2019-06-16
 */
require_once('./RateLimit.php');
ini_set('display_errors', true);

$org = $_GET['org'];
$pathInfo = $_GET['path_info'];

$result = (new RateLimit($org, $pathInfo))->isActionAllowed();

$handler = fopen('./stat.log', 'a') or die('can not open file!');
if ($result) {
    fwrite($handler, 'request success!' . PHP_EOL);
} else {
    fwrite($handler, 'request failed!' . PHP_EOL);
}
fclose($handler);
