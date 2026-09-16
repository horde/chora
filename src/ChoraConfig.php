<?php

declare(strict_types=1);
/**
 * Chora configuration class
 *
 * Provides access to the Chora configuration settings.
 *
 * Old pattern: globals $conf; $somethingDetail = $conf['something']['detail']; *
 * New pattern: $config = $injector->get(ChoraConfig::class); $somethingDetail = $config->get('something.detail');
 *
 * Prefer DI over instantiating $config in your code.
 */

namespace Horde\Chora;

use Horde\Core\Config\State;
use Horde\Injector\Attribute\Factory;

#[Factory(factory: ChoraConfigFactory::class, method: 'create')]
class ChoraConfig extends State {}
