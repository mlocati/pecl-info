<?php

declare(strict_types=1);

namespace PeclInfo\Pecl\Package\Version;

use JsonSerializable;
use RuntimeException;

class ConfigureOption implements JsonSerializable
{
    private string $name;

    private string $prompt;

    private ?string $default;

    public function __construct(string $name, string $prompt, ?string $default = null)
    {
        $this->name = $name;
        $this->prompt = $prompt;
        $this->default = $default;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrompt(): string
    {
        return $this->prompt;
    }

    public function getDefault(): ?string
    {
        return $this->default;
    }

    /**
     * {@inheritdoc}
     *
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize(): array
    {
        $result = [
            'n' => $this->getName(),
            'p' => $this->getPrompt(),
        ];
        $default = $this->getDefault();
        if ($default !== null) {
            $result['d'] = $default;
        }

        return $result;
    }

    /**
     * @throws \RuntimeException
     *
     * @return static
     */
    public static function fromJSON(array $value): self
    {
        $name = $value['n'] ?? null;
        if (!is_string($name) || $name === '') {
            throw new RuntimeException('Missing/invalid name key for ' . __CLASS__);
        }
        $prompt = $value['p'] ?? null;
        if (!is_string($prompt) || $prompt === '') {
            throw new RuntimeException('Missing/invalid prompt key for ' . __CLASS__);
        }
        $default = $value['d'] ?? null;
        if ($default !== null && !is_string($default)) {
            throw new RuntimeException('Missing/invalid default key for ' . __CLASS__);
        }

        return new static($name, $prompt, $default);
    }
}
