<?php

declare(strict_types=1);

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\EventListener;

use Contao\FilesModel;

class FilesCopyButtonListener
{
    /**
     * Disable copying bynder assets.
     */
    public function __invoke($row, $href, $label, $title, $icon, $attributes): string
    {
        $originalCallback = new \tl_files();
        $original = $originalCallback->copyFile($row, $href, $label, $title, $icon, $attributes);

        $model = FilesModel::findByPath($row['id']);

        if (null === $model) {
            return $original;
        }

        if (null !== $model->bynder_hash) {
            return '';
        }

        return $original;
    }
}
