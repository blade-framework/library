<?php

namespace Blade\Library;

/**
 * 通用数据集
 * - 使用set方法和merge方法可以将数组和对象转换成数据集对象
 * - 使用get方法可以获取指定名称的数据
 * - 使用find方法可以深度获取数据
 * - 使用toArray方法可以将数据集转换为数组
 * - 使用read和write方法可以快捷的读取和存储数据文件
 *
 * 注意
 * - set和merge提供的对象都将转换为数据集对象，而非原本的对象
 */
class Dataset
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
     * 取出指定名称的数据
     * @param string $name
     * @return self|mixed|null
     */
    public function get(string $name)
    {
        return $this->_config->{$name} ?? null;
    }

    /**
     * 指定一个名称路径，名称直接使用小数点“.”连接，返回找到的数据
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
     * 设置一个数据，name相同时会覆盖原先数据；当data为null时，删除原先数据
     * @param string $name
     * @param mixed|null $data
     */
    public function set(string $name, $data = null): void
    {
        if (null === $data) {
            // 删除数据
            unset($this->_config->{$name});
        } elseif (is_array($data) || is_object($data)) {
            // 数据是数组或对象时，转换为本类对象
            $this->_config->{$name} = new self($data);
        } else {
            // 其余类型数据直接存放
            $this->_config->{$name} = $data;
        }
    }

    /**
     * 将一个数据合并到当前数据集中，会自动递归合并
     * @param array|object $config
     * @return $this
     */
    public function merge($config): self
    {
        if (is_object($config)) {
            // 对象先转为数组
            if (method_exists($config, 'toArray') && is_callable($config, 'toArray')) {
                // 如果对象自带数组转换方法，则使用自带转换方法
                if (!is_array($config = call_user_func([$config, 'toArray']))) {
                    // 如果自带数组转换方法返回的数据不是数组则视为无效数据
                    return $this;
                }
            } else {
                // 对象未有自带数组转换方法，则使用原生方法获取对象的数组
                $config = get_object_vars($config);
            }
        }
        // 如果处理后不是数组，也视为无效数据
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
     * 将数据集转换为数组返回，自动递归转换
     * @return array
     */
    public function toArray(): array
    {
        $arr = [];
        foreach ((array)$this->_config as $name => $data) {
            // 数据是对象时，转成数组
            if (is_object($data)) {
                if (method_exists($data, 'toArray') && is_callable([$data, 'toArray'])) {
                    // 如果对象自带数组转换方法，则使用自带转换方法
                    if (!is_array($data = call_user_func([$data, 'toArray']))) {
                        // 如果自带数组转换方法返回的数据不是数组则视为无效数据
                        continue;
                    }
                } else {
                    // 对象未有自带数组转换方法，则使用原生方法获取对象的数组
                    $data = get_object_vars($data);
                }
            }
            $arr[$name] = $data;
        }
        return $arr;
    }

    /**
     * 从一个缓存文件中读取，读取成功返回true，object为数据集，读取失败返回false，object为空数据集
     * @param string $file
     * @param static $object
     * @return bool
     */
    public static function read(string $file, &$object): bool
    {
        $result = true;
        if (!($file = realpath($file)) || !file_exists($file) || !($data = file_get_contents($file)) || !($data = json_decode($data))) {
            $data = [];
            $result = false;
        }
        $object = $object instanceof self ? $object->merge($data) : new static($data);
        return $result;
    }

    /**
     * 将一个数据集保存到缓存文件中，保存成功返回true，保存失败返回false
     * @tip 保存失败一般是由于文件路径不正确/文件及文件夹没有写入权限
     * @param string $file
     * @param static $config
     * @return bool
     */
    public static function write(string $file, self $config): bool
    {
        // 如果文件的目录不存在则尝试创建
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            return false;
        }
        return false !== file_put_contents($file, json_encode($config->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}