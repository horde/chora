<?php

declare(strict_types=1);
/**
 * Chora configuration class factory
 *
 * Creates instances of the ChoraConfig class.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(ChoraConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Chora;

use Horde\Core\Config\ConfigLoader;
use Horde\Injector\Injector;

class ChoraConfigFactory
{
    public function __construct(private Injector $injector) {}

    public function create(): ChoraConfig
    {
        $state = $this->injector->get(ConfigLoader::class)->load('chora');
        return new ChoraConfig($state->toArray());
    }
}
