<?php

namespace Blade\Browser;

use Blade\Library\Browser;

/**
 * 模拟浏览器-请求器
 * - 可以设置请求头、请求类型、请求数据等
 * - 自动附带cookie，可以设置关闭
 */
class Request
{
    // 模拟浏览器user-agent
    const UA_PC = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36';
    const UA_MOBILE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1';
    // 请求头
    protected $headers = [];

    /**
     * 设置一个或多个请求头
     * @param string|array $name
     * @param string|null $value
     * @return $this
     */
    public function setHeader($name, string $value = null): self
    {
        if (is_string($name)) {
            if (null == $value) {
                unset($this->headers[$name]);
            } else {
                $this->headers[$name] = $value;
            }
        } elseif (is_array($name)) {
            $this->headers = array_merge($this->headers, $name);
        }
        return $this;
    }

    /**
     * 获取一个或全部请求头
     * @param string|null $name
     * @return array|string|null
     */
    public function getHeader(string $name = null)
    {
        if (null === $name) {
            $result = [];
            foreach ($this->headers as $name => $header) {
                $result[] = "{$name}: {$header}";
            }
            return $result;
        }
        return $this->headers[$name] ?: null;
    }
}