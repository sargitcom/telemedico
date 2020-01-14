<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthController extends AbstractController
{
    private $session;

    private $isLoggedIn = false;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;

        $this->isLoggedIn = Auth::isLoggedIn($this->session);
    }

    public function login(Request $request)
    {
        if ($this->isLoggedIn) {
            return $this->redirect('/');
        }

        if ($request->isMethod('post')) {
            $entityManager = $this->getDoctrine()->getManager();

            $user = Auth::getUser($entityManager, $request->get('login'), $request->get('password'));

            if ($user) {
                $this->session->set('auth', ['userId' => $user->getUserID()]);
                return $this->redirect('/');
            }

            $this->session->getFlashBag()->add('error', 'Invalid user login or password!');
            return $this->redirect('/login');
        }

        $errors = $this->session->getFlashBag()->get('error', []);
        $notices = $this->session->getFlashBag()->get('notice', []);

        return $this->render('auth/login.html.twig', [
            'page_title' => 'Log in page',
            'errors' => $errors,
            'notices' => $notices,
        ]);
    }

    public function logout(Request $request)
    {
        $this->session->clear();
        return $this->redirect('/');
    }

    public function register(Request $request)
    {
        if ($this->isLoggedIn) {
            return $this->redirect('/');
        }

        if ($request->isMethod('post')) {
            $entityManager = $this->getDoctrine()->getManager();

            $repository = $entityManager->getRepository(Users::class);

            $login = $request->get('login');
            $password = $request->get('password');

            if ($login == "" || $password == "") { // tutaj warto by użyć wlidatora; jako, że nie wiedziałem czy ten bundle wolno mi użyć zrobiłem walidację na "piechotę"
                $this->session->getFlashBag()->add('error', 'User login and password can`t be empty');
                return $this->redirect('/register');
            }

            $user = $repository->findOneBy(['login' => $login]);

            if ($user) {
                $this->session->getFlashBag()->add('error', 'User already exists!');
                return $this->redirect('/register');
            }

            $user = new Users();
            $user->setLogin($login);
            $user->setPassword(sha1($password)); // tutaj trzeba dodać sól, samo sha1 to za mało by hasło było bezpieczne

            $entityManager->persist($user);
            $entityManager->flush();

            $this->session->getFlashBag()->add('notice', 'User created!');
            return $this->redirect('/login');
        }

        $errors = $this->session->getFlashBag()->get('error', []);

        return $this->render('auth/register.html.twig', [
            'page_title' => 'Sign up page',
            'errors' => $errors,
        ]);
    }
}
