<?php

namespace Blade\Library\Browser;

use Blade\Library\Dataset;

/**
 * 模拟浏览器-cookie
 * - 可以通过全局/局部设置cookie是否缓存到文件
 * - 可以通过全局/局部设置数据是否永久不过期
 */
class Cookie
{
    // 全局过期时间，是当前时间加上这个时间
    protected static $expireTime = 86400;
    // 全局过期机制，为true则永不过期
    protected static $expireNever = false;
    // 局部过期机制，为true则永不过期，为null则继承全局
    protected $_expireNever = null;

    // 全局cookie文件存放目录，目录不存在时会尝试创建
    protected static $cacheDir = null;
    // 局部cookie文件存放路径，文件或目录不存在时会尝试创建
    protected $_cacheFile = null;

    // 全局是否自动保存
    protected static $autoSave = true;

    // cookie所属域名
    protected $domain;
    // cookie数据
    protected $cookies;

    /**
     * Cookie constructor.
     * @param string $domain
     */
    public function __construct(string $domain)
    {
        $this->domain = $domain;
        $this->cookies = new Dataset([]);
    }

    /**
     * 设置一个cookie数据，过期机制可填：null为继承全局，0为会话级过期，大于0则设置指定过期时间，小于0为立刻删除
     * @param string $name
     * @param mixed $data
     * @param int|null $expire
     * @return $this
     */
    public function set(string $name, $data, int $expire = null): self
    {
        if ($expire < 0) {
            $this->cookies->set($name, null);
            return $this;
        }
        $expireTime = 0 === $expire ? 0 : (time() + (null === $expire ? static::$expireTime : $expire));
        $this->cookies->set($name, ['data' => $data, 'expire' => $expireTime]);
        // 自动保存
        if (static::$autoSave) {
            $this->write();
        }
        return $this;
    }

    /**
     * 获取cookie
     * @param string|null $name
     * @return array|string|null
     */
    public function get(string $name = null)
    {
        $time = time();
        $expire = null === $this->_expireNever ? static::$expireNever : $this->_expireNever;
        if (null === $name) {
            $result = [];
            $data = $this->cookies->toArray();
            foreach ($data as $name => $item) {
                if ($expire || $item['expire'] > $time) {
                    $result[$name] = $item['data'];
                }
            }
            return $result;
        }
        $data = $this->cookies->get($name);
        return $expire || $data->get('expire') > $time ? $data->get('data') : null;
    }

    /**
     * 生成cookie字符串
     * @return string
     */
    public function toString(): string
    {
        $cookies = $this->get();
        $string = [];
        foreach ($cookies as $name => $cookie) {
            $string[] = "{$name}={$cookie}";
        }
        return implode('; ', $string);
    }

    /**
     * 从文件中读取cookie数据
     * @return $this
     */
    public function read(): self
    {
        // 没有设定局部文件时，会根据全局配置自动生成
        if (!$this->_cacheFile) {
            if (!static::$cacheDir) {
                return $this;
            }
            $this->setCacheFile(rtrim(static::$cacheDir, '/') . "{$this->domain}.cookie");
        }
        Dataset::read($this->_cacheFile, $this->cookies);
        return $this;
    }

    /**
     * 将cookie写入文化部
     * @return $this
     */
    public function write(): self
    {
        if (!$this->_cacheFile) {
            if (!static::$cacheDir) {
                return $this;
            }
            $this->setCacheFile(rtrim(static::$cacheDir, '/') . "{$this->domain}.cookie");
        }
        Dataset::write($this->_cacheFile, $this->cookies);
        return $this;
    }

    /**
     * 设置全局数据过期时间
     * @param int $time
     */
    public static function setGlobalExpireTime(int $time): void
    {
        static::$expireTime = $time;
    }

    /**
     * 设置全局过期机制
     * @param bool $never
     */
    public static function setGlobalExpire(bool $never): void
    {
        static::$expireNever = $never;
    }

    /**
     * 设置局部过期机制
     * @param bool $never
     */
    public function setExpire(bool $never): void
    {
        $this->_expireNever = $never;
    }

    /**
     * 设置全局cookie文件目录
     * @param string $dir
     * @return bool
     */
    public static function setGlobalCacheDir(string $dir): bool
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }
        static::$cacheDir = realpath($dir);
        return true;
    }

    /**
     * 设置局部cookie文件
     * @param string $file
     * @return bool
     */
    public function setCacheFile(string $file): bool
    {
        $dir = dirname($file);
        if (!is_dir($dir) && (!mkdir($dir, 0755, true)) || !file_put_contents($file, '{}')) {
            return false;
        }
        $this->_cacheFile = realpath($file);
        return true;
    }

    /**
     * 设置全局自动保存
     * @param bool $auto
     */
    public static function setGlobalAutoSave(bool $auto): void
    {
        static::$autoSave = $auto;
    }
}