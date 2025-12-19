<?php

namespace Marking\BunnyKitty\Helpers;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\JsonResponse;
use function Symfony\Component\String\u;

use Marking\BunnyKitty\Http\ResponseWrapper;
use Marking\BunnyKitty\Config\Manager as ConfigManager;

function config(string $key = "")
{
    if (!isset($key) || empty($key)) {
        return ConfigManager::getInstance();
    }

    return ConfigManager::getInstance()->get($key);
}

function pathFromRootDir(string ...$paths)
{
    return u(realpath(Path::join($_ENV["BUNNYKITTY_ROOT_DIR"], ...$paths)));
}

function response(?JsonResponse $response = null): ResponseWrapper
{
    return new ResponseWrapper($response);
}
