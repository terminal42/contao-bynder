<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addField('bynder_disable', 'fop', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_user_group')
;

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user_group']['fields']['bynder_disable'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user_group']['bynder_disable'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'sql' => ['type' => 'boolean', 'default' => 0],
];
