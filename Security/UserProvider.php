<?php

namespace Eab\LoginByEmailBundle\Security;

use eZ\Publish\Core\MVC\Symfony\Security\User\Provider;
use eZ\Publish\Core\MVC\Symfony\Security\User;
use eZ\Publish\Core\MVC\Symfony\Security\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;

/**
 * This provider is responsible for loading the user from eZ
 * eZ functionality is overridden here to be able to load user additionally via email address
 *
 *
 * Class UserProvider
 */
class UserProvider extends Provider
{

    /**
     * override the eZ functionality to fetch user by username or additionally email address
     * $user can be either the username/email or an instance of \eZ\Publish\Core\MVC\Symfony\Security\User
     *
     * @param string|\eZ\Publish\Core\MVC\Symfony\Security\User $user
     * Either the username/email to load an instance of User object.
     *
     * @return \eZ\Publish\Core\MVC\Symfony\Security\UserInterface
     *
     * @throws \Symfony\Component\Security\Core\Exception\UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($user)
    {
        try {
            // SecurityContext always tries to authenticate anonymous users when checking granted access.
            // In that case $user is an instance of \eZ\Publish\Core\MVC\Symfony\Security\User.
            // We don't need to reload the user here.
            if ( $user instanceof UserInterface ) {
                return $user;
            }
            return new User( $this->repository->getUserService()->loadUserByLogin( $user ), array( 'ROLE_USER' ) );
        } catch ( NotFoundException $e ) {
            try {
                $users = $this->repository->getUserService()->loadUsersByEmail( $user );
                if ( isset( $users[0] ) ) {
                    return new User( $users[0], array( 'ROLE_USER' ) );
                }
            } catch ( NotFoundException $e ) {
                throw new UsernameNotFoundException( $e->getMessage(), 0, $e );
            }
            throw new UsernameNotFoundException( $e->getMessage(), 0, $e );
        }
    }
}
