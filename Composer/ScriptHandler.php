<?php
/**
 * This file is part of the XTAIN Patternlab package.
 *
 * (c) Maximilian Ruta <mr@xtain.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XTAIN\Bundle\PatternlabBundle\Composer;

use Composer\Script\CommandEvent;
use XTAIN\Composer\Symfony\Util\Console;
use XTAIN\Composer\Symfony\Util\Kernel;

/**
 * Class ScriptHandler
 *
 * @author Maximilian Ruta <mr@xtain.net>
 * @package XTAIN\Bundle\JoomlaBundle\Composer
 */
class ScriptHandler
{
    /**
     * @param CommandEvent $event
     * @return void
     * @author Maximilian Ruta <mr@xtain.net>
     */
    public static function install(CommandEvent $event)
    {
        $console = new Console($event);
        $console->execute('xtain:patternlab:install');
    }
}