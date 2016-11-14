<?php

namespace uuf6429\ElderBrother\Action;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use uuf6429\ElderBrother\Change\FileList;

class PhpCsFixer extends ActionAbstract
{
    /**
     * @var FileList
     */
    protected $files;

    /**
     * @var string|null
     */
    protected $binFile;

    /**
     * @var string|null
     */
    protected $configFile;

    /**
     * @var boolean
     */
    protected $addAutomatically;

    /**
     * Runs all the provided files through PHP-CS-Fixer, fixing any code style issues.
     *
     * @param FileList $files The files to check.
     * @param string|null $binFile (Optional, default is from vendor) File path to PHP-CS-Fixer binary.
     * @param string|null $configFile (Optional, default is project root) File path to PHP-CS-Fixer config.
     * @param boolean $addAutomatically (Optional, default is true) Whether to add modified files to commit or not.
     */
    public function __construct(FileList $files, $binFile = null, $configFile = null, $addAutomatically = true)
    {
        $this->files = $files;
        $this->binFile = $binFile ?: $this->getBinFile();
        $this->configFile = $configFile;
        $this->addAutomatically = $addAutomatically;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'PHP Code Style Fixer (PhpCsFixer)';
    }

    /**
     * {@inheritdoc}
     */
    public function checkSupport()
    {
        return file_exists($this->binFile);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $this->files->toArray();

        $progress = $this->createProgressBar($input, $output, count($files));
        $progress->start();

        $failed = [];

        foreach ($files as $file) {
            $progress->setMessage('Processing ' . $file . '...');

            $outp = null;
            $exit = null;
            exec(
                sprintf(
                    'php -f %s fix %s %s',
                    escapeshellarg($this->binFile),
                    escapeshellarg($file),
                    $this->configFile ? ('--config-file=' . escapeshellarg($this->configFile)) : ''
                ),
                $outp,
                $exit
            );

            switch ((int) $exit) {
                case 0:
                    // file has been changed
                    if ($this->addAutomatically) {
                        exec('git add ' . escapeshellarg($file));
                    }
                    break;
                case 1:
                    // file not changed
                    break;
                default:
                    // some sort of error
                    $failed[$file] = $outp;
                    break;
            }

            $progress->advance();
        }

        if (count($failed)) {
            $message = 'PhpCsFixer failed for the following file(s):';
            foreach ($failed as $file => $result) {
                $message .= PHP_EOL . '- ' . $file . ':';
                $message .= PHP_EOL . ' - ' . implode(PHP_EOL . ' - ', $result);
            }
            throw new \RuntimeException($message);
        }

        $progress->finish();
    }

    /**
     * @return string
     */
    protected function getBinFile()
    {
        return PROJECT_ROOT . 'vendor'
            . DIRECTORY_SEPARATOR . 'friendsofphp'
            . DIRECTORY_SEPARATOR . 'php-cs-fixer'
            . DIRECTORY_SEPARATOR . 'php-cs-fixer';
    }
}
