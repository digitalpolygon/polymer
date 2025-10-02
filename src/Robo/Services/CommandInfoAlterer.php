<?php

namespace DigitalPolygon\Polymer\Core\Robo\Services;

use Consolidation\AnnotatedCommand\CommandInfoAltererInterface;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use DigitalPolygon\Polymer\Core\Robo\Config\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

class CommandInfoAlterer implements CommandInfoAltererInterface, ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * @param CommandInfo $commandInfo
     * @param object $commandFileInstance
     * @return void
     */
    public function alterCommandInfo(CommandInfo $commandInfo, $commandFileInstance): void
    {
        if ($this->getConfigValue('hide-internal-commands')) {
            $name = $commandInfo->getName();
            if (is_string($name)) {
                [$root,] = explode(':', $name, 2);
                if ('internal' === $root) {
                    $commandInfo->setHidden(true);
                }
            }
        }
    }
}
