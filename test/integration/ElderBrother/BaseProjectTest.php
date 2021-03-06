<?php

namespace uuf6429\ElderBrother;

use Symfony\Component\Process\Process;

abstract class BaseProjectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string Temporary directory for project under test
     */
    protected static $projectPath;

    /**
     * @var string File that ensure other tests won't influence this one
     */
    private static $projectLockFile;

    /**
     * @var string
     */
    private static $oldWorkingDirectory;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$projectLockFile = tempnam(sys_get_temp_dir(), 'test');

        self::assertNotEmpty(self::$projectLockFile);

        self::$projectPath = self::$projectLockFile . '_dir';
        mkdir(self::$projectPath);

        self::$oldWorkingDirectory = getcwd();
        chdir(self::$projectPath);
    }

    public static function tearDownAfterClass()
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                self::$projectPath,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getRealPath());
            } else {
                @unlink($file->getRealPath());
            }
        }

        chdir(self::$oldWorkingDirectory);

        if (@rmdir(self::$projectPath)) {
            @unlink(self::$projectLockFile);
        }

        parent::tearDownAfterClass();
    }

    /**
     * Runs a command and compares result against expection.
     *
     * @param string        $command        The command to execute
     * @param int           $expectedResult Expected exit code
     * @param string[]|null $expectedOutput Expected stdout as array of lines (null to skip check)
     * @param string        $message        Description of the assertion
     */
    protected static function assertCommand($command, $expectedResult, $expectedOutput = null, $message = '')
    {
        $process = new Process($command);
        $process->run();

        $actualResult = $process->getExitCode();
        $actualOutput = explode("\n", str_replace(PHP_EOL, "\n", $process->getOutput() ?: $process->getErrorOutput()));

        if (!$message) {
            $message = sprintf(
                "Command:\n$ %s\nResult (exit: %d):\n> %s",
                $command,
                $process->getExitCode(),
                implode("\n> ", $actualOutput)
            );
        }

        static::assertEquals($expectedResult, $actualResult, $message);

        if (!is_null($expectedOutput)) {
            static::assertEquals($expectedOutput, $actualOutput, $message);
        }
    }

    /**
     * Runs a command and asserts that it was successful.
     *
     * @param string        $command        The command to execute
     * @param string[]|null $expectedOutput Expected stdout as array of lines (null to skip check)
     * @param string        $message        Description of the assertion
     */
    protected static function assertCommandSuccessful($command, $expectedOutput = null, $message = '')
    {
        static::assertCommand($command, 0, $expectedOutput, $message);
    }

    /**
     * Returns command line to run ElderBrother.
     *
     * @return string
     */
    protected static function getEbCmd()
    {
        static $cache = null;

        if (!$cache) {
            $bin = __DIR__ . '/../../../elder-brother';
            $cache = 'php -f ' . escapeshellarg(realpath($bin)) . ' -- ';
        }

        return $cache;
    }
}
