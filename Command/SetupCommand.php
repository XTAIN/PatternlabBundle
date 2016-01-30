<?php
/**
 * This file is part of the XTAIN Patternlab package.
 *
 * (c) Maximilian Ruta <mr@xtain.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XTAIN\Bundle\PatternlabBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use XTAIN\Composer\Runner\ComposerRunner;

/**
 * Class SetupCommand
 *
 * @author Maximilian Ruta <mr@xtain.net>
 * @package XTAIN\Bundle\PatternlabBundle\Command
 */
class SetupCommand extends ContainerAwareCommand
{
    /**
     * @var string
     */
    protected $rootPath;

    /**
     * SetupCommand constructor.
     *
     * @param string $rootPath
     */
    public function __construct($rootPath)
    {
        $this->rootPath = $rootPath;

        parent::__construct();
    }


    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('xtain:patternlab:install')
            ->setDefinition(
                [
                    new InputArgument(
                        'composer-package',
                        InputArgument::OPTIONAL,
                        'The type of the extension',
                        'pattern-lab/edition-twig-standard'
                    )
                ]
            );
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return void
     * @throws \InvalidArgumentException When the target directory does not exist
     * @throws \InvalidArgumentException When symlink cannot be used
     * @author Maximilian Ruta <mr@xtain.net>
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $package = $input->getArgument('composer-package');
        $filesystem = new Filesystem();
        $composerRunner = new ComposerRunner();
        $cwd = getcwd();

        if ($filesystem->exists($this->rootPath)) {
            chdir($this->rootPath);
            $composerRunner->execute('install');
        } else {
            $composerRunner->execute('create-project', array(
                $package,
                $this->rootPath
            ));
        }

        chdir($cwd);
    }
}