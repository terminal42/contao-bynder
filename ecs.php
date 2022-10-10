<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $ecsConfig->sets([__DIR__.'/vendor/contao/easy-coding-standard/config/contao.php']);

    $ecsConfig->ruleWithConfiguration(HeaderCommentFixer::class, [
        'header' => "Contao Bynder Bundle\n\n@copyright  Copyright (c) 2008-2021, terminal42 gmbh\n@author     terminal42 gmbh <info@terminal42.ch>"
    ]);
};
