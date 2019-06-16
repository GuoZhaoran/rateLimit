<?php
/**
 * Description: 漏桶限流
 * User: guozhaoran<guozhaoran@cmcm.com>
 * Date: 2019-06-13
 */

class RateLimit
{
    private $conn = null;       //redis连接
    private $org = '';          //公司标识
    private $pathInfo = '';     //接口路径信息

    /**
     * RateLimit constructor.
     * @param $org
     * @param $pathInfo
     * @param $expire
     * @param $limitReq
     */
    public function __construct($org, $pathInfo)
    {
        $this->conn = $this->getRedisConn();
        $this->org = $org;
        $this->pathInfo = $pathInfo;
    }

    /**
     * 获取lua脚本
     * @return string
     */
    private function getLuaScript()
    {
        $luaScript = <<<LUA_SCRIPT
-- 限制接口访问频次
local times = redis.call('incr', KEYS[1]);    --将key自增1

if times == 1 then
redis.call('expire', KEYS[1], ARGV[1])    --给key设置过期时间
end

if times > tonumber(ARGV[2]) then
return 0
end

return 1
LUA_SCRIPT;

        return $luaScript;
    }

    /**
     * 获取redis连接
     * @return \Predis\Client
     */
    private function getRedisConn()
    {
        require_once('vendor/autoload.php');
        $conn = new Predis\Client(['host' => '127.0.0.1',
            'port' => 6379,]);
        return $conn;
    }

    /**
     * 判断接口是否限制访问
     * @return bool
     */
    public function isActionAllowed()
    {
        $config = $this->conn->hgetall($this->org);
        //配置中没有对接口进行限制
        if (!$config) return true;
        $pathInfoLimitKey = $this->org . $this->pathInfo;
        $ret = $this->conn->evalsha(sha1($this->getLuaScript()), 1,
                $pathInfoLimitKey, $config['expire'], $config['limitReq']);

        return boolval($ret);
    }
}