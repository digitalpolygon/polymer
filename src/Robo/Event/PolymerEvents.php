<?php

namespace DigitalPolygon\Polymer\Robo\Event;

class PolymerEvents
{
    const EXTENSION_CONFIG_PRIORITY_OVERRIDE = 'polymer.extension_config_priority_override';
    const COLLECT_CONFIG_CONTEXTS = 'polymer.collect_config_contexts';
    const ALTER_CONFIG_CONTEXTS = 'polymer.alter_config_contexts';
    const PRE_INVOKE_COMMAND = 'polymer.pre_invoke_command';
    const POST_INVOKE_COMMAND = 'polymer.post_invoke_command';
}
