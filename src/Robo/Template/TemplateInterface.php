<?php

namespace DigitalPolygon\Polymer\Robo\Template;

interface TemplateInterface
{
    public const SERVICE_PREFIX = 'plugin.template.';

    /**
     * Unique ID of the template.
     *
     * @return string
     */
    public static function id(): string;

    /**
     * Provide a description for this template.
     *
     * @return string
     */
    public function description(): string;

    /**
     * Get where the template file lives on the filesystem.
     *
     * @return string
     */
    public function source(): string;

    /**
     * Where the file should be copied to.
     *
     * @return string
     */
    public function destination(): string;

    /**
     * Tokens that should be replaced in the template.
     *
     * @return Token[]
     */
    public function tokens(): array;

    /**
     * @return string[]
     */
    public static function collections(): array;
}
