<?php

namespace AppBundle\Tool;

use Curl\Curl;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ShowHelper
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getToken()
    {
        return $this->container->get('session')->get('access_token', false);
    }

    public function isLoggedIn()
    {
        return $this->getToken();
    }

    public function getCodeRequestUrl()
    {
        $url = "https://www.tvshowtime.com/oauth/authorize";
        $clientId = $this->container->getParameter('client_id');
        $redirectUri = $this->container->get('router')->generate('token_request', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $codeRequestUrl = $url."?client_id=".$clientId."&redirect_uri=".$redirectUri;
        return $codeRequestUrl;
    }

    public function requestToken($code)
    {
        $curl = new Curl();
        $curl->post('https://api.tvshowtime.com/v1/oauth/access_token', array(
            'client_id' => $this->container->getParameter('client_id'),
            'client_secret' => $this->container->getParameter('client_secret'),
            'code' => $code,
            'redirect_uri' => $this->container->get('router')->generate('list', array(), UrlGeneratorInterface::ABSOLUTE_URL)
        ));

        return json_decode($curl->response, true);
    }

    public function getToWatchList()
    {
        $token = $this->getToken();
        $toWatchUrl = "https://api.tvshowtime.com/v1/to_watch";

        $curl = new Curl();
        $curl->get($toWatchUrl.'?access_token='.$token);
        $response = json_decode($curl->response, true);

        return $response;
    }

    public function katSearch($keywords)
    {
        $katPrefix = "https://kat.cr/json.php?q=";
        $curl = new Curl();
        $curl->get($katPrefix.urlencode($keywords));
        $apiReturn = json_decode($curl->response, true);
        return $apiReturn;
    }

    public function formatEpisodeName($showName, $season, $episode)
    {
        $lowercase = strtolower($showName);
        $clean = trim(str_replace(array('.', "'"), '', $lowercase));
        $formatted = $clean." S".sprintf("%02d", $season)."E".sprintf("%02d", $episode);

        return $formatted;
    }

    public function orderListBy(&$list, $criteria)
    {
        $sort = array();
        foreach($list as $key => $value) {
            $sort[$key] = $value[$criteria];
        }
        array_multisort($sort, SORT_ASC, $list);
    }

}
