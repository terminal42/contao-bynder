<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2018, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

$GLOBALS['TL_DCA']['tl_files']['config']['sql']['keys']['bynder_id'] = 'unique';
$GLOBALS['TL_DCA']['tl_files']['fields']['bynder_id']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['bynder_hash']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];

/*
 * Disable copying bynder assets
 */
$GLOBALS['TL_DCA']['tl_files']['list']['operations']['copy']['button_callback'] = function ($row, $href, $label, $title, $icon, $attributes) {
    $originalCallback = new tl_files();
    $original = $originalCallback->copyFile($row, $href, $label, $title, $icon, $attributes);

    $model = \FilesModel::findByPath($row['id']);
    if (null === $model) {
        return $original;
    }

    if (null !== $model->bynder_hash) {
        return '';
    }

    return $original;
};
