<?php

namespace AppBundle\Controller;

use Curl\Curl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DefaultController
 * @package AppBundle\Controller
 */
class DefaultController extends BaseController
{
    /**
     * @Route("/", name="homepage")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        if($this->get('show_helper')->isLoggedIn()) {
            return $this->redirectToRoute('shows');
        }

        $codeRequestUrl = $this->get('show_helper')->getCodeRequestUrl();

        return $this->redirect($codeRequestUrl);
    }

    /**
     * @Route("/logout", name="logout")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function logOutAction()
    {
        $session = $this->get('session');
        if($session->getId()) {
            $session->clear();
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/tokenRequest", name="token_request")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tokenRequestAction(Request $request)
    {
        if(!$code = $request->get('code', null)) {
            return $this->redirectToRoute('homepage');
        }

        $response = $this->get('show_helper')->requestToken($code);

        if ($response['result'] == "OK") {
            $token = $response['access_token'];
            $session = $this->get('session');
            if(!$session->getId()) {
                $session->start();
            }
            $session->set('access_token', $token);
            return $this->redirectToRoute('shows');
        } else {
            return $this->render('AppBundle::apiError.html.twig', array('errorMessage' => $response['message']));
        }
    }

    /**
     * @Route("/shows", name="shows")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showsAction(Request $request)
    {
        if(!$this->get('show_helper')->isLoggedIn()) {
            return $this->redirectToRoute('login');
        }

        $toWatchList = $this->get('show_helper')->getToWatchList();

        if($toWatchList['result'] == 'OK') {
            $apiReturn = array();
            $keywords = array();
            foreach ($toWatchList['episodes'] as $episode) {
                $search = $this->get('show_helper')->formatEpisodeName(
                    $episode['show']['name'],
                    $episode['season_number'],
                    $episode['number']
                );
                $apiReturnTemp = $this->get('show_helper')->katSearch($search);
                $keywords[] = $search;
                $apiReturnTemp['list'] = array_slice($apiReturnTemp['list'], 0, 5);
                $apiReturn[] = $apiReturnTemp;
            }

            return $this->render('AppBundle::shows.html.twig', array(
                'episodes' => $toWatchList['episodes'],
                'apiReturn' => $apiReturn,
                'keywords' => $keywords
            ));
        } else {
            return $this->render('AppBundle::apiError.html.twig', array('errorMessage' => $toWatchList['message']));
        }

    }
}
