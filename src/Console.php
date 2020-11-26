<?php

namespace Blade\Library;

/**
 * Class Console
 * @package Blade\Library
 */
class Console
{
    const CMD_CLEAR = "\033\133\110\033\133\062\112"; // 控制台命令--清屏
    const FORMAT_MARK = ['<R~', '<G~', '<B~', '<P~', '<Y~', '<Q~', '~>'];
    const FORMAT_CODE = ["\033[31m", "\033[32m", "\033[34m", "\033[35m", "\033[33m", "\033[36m", "\033[0m"];

    /**
     * TODO：输出格式化内容到控制台
     * @param string $format
     * @param mixed ...$arg
     */
    public static function print(string $format, ...$arg): void
    {
        $format = str_replace(self::FORMAT_MARK, self::FORMAT_CODE, $format);
        printf($format . "\n", ...$arg);
    }

    /**
     * TODO：在控制台打印内容
     * @param mixed ...$message
     */
    public static function trace(...$message): void
    {
        echo date('Y-m-d H:i:s') . PHP_EOL;
        foreach ($message as $m) {
            if (is_array($m) || is_object($m)) {
                echo json_encode($m, JSON_UNESCAPED_UNICODE) . PHP_EOL;
            } elseif (is_string($m)) {
                echo "\r{$m}\n";
            } else {
                var_dump($m);
            }
        }
        echo PHP_EOL;
    }

    /**
     * TODO：清屏
     */
    public static function clearScreen(): void
    {
        printf(self::CMD_CLEAR);
    }

    /**
     * 获取指定位置的命令行参数，0为入口文件名
     * @param int $index
     * @return string|null
     */
    public static function get(int $index): ?string
    {
        global $argv;
        return $argv[$index] ?? null;
    }

    /**
     * TODO：获取指定开关是否打开
     * @param string $name
     * @return bool
     */
    public static function enable(string $name): bool
    {
        global $argv;
        return in_array('-' . $name, $argv);
    }

    /**
     * TODO：获取指定设置内容
     * @param string $name
     * @return string|null
     */
    public static function set(string $name): ?string
    {
        global $argv;
        foreach ($argv as $arg) {
            if (preg_match("/^{$name}=(.^?)$/", $arg, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * TODO：获取指定开关名称的配置值
     * @param string $name
     * @param bool $hasBar
     * @return string|null
     */
    public static function enableSet(string $name, bool $hasBar = false): ?string
    {
        global $argv;
        $len = count($argv);
        $hasBar && ($name = '-' . $name);
        for ($i = 0; $i < $len; $i++) {
            if ($argv[$i] === $name && isset($argv[$i + 1])) {
                return $argv[$i + 1];
            }
        }
        return null;
    }

    /**
     * TODO：引导用户输入，返回输入内容
     * @param string $tips
     * @return string
     */
    public static function input(string $tips = ''): string
    {
        fwrite(STDOUT, $tips);
        $msg = fgets(STDIN);
        // TODO：如果接收内容为null时，退出进程
        if (null === $msg) {
            exit(0);
        }
        return trim($msg);
    }

    /**
     * TODO：取出指定范围的命令行参数，返回数组
     * @param int $start
     * @param int $end
     * @return array
     */
    public static function take(int $start = 0, int $end = -1): array
    {
        global $argv;
        $length = count($argv);
        $start = 0 > $start ? ($length - $start) : $start;
        $end = -1 === $end ? ($length - 1) : min($length - 1, $end);
        $arr = [];
        for ($i = $start; $i <= $end; $i++) {
            $arr[] = $argv[$i];
        }
        return $arr;
    }
}