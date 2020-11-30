<?php

namespace Blade\Library;

/**
 * Class Config
 * @package Blade\Library
 *
 * TODO：通用配置管理类
 * 主要用于配置的管理和使用，所有添加进来的配置都会以本类对象存储，所以获取时都将返回本类对象
 */
class Config
{
    protected $_config;

    /**
     * Config constructor.
     * @param array|object $config
     */
    public function __construct($config)
    {
        $this->_config = new \stdClass();
        $this->merge($config);
    }

    public function print()
    {
        print_r($this->_config);
    }

    /**
     * TODO：取出指定名称的配置数据
     * @param string $name
     * @return self|mixed|null
     */
    public function get(string $name)
    {
        return $this->_config->{$name} ?? null;
    }

    /**
     * TODO：指定一个名称路径，名称直接使用小数点“.”连接，返回找到的配置
     * @param string $names
     * @return self|mixed|null
     */
    public function find(string $names)
    {
        $find = $this;
        if (!empty($names)) {
            foreach (explode('.', $names) as $name) {
                if ($find instanceof self) {
                    $find = $find->get($name);
                } else {
                    return null;
                }
            }
        }
        return $find;
    }

    /**
     * TODO：设置一个配置数据，name相同时会覆盖原先数据；当data为null时，删除原先数据
     * @param string $name
     * @param mixed|null $data
     */
    public function set(string $name, $data = null): void
    {
        if (null === $data) {
            // TODO：删除数据
            unset($this->_config->{$name});
        } elseif (is_array($data) || is_object($data)) {
            // TODO：数据是数组或对象时，转换为本类对象
            $this->_config->{$name} = new self($data);
        } else {
            // TODO：其余类型数据直接存放
            $this->_config->{$name} = $data;
        }
    }

    /**
     * TODO：将一个配置数据合并到当前配置中，会自动递归合并
     * @param array|object $config
     * @return $this
     */
    public function merge($config): self
    {
        if (is_object($config)) {
            // TODO：对象先转为数组
            if (method_exists($config, 'toArray') && is_callable($config, 'toArray')) {
                // TODO：如果对象自带数组转换方法，则使用自带转换方法
                if (!is_array($config = call_user_func([$config, 'toArray']))) {
                    // TODO：如果自带数组转换方法返回的数据不是数组则视为无效数据
                    return $this;
                }
            } else {
                // TODO：对象未有自带数组转换方法，则使用原生方法获取对象的数组
                $config = get_object_vars($config);
            }
        }
        // TODO：如果处理后不是数组，也视为无效数据
        if (!is_array($config)) {
            return $this;
        }
        // 遍历数组，合并到当前对象中
        foreach ($config as $name => $data) {
            $this->set($name, $data);
        }
        return $this;
    }

    /**
     * TODO：将配置数据转换为数组返回，自动递归转换
     * @return array
     */
    public function toArray(): array
    {
        $arr = [];
        foreach ((array)$this->_config as $name => $data) {
            // TODO：数据是对象时，转成数组
            if (is_object($data)) {
                if (method_exists($data, 'toArray') && is_callable([$data, 'toArray'])) {
                    // TODO：如果对象自带数组转换方法，则使用自带转换方法
                    if (!is_array($data = call_user_func([$data, 'toArray']))) {
                        // TODO：如果自带数组转换方法返回的数据不是数组则视为无效数据
                        continue;
                    }
                } else {
                    // TODO：对象未有自带数组转换方法，则使用原生方法获取对象的数组
                    $data = get_object_vars($data);
                }
            }
            $arr[$name] = $data;
        }
        return $arr;
    }

    /**
     * TODO：从一个配置文件中读取配置，返回配置对象
     * @param string $file
     * @return static
     */
    public static function read(string $file): self
    {
        if (!($file = realpath($file)) || !file_exists($file) || !($data = file_get_contents($file)) || !($data = json_decode($data))) {
            $data = [];
        }
        return new self($data);
    }

    /**
     * TODO：将一个配置对象保存到配置文件中，保存成功返回true，保存失败返回false
     * TIP：保存失败一般是由于文件路径不正确/文件及文件夹没有写入权限
     * @param string $file
     * @param Config $config
     * @return bool
     */
    public static function write(string $file, self $config): bool
    {
        // TODO：如果配置文件的目录不存在则尝试创建
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }
        return false !== file_put_contents($file, json_encode($config->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}