<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_files']['config']['sql']['keys']['bynder_id'] = 'unique';
$GLOBALS['TL_DCA']['tl_files']['fields']['bynder_id']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
$GLOBALS['TL_DCA']['tl_files']['fields']['bynder_hash']['sql'] = ['type' => 'string', 'length' => 64, 'notnull' => false];
