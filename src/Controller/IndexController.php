<?php

namespace App\Controller;

use App\Service\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class IndexController extends AbstractController
{
    private $session;

    private $isLoggedIn = false;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;

        $this->isLoggedIn = Auth::isLoggedIn($this->session);
    }

    public function index()
    {

        return $this->render('base.html.twig', [
            'page_title' => 'Telemedi.co',
            'is_logged_in' => $this->isLoggedIn
        ]);

//
//        return new Response(
//            '<html><body>Lucky number: </body></html>'
//        );
    }
}
