<?php declare(strict_types=1);

namespace FroshPluginUploader\Components;

class Util
{
    /**
     * @param      $name
     * @param bool $default
     *
     * @return array|bool|false|string
     */
    public static function getEnv($name, $default = false)
    {
        $var = getenv($name);

        if (!$var) {
            $var = $default;
        }

        return $var;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool   $remove
     *
     * @return bool
     */
    public static function setEnv($name, $value, $remove = false)
    {
        $bOk = putenv("{$name}={$value}");

        if (!$bOk) {
            $sEnv = "{$name}="; //set env to empty
            if ($remove) {
                $sEnv = "{$name}"; //remove env
            }
            putenv($sEnv);
        }

        return $bOk;
    }

    public static function mkTempDir(?string $prefix = null): string
    {
        if ($prefix === null) {
            $prefix = (string)random_int(PHP_INT_MIN, PHP_INT_MAX);
        }

        $tmpFolder = sys_get_temp_dir() . '/' . uniqid($prefix, true);

        if (!mkdir($tmpFolder) && !is_dir($tmpFolder)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $tmpFolder));
        }

        return $tmpFolder;
    }

    public static function getPluginName(string $tmpFolder)
    {
        return current(
            array_filter(
                scandir($tmpFolder, SCANDIR_SORT_NONE),
                function ($value) {
                    return $value[0] !== '.';
                }
            )
        );
    }
}
