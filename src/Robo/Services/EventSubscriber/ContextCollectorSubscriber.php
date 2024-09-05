<?php

namespace DigitalPolygon\Polymer\Robo\Services\EventSubscriber;

use DigitalPolygon\Polymer\Robo\Event\CollectConfigContextsEvent;
use DigitalPolygon\Polymer\Robo\Event\PolymerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextCollectorSubscriber implements EventSubscriberInterface {

    public function addContexts(CollectConfigContextsEvent $event) {
//        $event->addPlaceholderContext('project');
    }

    public static function getSubscribedEvents()
    {
        $events = [
            PolymerEvents::COLLECT_CONFIG_CONTEXTS => [
                ['addContexts', -100]
            ],
        ];
        return $events;
    }
}
