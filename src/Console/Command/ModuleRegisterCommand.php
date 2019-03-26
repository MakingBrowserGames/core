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

        $registered = [];
        foreach ($modules as $p) {
            $this->registerModule($p->getName(), $output, $registered);
        }
    }

    /**
     * Register a given package as a module if it is of type lotdg-module. Resolves dependencies and skips already registered packages.
     * @param string $packageName
     * @param OutputInterface $output
     * @param array $registered
     * @throws \LotGD\Core\Exceptions\InvalidConfigurationException
     * @throws \LotGD\Core\Exceptions\WrongTypeException
     */
    protected function registerModule(
        string $packageName,
        OutputInterface $output,
        array &$registered
    ) {
        $composerRepository = $this->game->getComposerManager()->getComposer()
            ->getRepositoryManager()->getLocalRepository();
        $moduleManager = $this->game->getModuleManager();

        $package = $composerRepository->findPackage($packageName, "*");
        if ($package->getType() !== "lotgd-module") {
            return;
        }
        if (!empty($registered[$packageName])) {
            return;
        }

        $output->writeln("Reading module {$packageName} {$package->getPrettyVersion()}");

        $library = new LibraryConfiguration($this->game->getComposerManager(), $package, $this->game->getCWD());

        $dependencies = $package->getRequires();
        foreach ($dependencies as $dependency) {
            $this->registerModule($dependency->getTarget(), $output, $registered);
        }

        try {
            $this->game->getModuleManager()->register($library);
            $output->writeln("\t<info>Registered new module {$packageName}</info>");
        } catch (ModuleAlreadyExistsException $e) {
            $output->writeln("\tSkipping already registered module {$packageName}");
        } catch (ClassNotFoundException $e) {
            $output->writeln("\t<error>Error installing module {$packageName}: " . $e->getMessage() . "</error>");
        }

        $registered[$packageName] = true;
    }
}
