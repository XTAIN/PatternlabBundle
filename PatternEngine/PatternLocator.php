<?php
/**
 * @author Maximilian Ruta <mr@xtain.net>
 */

namespace XTAIN\Bundle\PatternlabBundle\PatternEngine;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Class PatternLocator
 *
 * @author Maximilian Ruta <mr@xtain.net>
 * @package XTAIN\Bundle\PatterlabBundle\PatternEngine
 */
class PatternLocator
{
    /**
     * @var string
     */
    protected $patternlabRoot;

    /**
     * @var string
     */
    protected $extension = '.twig';

    /**
     * PatternPath constructor.
     *
     * @param string $patternlabRoot
     */
    public function __construct($patternlabRoot)
    {
        $this->patternlabRoot = $patternlabRoot;
    }

    public function getSourcePath()
    {
        return $this->patternlabRoot . DIRECTORY_SEPARATOR . 'source';
    }

    public function getPatternsPath()
    {
        return $this->getSourcePath() . DIRECTORY_SEPARATOR . '_patterns';
    }

    public function getPatternPaths()
    {
        $patterns = [];

        $base = realpath($this->getPatternsPath());

        if (!file_exists($base)) {
            return array();
        }

        $finder = new Finder();
        $finder->files()->in($base);

        /** @var \SplFileInfo $item */
        foreach ($finder as $item) {
            $part = substr($item->getRealPath(), strlen($base) + strlen(DIRECTORY_SEPARATOR));
            $part = preg_replace('/' . preg_quote($this->extension, '/') . '$/', '', $part);

            $parts = explode(DIRECTORY_SEPARATOR, $part);

            $parts = array_map(function($item) {
                return preg_replace('/^[0-9]+\-/', '', $item);
            }, $parts);

            $file = array_pop($parts);

            if (count($parts) > 1) {
                unset($parts[1]);
            }

            $currentPatterns =& $patterns;

            foreach ($parts as $cpart) {
                if (!isset($currentPatterns[$cpart])) {
                    $currentPatterns[$cpart] = [];
                }

                $currentPatterns =& $currentPatterns[$cpart];
            }

            $currentPatterns[$file] = $part;
        }

        return $patterns;
    }

}