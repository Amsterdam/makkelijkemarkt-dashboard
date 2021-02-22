<?php

declare(strict_types=1);
namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{

    private $username;

    private $password;

    private $token;

    private $salt;

    private $roles;
    
    public function __construct($username, $password, $token, $salt, array $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;
        $this->salt = $salt;
        $this->roles = $roles;
    }

    /**
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getPassword(): string
    {
        return (string) $this->password;
    }

    /**
     * @see UserInterface
     */
    public function getToken(): string
    {
        return (string) $this->token;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): string
    {
        return (string) $this->salt;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }
}
