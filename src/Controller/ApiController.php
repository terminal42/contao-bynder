<?php

/*
 * Contao Bynder Bundle
 *
 * @copyright  Copyright (c) 2008-2017, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 */

namespace Terminal42\ContaoBynder\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "backend"})
 */
class ApiController extends Controller
{
    /**
     * @param Request $request
     *
     * @return Response
     *
     * @Route("/_bynder_api", name="bynder_api")
     */
    public function apiAction(Request $request)
    {
        $api = $this->get('terminal42.contao_bynder.api');

        /** @var $promise \GuzzleHttp\Promise\PromiseInterface */
        $promise = $api->getAssetBankManager()->getMediaList();

        $promise->then(function() {
            dump(func_get_args());
        });

        dump($request);exit;
    }
}
