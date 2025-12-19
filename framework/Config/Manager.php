<?php

namespace Marking\BunnyKitty\Config;

use Symfony\Component\HttpFoundation\ParameterBag;
use function Symfony\Component\String\u;

use Yosymfony\Toml\Toml;

final class Manager
{
    private static ?self $instance = null;

    protected ParameterBag $config;

    protected function __construct()
    {
        $this->config = new ParameterBag(
            Toml::parseFile(
                realpath($_ENV["BUNNYKITTY_ROOT_DIR"]) . "/config.toml",
            ),
        );

        // print_r($this->config);
    }

    private function __clone(): void {}

    public function __wakeup(): void
    {
        throw new \RuntimeException("Cannot unserialize singleton");
    }

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function get(string $path): mixed
    {
        $parts = array_map(static fn($s) => (string) $s, u($path)->split("."));

        if (!$this->config->has($parts[0])) {
            throw new \InvalidArgumentException(
                "Config key not found: {$parts[0]}",
            );
        }

        $current = $this->config->get(array_shift($parts));

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                throw new \InvalidArgumentException(
                    "Config key not found: {$path}",
                );
            }

            $current = $current[$part];
        }

        // print_r($current);

        return $current;
    }
}
