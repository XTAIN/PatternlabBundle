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
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use XTAIN\Composer\Runner\ComposerRunner;

/**
 * Class RunCommand
 *
 * @author Maximilian Ruta <mr@xtain.net>
 * @package XTAIN\Bundle\PatternlabBundle\Command
 */
class RunCommand extends ContainerAwareCommand
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
            ->setName('xtain:patternlab:run')
            ->setDefinition(
                [
                    new InputArgument(
                        'mode',
                        InputArgument::OPTIONAL,
                        'Generate using watch or polling',
                        'polling'
                    ),
                    new InputOption(
                        'interval',
                        'i',
                        InputOption::VALUE_OPTIONAL,
                        'Generate interval used for polling mode',
                        500
                    )
                ]
            );
    }

    /**
     * @param string $prefix
     *
     * @return \Closure
     * @author Maximilian Ruta <mr@xtain.net>
     */
    protected function buildPassthru($prefix)
    {
        return function ($type, $buffer) use($prefix) {
            if (Process::ERR === $type) {
                echo $prefix . ' > '.$buffer;
            } else {
                echo $prefix . ' > '.$buffer;
            }
        };
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
        $mode = $input->getArgument('mode');
        $interval = (int) $input->getOption('interval');
        $modeWatch = $mode == "watch";
        $modePolling = $mode == "polling";

        if (!$modePolling && !$modeWatch) {
            throw new \InvalidArgumentException(sprintf(
                "mode needs to be watch or polling"
            ));
        }

        $filesystem = new Filesystem();

        if (!$filesystem->exists($this->rootPath)) {
            throw new \RuntimeException(sprintf(
                "patternlab not present, did you forgot to run xtain:patternlab:install first?"
            ));
        }

        chdir($this->rootPath);
        $phpFinder = new PhpExecutableFinder();
        $php = $phpFinder->find();

        $processBuilder = new ProcessBuilder();
        $processBuilder->setPrefix($php);
        $processBuilder->setTimeout(0);

        $processBuilder->setArguments(array(
            'core/console',
            '--generate'
        ));
        $processGenerate = $processBuilder->getProcess();

        $processBuilder->setArguments(array(
            'core/console',
            '--watch'
        ));
        $processWatch = $processBuilder->getProcess();

        if ($modeWatch) {
            $processWatch->start($this->buildPassthru('watch'));
        }

        $processBuilder->setArguments(array(
            'core/console',
            '--server'
        ));

        $processServer = $processBuilder->getProcess();
        $processServer->start($this->buildPassthru('server'));

        $forceRegenerate = 0;

        $processes = array($processServer);
        if ($modeWatch) {
            $processes[] = $processWatch;
        }

        /** @var Process $process */
        do {
            foreach ($processes as $process) {
                $process->getStatus();
            }
            usleep(500);

            if ($modePolling) {
                if (microtime(true) - $forceRegenerate > ($interval / 1000)) {
                    $processGenerate->start();
                    $processGenerate->wait($this->buildPassthru('watch'));
                    $forceRegenerate = microtime(true);
                }
            }
            // as soon as the server is not running anymore we dont need the watcher anymore
        } while ($processServer->isRunning());
    }
}