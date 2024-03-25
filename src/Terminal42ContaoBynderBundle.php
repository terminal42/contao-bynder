<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42ContaoBynderBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
