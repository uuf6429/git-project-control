<?php

namespace uuf6429\ElderBrother\Change;

use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;

class FullChangeSet
{
    /**
     * @param string|null Starting directory (current working dir is used if null)
     *
     * @return FileList
     */
    public static function get($dir = null)
    {
        $dir = is_null($dir) ? getcwd() : $dir;

        return new FileList(
            __METHOD__ . '()',
            function () use ($dir) {
                return new \RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $dir,
                        RecursiveDirectoryIterator::SKIP_DOTS
                    ),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
            }
        );
    }
}
