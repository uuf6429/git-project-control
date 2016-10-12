<?php

namespace uuf6429\GitProjectControl\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use uuf6429\GitProjectControl\Action\ActionInterface;

class Run extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('run')
            ->setDescription('Runs configured actions.')
            ->setHelp('This command runs all actions defined in configuration.')
        ;
        // TODO add argument to specify which even to trigger
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $actions = $this->getConfigActions($output);

        if (!empty($actions)) {
            // See https://github.com/symfony/symfony/pull/10356 for multiple bars
            $progress = new ProgressBar($output, count($actions));
            $progress->start();
            $output->write("\n");

            foreach ($actions as $action) {
                $output->write("\033[1A");
                $progress->setMessage('Running "' . $action->getName() . '".');
                $progress->advance();
                $output->write("\n");

                $action->execute($input, $output);
            }

            $progress->finish();
            $output->writeln(['', 'FINISHED']);
        } else {
            $output->writeln('<info>No actions have been set up yet!</info>');
        }

        exit(0);
    }

    /**
     * @return ActionInterface[]
     */
    protected function getConfigActions(OutputInterface $output)
    {
        $config = [];
        $configPaths = [
            'project config' => 'path1', // TODO fix path
            'user config' => 'path1', // TODO fix path
        ];

        foreach ($configPaths as $configType => $configPath) {
            if (file_exists($configPath)) {
                if ($output->isVerbose()) {
                    $output->writeln("<info>Reading $configType from $configPath...</info>");
                }

                $config = array_merge($config, include($configPath)); // TODO to be fixed in #1 or #3
            } elseif ($output->isVerbose()) {
                $output->writeln("<info>Reading $configType from $configPath skipped (no file).</info>");
            }
        }

        return $config;
    }
}