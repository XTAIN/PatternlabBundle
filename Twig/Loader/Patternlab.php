<?php

namespace XTAIN\Bundle\PatternlabBundle\Twig\Loader;

use Symfony\Component\Templating\TemplateReferenceInterface;
use XTAIN\Bundle\PatternlabBundle\PatternEngine\PatternLocator;
use XTAIN\Bundle\PatternlabBundle\PatternEngine\Util;

/**
 * @author Maximilian Ruta <mr@xtain.net>
 */
class Patternlab extends \Twig_Loader_Filesystem
{
    /**
     * @var PatternLocator
     */
    protected $locator;

    /**
     * @var Util
     */
    protected $util;

    /**
     * @var string
     */
    protected $extension    = '.twig';

    /**
     * Constructor.
     *
     * @param PatternLocator $locator A FileLocatorInterface instance
     * @param Util           $util    The PatternLab Util
     */
    public function __construct(PatternLocator $locator, Util $util)
    {
        parent::__construct(array());

        $this->locator = $locator;
        $this->util = $util;
    }

    /**
     * {@inheritdoc}
     *
     * The name parameter might also be a TemplateReferenceInterface.
     */
    public function exists($name)
    {
        return parent::exists((string) $name);
    }

    protected function normalizeName($name) {
        return preg_replace('#/{2,}#', '/', strtr((string) $name, '\\', '/'));
    }

    protected function validateName($name) {

        if (false !== strpos($name, "\0")) {
            throw new \Twig_Error_Loader('A template name cannot contain NUL bytes.');
        }

        $name = ltrim($name, '/');
        $parts = explode('/', $name);
        $level = 0;
        foreach ($parts as $part) {
            if ('..' === $part) {
                --$level;
            } elseif ('.' !== $part) {
                ++$level;
            }

            if ($level < 0) {
                throw new \Twig_Error_Loader(sprintf('Looks like you try to load a template outside configured directories (%s).', $name));
            }
        }

    }

    /**
     * Returns the path to the template file.
     *
     * The file locator is used to locate the template when the naming convention
     * is the symfony one (i.e. the name can be parsed).
     * Otherwise the template is located using the locator from the twig library.
     *
     * @param string|TemplateReferenceInterface $template The template
     *
     * @return string The path to the template file
     *
     * @throws \Twig_Error_Loader if the template could not be found
     */
    protected function findTemplate($template)
    {
        $logicalName = (string) $template;

        list($partialName, $styleModifier, $parameters) = $this->util->getPartialInfo($template);

        $name = $this->util->getFileName($partialName, $this->extension);

        $name = $this->normalizeName($name);

        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $this->validateName($name);

        $file = false;

        if ($name !== $this->extension) {
            $file = $this->locator->getPatternsPath() . DIRECTORY_SEPARATOR . $name;
        }

        if (false === $file || null === $file) {
            throw new \Twig_Error_Loader(sprintf('Unable to find template "%s".', $logicalName));
        }

        return $this->cache[$name] = $file;
    }
}