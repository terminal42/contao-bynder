<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

Contao\CoreBundle\DataContainer\PaletteManipulator::create()
->addField('bynder_disable', 'fop', Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
->applyToPalette('default', 'tl_user_group');

/*
 * Fields
 */
$GLOBALS['TL_DCA']['tl_user_group']['fields']['bynder_disable'] = [
    'label' => $GLOBALS['TL_LANG']['tl_user_group']['bynder_disable'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'sql' => ['type' => 'boolean'],
];
