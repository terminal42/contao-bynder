<?php

declare(strict_types=1);

namespace Terminal42\ContaoBynder\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\FilesModel;

#[AsCallback('tl_files', 'list.operations.copy.button')]
class FilesCopyButtonListener
{
    /**
     * Disable copying bynder assets.
     */
    public function __invoke(array $row, $href, $label, $title, $icon, $attributes): string
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
