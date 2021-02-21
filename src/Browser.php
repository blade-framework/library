<?php

namespace Blade\Library;

use Blade\Browser\Cookie;
use Blade\Browser\Request;
use Blade\Browser\Response;

/**
 * 模拟浏览器请求web服务
 * - 所有的请求信息在request对象中操作
 * - 所有的响应信息在response对象中
 * - 仅支持http和https请求
 * - post请求支持表单和文本
 * - 同一个域名使用同一个本实例时，资源自动复用
 * - 不同域名时请重新创建实例
 *
 * @property Request $request
 * @property Cookie $cookie
 */
class Browser
{
    // post请求类型
    const POST_FORM = 1;
    const POST_PAYLOAD = 2;
    // 限定访问的域名和协议类型
    protected $host;
    protected $prototype;
    // 访问历史
    public $history = [];
    protected $lastPage;
    // 是否启用cookie
    public $cookieEnable = true;
    // curl资源
    protected $resource;

    /**
     * 初始化，绑定域名和协议
     * @param string $host
     * @param string $prototype
     */
    public function __construct(string $host, string $prototype = 'http')
    {
        $this->host = $host;
        $this->prototype = $prototype;
        $this->request = new Request();
        $this->cookie = new Cookie($this->host);
    }

    /**
     * 关闭curl资源
     */
    public function __destruct()
    {
        if ($this->resource) {
            curl_close($this->resource);
        }
    }

    /**
     * 将链接填充完整
     * @param string $url
     * @return string
     */
    public function fullUrl(string $url): string
    {
        $url = trim($url);

        // 原本就完整的链接不处理
        if ('http://' === substr($url, 0, 7) || 'https://' === substr($url, 0, 8)) {
            return $url;
        }

        // 链接为 :// 开头时，加协议类型即可，协议类型优先取prototype，啥都没提供就默认http
        if ('://' === substr($url, 0, 3)) {
            return $this->prototype . $url;
        }
        // 链接为 // 开头时，增加协议类型加冒号补全
        if ('//' === substr($url, 0, 2)) {
            return $this->prototype . ':' . $url;
        }
        // 链接以 / 开头时，补全协议类型和域名，域名优先取domain，啥都没提供就原文返回
        if ('/' === substr($url, 0, 1)) {
            return $this->prototype . '://' . $this->host . $url;
        }
        // 其余情况则是当前链接的路径同级，如果没提供page则默认根目录
        return $this->prototype . '://' . $this->host . (!empty($this->lastPage) ? dirname(parse_url($this->lastPage, PHP_URL_PATH)) : '') . '/' . $url;
    }

    /**
     * 模拟GET请求
     * @param string $url
     * @return Response|null
     */
    public function get(string $url): string
    {
        return $this->curl($url);
    }

    /**
     * 模拟POST请求
     * @param string $url
     * @param array $data
     * @param int $type
     * @return Response|null
     */
    public function post(string $url, array $data = [], int $type = self::POST_FORM): string
    {
        return $this->curl($url, $data, $type);
    }

    /**
     * 配置curl并执行
     * @param string $url
     * @param array|null $data
     * @param int $type
     * @return Response|null
     */
    protected function curl(string $url, array $data = null, int $type = self::POST_FORM): ?Response
    {
        // curl设置
        if (!$this->resource) {
            $this->resource = curl_init();
        }
        curl_setopt($this->resource, CURLOPT_URL, $this->fullUrl($url));
        curl_setopt($this->resource, CURLOPT_HEADER, TRUE);
        curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, 1);
        // header
        $header = $this->request->getHeader();
        if ($this->cookieEnable) {
            $header[] = 'cookie: ' . $this->cookie->toString();
        }
        curl_setopt($this->resource, CURLOPT_HTTPHEADER, $header);
        // referer
        if (!empty($this->lastPage)) {
            curl_setopt($this->resource, CURLOPT_REFERER, $this->lastPage);
        }
        // post
        if (!empty($data)) {
            curl_setopt($this->resource, CURLOPT_POST, 1);
            if (self::POST_PAYLOAD === $type) {
                curl_setopt($this->resource, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($this->resource, CURLOPT_POSTFIELDS, $data);
            }
        }
        // send
        $context = curl_exec($this->resource);
        if (empty($context)) {
            return null;
        }
        // 生成响应对象
        list($header, $body) = explode("\r\n\r\n", $context);
        $response = new Response($header, $body);
        // 如果请求结果是200则记录请求历史
        $this->history[] = $url;
        $this->lastPage = $url;

        return $response;
    }
}