<?php

declare(strict_types=1);

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

$GLOBALS['TL_DCA']['tl_files']['config']['sql']['keys']['bynder_id'] = 'unique';
$GLOBALS['TL_DCA']['tl_files']['fields']['bynder_id']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['bynder_hash']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
