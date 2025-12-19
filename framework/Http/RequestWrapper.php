<?php

namespace Marking\BunnyKitty\Http;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class RequestWrapper
{
    public function __constructor()
    {
        $this->request = SymfonyRequest::createFromGlobals();
    }

    public function headers(string $headerName): ?string
    {
        return $this->request->headers->get($headerName);
    }
}
