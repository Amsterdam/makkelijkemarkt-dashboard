<?php
namespace GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserProvider implements UserProviderInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function loadUserByUsername($username, $password = null)
    {
        $password = $this->container->get('request')->request->get('_password', $password);

        try {
            $data = $this->container->get('markt_api')->login($username, $password);

            return new User($data->account->username, $password, $data->uuid, '', $data->account->roles);
        } catch (\Exception $e) {
            throw new UsernameNotFoundException($e->getMessage());
        }

        throw new UsernameNotFoundException('Unknown user');
    }

    /** (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::refreshUser()
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $user;

        // return $this->loadUserByUsername($user->getUsername(), $user->getPassword());
    }

    /** (non-PHPdoc)
     * @see \Symfony\Component\Security\Core\User\UserProviderInterface::supportsClass()
     */
    public function supportsClass($class)
    {
        return $class === 'GemeenteAmsterdam\MakkelijkeMarkt\DashboardBundle\Security\User';
    }
}