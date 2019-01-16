<?php declare(strict_types=1);

namespace FroshPluginUploader\Commands;

use FroshPluginUploader\Components\PluginUpdater;
use FroshPluginUploader\Components\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class UpdatePluginCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('plugin:update')
            ->addArgument('path', InputArgument::REQUIRED, 'Path to /Resources/store folder');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!Util::getEnv('PLUGIN_ID')) {
            throw new \RuntimeException('The enviroment variable $PLUGIN_ID is required');
        }

        $path = realpath($input->getArgument('path'));

        if (!file_exists($path)) {
            throw new \RuntimeException(sprintf('Folder by path %s does not exist', $input->getArgument('path')));
        }

        $this->container->get(PluginUpdater::class)->sync($path);

        $io = new SymfonyStyle($input, $output);
        $io->success('Store folder has been applied to plugin page');
    }
}
