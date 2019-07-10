<?php

namespace App\Event;

use League\CommonMark\ConfigurableEnvironmentInterface;
use League\CommonMark\Extension\Extension;
use Symfony\Contracts\EventDispatcher\Event;

final class MarkdownInitEvent extends Event {
    /**
     * @var ConfigurableEnvironmentInterface
     */
    private $environment;

    /**
     * @var string[]
     */
    private $context;

    private $commonMarkConfig = [];

    private $htmlPurifierConfig = [];

    public function __construct(ConfigurableEnvironmentInterface $environment, array $context) {
        $this->environment = $environment;
        $this->context = $context;
    }

    public function getEnvironment(): ConfigurableEnvironmentInterface {
        return $this->environment;
    }

    /**
     * @return string[]
     */
    public function getContext(): array {
        return $this->context;
    }

    public function getHtmlPurifierConfig(): array {
        return $this->htmlPurifierConfig;
    }

    public function addHtmlPurifierConfig(array $config): void {
        $this->htmlPurifierConfig = array_replace($this->htmlPurifierConfig, $config);
    }
}
