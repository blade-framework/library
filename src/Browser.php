<?php

namespace Blade\Library;

/**
 * Class Browser
 * @package Blade\Library
 */
class Browser
{
    const UA_PC = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36';
    const UA_MOBILE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1';

    const POST_FORM = 1;
    const POST_PAYLOAD = 2;

    public $host;
    public $prototype;
    public $headers = [];
    public $cookies = [];
    public $responseHeaders = [];
    public $responseCode;
    public $responseBody;

    public $lastPage;

    /**
     * Browser constructor.
     * @param string $host
     * @param string $prototype
     */
    public function __construct(string $host, string $prototype = 'http')
    {
        $this->host = $host;
        $this->prototype = $prototype;
    }

    /**
     * TODO：将链接填充完整
     * @param string $url
     * @return string
     */
    public function fullUrl(string $url): string
    {
        $url = trim($url);

        // TODO：原本就完整的链接不处理
        if ('http://' === substr($url, 0, 7) || 'https://' === substr($url, 0, 8)) {
            return $url;
        }

        // TODO：链接为 :// 开头时，加协议类型即可，协议类型优先取prototype，啥都没提供就默认http
        if ('://' === substr($url, 0, 3)) {
            return $this->prototype . $url;
        }
        // TODO：链接为 // 开头时，增加协议类型加冒号补全
        if ('//' === substr($url, 0, 2)) {
            return $this->prototype . ':' . $url;
        }
        // TODO：链接以 / 开头时，补全协议类型和域名，域名优先取domain，啥都没提供就原文返回
        if ('/' === substr($url, 0, 1)) {
            return $this->prototype . '://' . $this->host . $url;
        }
        // TODO：其余情况则是当前链接的路径同级，如果没提供page则默认根目录
        return $this->prototype . '://' . $this->host . (!empty($this->lastPage) ? dirname(parse_url($this->lastPage, PHP_URL_PATH)) : '') . '/' . $url;
    }

    /**
     * TODO：模拟GET请求
     * @param string $url
     * @return string
     */
    public function get(string $url): string
    {
        return self::curl($url);
    }

    /**
     * TODO：模拟POST请求
     * @param string $url
     * @param array $data
     * @param int $type
     * @return string
     */
    public function post(string $url, array $data = [], int $type = self::POST_FORM): string
    {
        return self::curl($url, $data, $type);
    }

    /**
     * TODO：将消息头配置转换成请求头
     * @return array
     */
    protected function toHeader(): array
    {
        $header = [];
        // TODO：请求头
        foreach ($this->headers as $name => $value) {
            $header[] = is_int($name) ? $value : "{$name}: {$value}";
        }
        // TODO：加入cookie
        $cookie = [];
        foreach ($this->cookies as $name => $value) {
            $cookie[] = $name . '=' . urlencode($value);
        }
        $header[] = 'cookie: ' . implode('; ', $cookie);
        return $header;
    }

    /**
     * TODO：配置curl并执行
     * @param string $url
     * @param array|null $data
     * @param int $type
     * @return string
     */
    protected function curl(string $url, array $data = null, int $type = self::POST_FORM): string
    {
        // TODO：curl设置
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->fullUrl($url));
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->toHeader());
        // TODO：referer
        if (!empty($this->lastPage)) {
            curl_setopt($curl, CURLOPT_REFERER, $this->lastPage);
        }
        // TODO：post
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            if (self::POST_PAYLOAD === $type) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
        // TODO：send
        list($header, $this->responseBody) = explode("\r\n\r\n", curl_exec($curl));
        // TODO：header
        $this->setResponseHeader($header);
        // TODO：close
        curl_close($curl);
        // TODO：referer
        $this->lastPage = $url;

        return $this->responseBody;
    }

    /**
     * TODO：解析响应头
     * @param string $headerString
     */
    protected function setResponseHeader(string $headerString): void
    {
        // TODO：按行分割响应头
        $header = explode("\r\n", $headerString);
        // TODO：取出状态
        $status = array_shift($header);
        // TODO：取出响应代码
        if (preg_match('/^HTTP\/1\.1 (\d+)/i', $status, $matches)) {
            $this->responseCode = $matches[1];
        }
        // TODO：解出响应头
        $this->responseHeaders = [];
        foreach ($header as $value) {
            if (preg_match('/^Set-Cookie: (.*?)=(.*?);/i', $value, $matches)) {
                $this->cookies[$matches[1]] = $matches[2];
            } else {
                list($name, $value) = explode(':', $value);
                $this->responseHeaders[strtolower(trim($name))] = trim($value);
            }
        }
    }
}