<?php

namespace App\Service;

use App\Entity\Users;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Auth
{
    public static function isLoggedIn(SessionInterface$session): bool
    {
        return $session->get('auth') !== null;
    }

    public static function getUser(ObjectManager $entityManager, string $login, string $password): ?Users
    {
        $repository = $entityManager->getRepository(Users::class);

        $user = $repository->findOneBy(['login' => $login, 'password' => sha1($password)]);

        return $user;
    }
}
