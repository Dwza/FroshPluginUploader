<?php declare(strict_types=1);

namespace FroshPluginUploader;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Application extends \Symfony\Component\Console\Application
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct()
    {
        parent::__construct('FroshPluginUploader', '__VERSION__');
        $this->container = DependencyInjection::getContainer();

        foreach (array_keys($this->container->findTaggedServiceIds('console.command')) as $command) {
            /** @var Command $command */
            $command = $this->container->get($command);

            if ($command instanceof ContainerAwareInterface) {
                $command->setContainer($this->container);
            }

            $this->add($command);
        }
    }

    public function getContainer(): ContainerBuilder
    {
        return $this->container;
    }
}
