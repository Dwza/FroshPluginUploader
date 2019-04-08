<?php
/**
 * Created by PhpStorm.
 * User: dwayne.sharp
 * Date: 04.04.2019
 * Time: 10:50
 */

namespace FroshPluginUploader\Commands;

use FroshPluginUploader\Components\SBP\Client;
use FroshPluginUploader\Components\Util;
use FroshPluginUploader\Structs\Plugin;
use FroshPluginUploader\Structs\Config\PluginConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Filesystem\Filesystem;

class ConfigurePluginsCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Filesystem $filesystem
     */
    private $filesystem;

    /**
     * @var SymfonyStyle $io
     */
    private $io;

    public function __construct(string $name = null)
    {
        parent::__construct($name);

        $this->filesystem = new Filesystem();
    }

    protected function configure()
    {
        $this->setName('plugin:create:configs')->setDescription(
            'Creates config-files for each plugin to handle multiple plugins'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Util::getEnv('SHOPWARE_ROOT')) {
            throw new \RuntimeException(
                'The environment variable SHOPWARE_ROOT is required for this command. Please edit your .env file.'
            );
        }

        $this->io      = new SymfonyStyle($input, $output);
        $pluginConfigs = Util::getPluginConfigs();
        $client        = $this->container->get(Client::class);
        $plugins       = $client->Producer()->getPlugins($client->Producer()->getProducer()->id);

        foreach ($plugins as $plugin) {
            if (false !== array_search($plugin->name, array_column((array)$pluginConfigs, 'name'))) {
                $this->updateConfig($plugin, $pluginConfigs[$plugin->id]);
            } else {
                $this->createConfig($plugin);
            }
        }
        $this->io->success('Done');
    }

    /**
     * @param Plugin       $plugin
     */
    private function createConfig($plugin)
    {
        $pluginConfig             = new PluginConfig();
        $pluginConfig->id         = $plugin->id;
        $pluginConfig->name       = $plugin->name;
        $pluginConfig->version    = $plugin->latestBinary->version;
        $pluginConfig->status     = $plugin->activationStatus->description;
        $pluginConfig->lastChange = $plugin->lastChange;

        $this->setPluginPath($pluginConfig);
        $this->saveYaml($pluginConfig);
        $this->io->note("Created {$plugin->name}.json in config/plugins");
    }

    /**
     * @param Plugin       $plugin
     * @param PluginConfig $pluginConfig
     */
    private function updateConfig($plugin, $pluginConfig)
    {
        if (!isset($pluginConfig->version) || $pluginConfig->version !== $plugin->latestBinary->version) {
            $pluginConfig->version = $plugin->latestBinary->version;
            $this->io->note("Updated Version in {$pluginConfig->name}.json");
        }

        if (!isset($pluginConfig->lastChange) || $pluginConfig->lastChange !== $plugin->lastChange) {
            $pluginConfig->lastChange = $plugin->lastChange;
            $this->io->note("Updated lastChange in {$pluginConfig->name}.json");
        }

        if (!isset($pluginConfig->status) || $pluginConfig->status !== $plugin->activationStatus->description) {
            $pluginConfig->status = $plugin->activationStatus->description;
            $this->io->note("Updated status in {$pluginConfig->name}.json");
        }

        $this->setPluginPath($pluginConfig);
        $this->saveYaml($pluginConfig);
    }

    /**
     * @param PluginConfig $pluginConfig
     */
    private function saveYaml($pluginConfig)
    {
        $this->filesystem->dumpFile(
            Util::$configDirectory . $pluginConfig->name . '.json',
            json_encode((array)$pluginConfig, JSON_PRETTY_PRINT)
        );
    }

    /**
     * @param PluginConfig $pluginConfig
     */
    private function setPluginPath($pluginConfig)
    {
        if (!isset($pluginConfig->path) || empty($pluginConfig->path)) {
            $pluginPath = Util::searchPluginPaths($pluginConfig->name);



            if (!empty($pluginPath)) {
                $pluginConfig->path = $pluginPath;
            } else {
                $n = $pluginConfig->name;
                $this->io->caution(
                    "Could not find {$n} in any directory.\r\nPath was set to: null.\r\nMove {$n} to your project or specify the location of this plugin in {$n}.json"
                );



                /*
                $pluginPath = $this->io->ask(
                    "Please enter absolute path to folder that contains {$pluginConfig->name}-Folder: ",
                    getcwd()
                );

                if ($this->filesystem->exists(realpath($pluginPath) . '/' . $pluginConfig->name)) {
                    $pluginConfig->path = $pluginPath;
                    $this->io->success("Path was set. Plugin was found!");
                } else {
                    $this->io->error(
                        "Path does not exists or no Plugin with name {$pluginConfig->name} were found. Path set to: null. Re-Run command."
                    );
                }
                */
            }
        }
    }
}