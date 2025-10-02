<?php

namespace DigitalPolygon\Polymer\Core\Robo\Template;

final class Token
{
    public function __construct(
        protected string $name,
        protected ?string $defaultValue = null,
        protected bool $isRequired = false,
        protected ?string $value = null,
    ) {
    }

    /**
     * Create token from array.
     *
     * @param array $data
     * @return self
     */
    public static function tokenFromArray(array $data): self
    {
        $token = new self($data['name']);
        if ($data['value']) {
            $token->setValue($data['value']);
        }
        if ($data['defaultValue']) {
            $token->setDefaultValue($data['defaultValue']);
        }
        if ($data['isRequired']) {
            $token->setIsRequired($data['isRequired']);
        }
        return $token;
    }

    /**
     * Create tokens from array data.
     *
     * @param array $data
     * @return self[]
     */
    public static function tokensFromArray(array $data): array
    {
        $tokens = [];
        foreach ($data as $tokenData) {
            $tokens[] = self::tokenFromArray($tokenData);
        }
        return $tokens;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDefaultValue(string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function setIsRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getIsRequired(): bool|null
    {
        return $this->isRequired;
    }

    public function getValue(): string|null
    {
        return $this->value ?? $this->defaultValue;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDefaultValue(): string|null
    {
        return $this->defaultValue;
    }
}
