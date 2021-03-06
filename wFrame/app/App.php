<?php
/**
 * Created by PhpStorm.
 * User: 40466
 * Date: 2018/3/7
 * Time: 16:30
 */

namespace wFrame\app;

/**
 * Class App
 * @package wFrame\app
 */
class App
{
    /**
     * @var mixed|string  当前访问路由
     */
    public $url;
    /**
     * @var mixed|string 当前访问真实路由
     */
    public $realUrl;
    /**
     * @var string 匹配的控制器路径
     */
    public $realController;
    /**
     * @var string 当前访问控制器
     */
    public $controller;
    /**
     * @var string 当前访问方法
     */
    public $action;
    /**
     * @var mixed get参数
     */
    public $get;
    /**
     * @var mixed post参数
     */
    public $post;
    /**
     * @var App 自生对象
     */
    private static $instans;

    /**
     * App constructor.
     */
    private function __construct()
    {
        $this->get = $this->handle($_GET);
        $this->post = $this->handle($_POST);
        $this->realUrl = self::getUrl(true);
        $this->url = self::getUrl();
        $this->translate();
    }

    /**
     * 单例入口方法
     * @return App
     */
    public static function app()
    {
        if (!self::$instans) {
            self::$instans = new self();
        }
        return self::$instans;
    }

    /**
     * 获取自定义响应头参数
     * @param string $key
     * @return string
     */
    public function headers($key = '')
    {
        if ($key) {
            $key = 'HTTP_' . strtoupper($key);
            if (isset($_SERVER[$key])) {
                $key = $this->handle($_SERVER[$key]);
            }
        }
        return $key;
    }

    /**
     * 是否是AJAX提交
     * @return bool
     */
    public function isAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' ? true : false;
    }

    /**
     * 是否是GET提交
     * @return bool
     */
    public function isGet()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET' ? true : false;
    }

    /**
     * 是否是POST提交
     * @return bool
     */
    public function isPost()
    {
        return isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' ? true : false;
    }

    /**
     * 特殊字符过滤
     * @param $params
     * @param string $ruleName
     * @return mixed
     */
    public function handle($params, $ruleName = 'params')
    {
        if ($params) {
            $filter = CONFIG['Filter'];
            isset($filter[$ruleName]) ? $rule = $filter[$ruleName] : $rule = '';
            if (is_array($params)) {
                foreach ($params as &$v) {
                    $v = preg_replace($rule, '', $v);
                }
            } else {
                $params = preg_replace($rule, '', $params);
            }
        }
        return $params;
    }

    /**
     * redis缓存
     * @return object RedisCache
     */
    public function redis($config = CONFIG['Redis'])
    {
        return RedisCache::redis($config);
    }

    /**
     * 文件缓存
     * @param $config
     * @return object|FileCache
     */
    public function cache($config = CONFIG['Cache']['cachePath'])
    {
        return FileCache::cache($config);
    }

    /**
     * cookie
     * @return object Cookie
     */
    public function cookie()
    {
        return new Cookie();
    }

    /**
     * 翻译路由
     */
    private function translate()
    {
        $arr = explode('/', $this->url);
        $count = count($arr);
        $dir = '';
        if ($count > 3) {
            $dir = array_slice($arr, 1, $count - 3);
            $dir = implode($dir, '\\') . '\\';
        }
        $this->controller = ucfirst($arr[$count - 2]) . 'Controller';
        $this->realController = 'app\controller\\' . $dir . ucfirst($arr[$count - 2]) . 'Controller';
        $this->action = 'action' . ucfirst($arr[$count - 1]);
    }

    /**
     * 获取处理后的URL
     * @param bool $real
     * @return mixed|string
     */
    private static function getUrl($real = false)
    {
        $url = $_SERVER["REQUEST_URI"];
        $bad = strstr($url, '?');
        if ($bad) {
            $url = str_replace($bad, '', $url);
        }
        if ($real) {
            return $url;
        }
        if ($url == '/') {
            $url .= CONFIG['defaultRoute'];
        }
        $prettifyUrl = CONFIG['PrettifyUrl'];
        if ($prettifyUrl['switch'] && isset($prettifyUrl['rule'][$url])) {
            $url = $prettifyUrl['rule'][$url];
        }
        return $url;
    }
}