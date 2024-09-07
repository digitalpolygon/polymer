<?php

namespace DigitalPolygon\Polymer\Robo\Services;

use Consolidation\AnnotatedCommand\CommandInfoAltererInterface;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use DigitalPolygon\Polymer\Robo\Config\ConfigAwareTrait;
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
            [$root,] = explode(':', $name, 2);
            if ('internal' === $root) {
                $commandInfo->setHidden(true);
            }
        }
    }
}
