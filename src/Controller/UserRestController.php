<?php

namespace App\Controller;

use App\Entity\Users;
use App\Service\Auth;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\User\User;

class UserRestController extends AbstractController
{
    private $session;

    private $isLoggedIn = false;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;

        $this->isLoggedIn = Auth::isLoggedIn($this->session);
    }

    public function auth(Request $request)
    {
        $data = $this->getJSONRequest($request);

        if (!isset($data['login']) || !isset($data['password']) || $data['login'] == "" || $data['password'] == "") {
            return new JsonResponse(json_encode([
                'is_error' => true,
                'message' => 'You need to provide login and password'
            ]));
        }

        $entityManager = $this->getDoctrine()->getManager();

        $user = Auth::getUser($entityManager, $data['login'], $data['password']);

        if ($user === null) {
            return new JsonResponse(json_encode([
                'is_error' => true,
                'message' => 'You are not authorized'
            ]));
        }

        $this->session->set('auth', ['userId' => $user->getUserID()]);
        return new JsonResponse(json_encode([
            'is_error' => false,
            'message' => 'You are authorized'
        ]));
    }

    public function createUser(Request $request)
    {
        if ($this->isLoggedIn == false) {
            return new JsonResponse([
                'is_error' => true,
                'message' => 'You need to authorize to use API'
            ]);
        }

        $data = $this->getJSONRequest($request);

        if (!isset($data['login']) || !isset($data['password']) || $data['login'] == '' || $data['password'] == "") {
            return new JsonResponse(json_encode([
                'is_error' => true,
                'message' => 'You need to provide valid login and password'
            ]));
        }

        $user = new Users();
        $user->setLogin($data['login']);
        $user->setPassword($data['password']);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(json_encode([
            'is_error' => false,
            'message' => 'User created',
            'user_id' => $user->getUserId(),
        ]));
    }

    public function readUser(Request $request, int $id)
    {
        if ($this->isLoggedIn == false) {
            return new JsonResponse([
                'is_error' => true,
                'message' => 'You need to authorize to use API'
            ]);
        }

        $data = $this->getJSONRequest($request);

        $entityManager = $this->getDoctrine()->getManager();

        $repository = $entityManager->getRepository(Users::class);

        $user = $repository->findOneBy(['userId' => $id]);

        if ($user === null) {
            return new JsonResponse([
                'is_error' => true,
                'message' => 'No such user',
            ]);
        }

        return new JsonResponse([
            'is_error' => false,
            'message' => '',
            'user' => [
                'login' => $user->getLogin()
            ]
        ]);
    }

    public function updateUser(Request $request, int $id)
    {
        if ($this->isLoggedIn == false) {
            return new JsonResponse([
                'is_error' => true,
                'message' => 'You need to authorize to use API'
            ]);
        }

        $data = $this->getJSONRequest($request);

        $entityManager = $this->getDoctrine()->getManager();

        $repository = $entityManager->getRepository(Users::class);

        $user = $repository->findOneBy(['userId' => $id]);

        if ($user === null) {
            return new JsonResponse([
                'is_error' => true,
                'message' => 'No such user',
            ]);
        }

        $user->setLogin($data['login']);
        $user->setPassword(sha1($data['password']));
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'is_error' => false,
            'message' => 'User updated',
        ]);
    }

    public function deleteUser(Request $request, int $id)
    {
        if ($this->isLoggedIn == false) {
            return new JsonResponse([
                'is_error' => true,
                'message' => 'You need to authorize to use API'
            ]);
        }

        $data = $this->getJSONRequest($request);

        $entityManager = $this->getDoctrine()->getManager();
        $user = $entityManager->getPartialReference(Users::class, array('userId' => $id));
        $entityManager->remove($user);
        $entityManager->flush();

        return new JsonResponse([
            'is_error' => false,
            'message' => 'User deleted',
        ]);
    }

    protected function getJSONRequest(Request $request): array
    {
        if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
            return json_decode($request->getContent(), true);
        }

        return [];
    }
}
