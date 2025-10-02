<?php

namespace DigitalPolygon\Polymer\Core\Robo\Services\EventSubscriber;

use DigitalPolygon\Polymer\Core\Robo\ConsoleApplication;
use DigitalPolygon\Polymer\Core\Robo\Event\PolymerEvents;
use DigitalPolygon\Polymer\Core\Robo\Event\PostInvokeCommandEvent;
use Robo\GlobalOptionsEventListener;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\NullOutput;

class SetGlobalOptionsPostInvoke extends GlobalOptionsEventListener
{
    public function __construct(ConsoleApplication $application)
    {
        parent::__construct();
        $this->application = $application;
    }

    public static function getSubscribedEvents()
    {
        return [
            PolymerEvents::POST_INVOKE_COMMAND => 'onPostInvokeCommand',
        ];
    }

    public function onPostInvokeCommand(PostInvokeCommandEvent $event): void
    {
        $input = $event->getParentInput();
        $output = new NullOutput();
        $consoleEvent = new ConsoleCommandEvent(null, $input, $output);
        $this->handleCommandEvent($consoleEvent);
    }
}
