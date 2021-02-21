<?php

namespace Blade\Library\Browser;

/**
 * 模拟浏览器-响应数据
 * - 可以获取所有的响应信息
 * - 所有数据都将公开可读写
 */
class Response
{
    // 响应状态码
    public $statusCode;
    // 状态文本
    public $statusText;
    // 响应头，全部小写
    public $headers = [];
    // 响应正文原文
    public $body;
    // 解析后的正文
    public $data;

    /**
     * 解析响应头，会根据响应的正文类型解析数据
     * @param string $header
     * @param string $body
     * @param Cookie|null $cookie
     */
    public function __construct(string $header, string $body, Cookie $cookie = null)
    {
        // 解析响应头
        $header = explode("\r\n", $header);
        // 第一行是协议、状态码和状态文本
        list(, $this->statusCode, $this->statusText) = explode(' ', array_shift($header));
        // 第二行开始是响应头数据
        foreach ($header as $value) {
            $value = explode(':', $value, 2);
            $name = strtolower(trim($value[0]));
            $value = strtolower(trim($value[1]));
            // 设置cookie
            if ('set-cookie' === $name) {
                if ($cookie) {
                    $this->parseCookie($value, $cookie);
                }
                continue;
            }
            // 保存数据
            $this->headers[$name] = $value;
        }
        // 解析正文
        $this->body = $body;
        $this->parseBody();
    }

    /**
     * 解析cookie文本
     * @param string $cookieText
     * @param Cookie $cookie
     */
    protected function parseCookie(string $cookieText, Cookie $cookie): void
    {
        parse_str(str_replace(['%3D', '%3B+'], ['=', '&'], urlencode($cookieText)), $items);
        $name = array_key_first($items);
        $value = current($items);
        $expire = isset($items['expires']) ? (strtotime($items['expires']) - time()) : 0;
        $cookie->set($name, $value, $expire);
    }

    /**
     * 根据消息头标识的数据类型进行解析，目前支持：json
     */
    protected function parseBody(): void
    {
        if (empty($this->headers['content-type'])) {
            return;
        }
        switch (strtolower($this->headers['content-type'])) {
            case 'application/json':
                $this->data = json_decode($this->body);
                break;
        }
    }
}