<?php declare(strict_types=1);

namespace FroshPluginUploader\Components;

use FroshPluginUploader\Structs\Config\PluginConfig;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class Util
{
    public static $configDirectory = "config/plugins/";

    public static $possiblePluginDirs = [
        "custom\plugins",
        "Plugins\Community\Backend",
        "Plugins\Community\Frontend",
        "Plugins\Local\Backend",
        "Plugins\Local\Frontend",
        "engine\Shopware\Plugins\Community\Backend",
        "engine\Shopware\Plugins\Community\Frontend",
        "engine\Shopware\Plugins\Local\Backend",

    ];

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

    /**
     * @param string|null $prefix
     *
     * @return string
     * @throws \Exception
     */
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

    /**
     * @param string $tmpFolder
     *
     * @return mixed
     */
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

    /**
     * If hasPath is false, all Plugin-configs will be gathered
     *
     * @return array
     */
    public static function getPluginConfigs(): array
    {
        /**
         * Add config directory to root if there where no directory before.
         * It will do none of the directory already exists.
         */
        $filesystem = new Filesystem();
        $filesystem->mkdir(self::$configDirectory);

        /**
         * If directory exists, select all containing json files and returning
         * them in an array, indexed by its config id specified at shopware account backend.
         */
        $finder = new Finder();
        $finder->files()->in(self::$configDirectory);

        $plugins = [];

        /**
         * @var PluginConfig $config ;
         */
        foreach ($finder as $file) {
            $config               = json_decode($file->getContents());
            $plugins[$config->id] = $config;
        }

        return $plugins;
    }

    /**
     * Scans directory for plugin. If there is no plugin in on of the possible directories
     * it returns null, otherwise it will return the path where the plugin was found.
     * The path does not contain the name of the plugin.
     *
     * @param string $pluginName
     *
     * @return string|null
     */
    public static function searchPluginPaths(string $pluginName): ?string
    {
        $shopwareRoot       = self::getEnv('SHOPWARE_ROOT');
        $possiblePluginDirs = self::$possiblePluginDirs;

        foreach ($possiblePluginDirs as $possiblePluginDir) {
            $path = "{$shopwareRoot}/{$possiblePluginDir}";
            if (file_exists("{$path}/{$pluginName}")) {
                return realpath($path);
            }
        }

        return null;
    }
}
