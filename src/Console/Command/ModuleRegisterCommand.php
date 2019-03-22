<?php
declare(strict_types=1);

namespace LotGD\Core\Console\Command;

use Composer\Repository\RepositoryInterface;
use LotGD\Core\ModuleManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use LotGD\Core\Exceptions\ClassNotFoundException;
use LotGD\Core\Exceptions\ModuleAlreadyExistsException;
use LotGD\Core\LibraryConfiguration;

/**
 * Danerys command to register and initiate any newly installed modules.
 */
class ModuleRegisterCommand extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('module:register')
             ->setDescription('Register and initialize any newly installed modules');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modules = $this->game->getComposerManager()->getModulePackages();

        foreach ($modules as $p) {
            $this->registerModule($p->getName(), $output);
        }
    }

    protected function registerModule(
        string $packageName,
        OutputInterface $output
    ) {
        $composerRepository = $this->game->getComposerManager()->getComposer()
            ->getRepositoryManager()->getLocalRepository();
        $moduleManager = $this->game->getModuleManager();

        $package = $composerRepository->findPackage($packageName, "*");
        if ($package->getType() !== "lotgd-module") {
            return;
        }

        $library = new LibraryConfiguration($this->game->getComposerManager(), $package, $this->game->getCWD());

        $dependencies = $package->getRequires();
        foreach ($dependencies as $dependency) {
            $this->registerModule($dependency->getTarget(), $output);
        }

        try {
            $this->game->getModuleManager()->register($library);
            $output->writeln("<info>Registered new module {$name}</info>");
        } catch (ModuleAlreadyExistsException $e) {
            $output->writeln("Skipping already registered module {$name}");
        } catch (ClassNotFoundException $e) {
            $output->writeln("<error>Error installing module {$name}: " . $e->getMessage() . "</error>");
        }
    }
}
