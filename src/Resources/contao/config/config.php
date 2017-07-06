<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

if (TL_MODE === 'BE') {
    $GLOBALS['TL_CSS'][] = 'bundles/terminal42contaobynder/styles.min.css|static';
    $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/terminal42contaobynder/app.min.js|static';
}

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePreActions'][] = ['terminal42.contao_bynder.event_listener.import_images', 'onExecutePreActions'];
