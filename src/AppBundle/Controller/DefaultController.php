<?php

namespace AppBundle\Controller;

use Curl\Curl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class DefaultController
 * @package AppBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..'),
        ]);
    }

    /**
     * @Route("/login", name="login")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function loginAction()
    {
        $clientId = $this->container->getParameter('client_id');
        $url = "https://www.tvshowtime.com/oauth/authorize";
        $redirectUri = $this->generateUrl('token_request', array(), UrlGeneratorInterface::ABSOLUTE_URL);
        $codeRequestUrl = $url."?client_id=".$clientId."&redirect_uri=".$redirectUri;
        return $this->redirect($codeRequestUrl);
    }

    /**
     * @Route("/tokenRequest", name="token_request")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function tokenRequestAction(Request $request)
    {
        $code = $request->get('code', null);

        $curl = new Curl();
        $curl->post('https://api.tvshowtime.com/v1/oauth/access_token', array(
            'client_id' => $this->getParameter('client_id'),
            'client_secret' => $this->getParameter('client_secret'),
            'code' => $code,
            'redirect_uri' => $this->generateUrl('shows', array(), UrlGeneratorInterface::ABSOLUTE_URL)
        ));

        $response = json_decode($curl->response, true);

        if ($response['result'] == "OK") {
            $token = $response['access_token'];
            $session = new Session();
            $session->start();
            $session->set('access_token', $token);
            return $this->redirectToRoute('shows');
        } else {
            return $this->render('default/apiError.html.twig', array('errorMessage' => $response['message']));
        }
    }

    /**
     * @Route("/shows", name="shows")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showsAction(Request $request)
    {
        $session = new Session();
        $token = $session->get('access_token', false);
        if(!$token) {
            return $this->redirectToRoute('login');
        }

        $userUrl = "https://api.tvshowtime.com/v1/user";
        $toWatchUrl = "https://api.tvshowtime.com/v1/to_watch";

        $curl = new Curl();
        $curl->get($toWatchUrl.'?access_token='.$token);
        $response = json_decode($curl->response, true);

        if($response['result'] == 'OK') {
            /*$katPrefix = "https://kat.cr/usearch/";
            foreach ($response['episodes'] as $episode) {
                $search = strtolower($episode['show']['name'])." S".sprintf("%02d", $episode['season_number'])."E".sprintf("%02d", $episode['number']);
                $curl = new Curl();
                dump($katPrefix.str_replace(' ', '%20', $search));
                $curl->get($katPrefix.str_replace(' ', '%20', $search));
                dump($curl->response);
            }
            die();*/

            return $this->render('default/shows.html.twig', array('episodes' => $response['episodes']));
        } else {
            return $this->render('default/apiError.html.twig', array('errorMessage' => $response['message']));
        }

    }
}
