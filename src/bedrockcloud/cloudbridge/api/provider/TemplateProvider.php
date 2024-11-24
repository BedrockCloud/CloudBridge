<?php

namespace bedrockcloud\cloudbridge\api\provider;

use Closure;
use bedrockcloud\cloudbridge\api\object\template\Template;
use bedrockcloud\cloudbridge\api\registry\Registry;
use bedrockcloud\cloudbridge\util\GeneralSettings;
use RuntimeException;

class TemplateProvider {

    public function current(): Template {
        return $this->getTemplate(GeneralSettings::getTemplateName()) ?? throw new RuntimeException("Current template shouldn't be null");
    }

    public function pickTemplates(Closure $filterClosure): array {
        return array_filter($this->getTemplates(), $filterClosure);
    }

    public function getTemplate(string $name): ?Template {
        return $this->getTemplates()[$name] ?? null;
    }

    /** @return array<Template> */
    public function getTemplates(): array {
        return Registry::getTemplates();
    }
}