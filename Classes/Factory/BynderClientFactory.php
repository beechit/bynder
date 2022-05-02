<?php

namespace BeechIt\Bynder\Factory;

use BeechIt\Bynder\Utility\ConfigurationUtility;
use Bynder\Api\BynderClient;
use Bynder\Api\Impl\PermanentTokens\Configuration;

class BynderClientFactory
{
    public function __invoke(): BynderClient
    {
        return new BynderClient(new Configuration(
            ConfigurationUtility::getDomain(),
            ConfigurationUtility::getPermanentToken(),
            ConfigurationUtility::getHTTPRequestOptions()
        ));
    }
}
