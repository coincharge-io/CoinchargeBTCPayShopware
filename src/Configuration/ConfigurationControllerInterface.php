<?php

declare(strict_types=1);

namespace Coincharge\Shopware\Configuration;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\Context;

interface ConfigurationControllerInterface
{
  public function verifyApiKey(Request $request, Context $context): JsonResponse;
}
