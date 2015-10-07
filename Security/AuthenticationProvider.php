<?php

namespace Eab\LoginByEmailBundle\Security;

use eZ\Publish\Core\MVC\Symfony\Security\Authentication\RepositoryAuthenticationProvider;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\Core\MVC\Symfony\Security\User as EzUser;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * This provider is responsible for user authentication
 * eZ functionality is overridden here to be able to load the user additionally
 * via email address, or later load the user from different tree
 * Additionally the session id of anonymous user is stored in the session here
 *
 * Class AuthenticationProvider
 */
class AuthenticationProvider extends RepositoryAuthenticationProvider
{
    const SES_ANONYMOUS_SESSION_ID = 'sesAnonymousSessionId';

    /**
     * @var \eZ\Publish\API\Repository\Repository $repository
     */
    protected $repository;

    /**
     * @var Session $session
     */
    protected $session;

    /**
     * set the dependency to the repository
     *
     * @param Repository $repository
     */
    public function setRepository( Repository $repository )
    {
        $this->repository = $repository;
    }

    /**
     * set the dependency to the session
     *
     * @param Session $session
     */
    public function setSession( Session $session )
    {
        $this->session = $session;
    }

    /**
     * override the eZ functionality to fetch user additionally by email address
     * if the user was authenticated successfully the session id of anonymous user
     * is stored in the session for later purposes
     *
     * @param UserInterface $user
     * @param UsernamePasswordToken $token
     * @return bool|void
     * @throws \Symfony\Component\Security\Core\Exception\BadCredentialsException
     */
    protected function checkAuthentication( UserInterface $user, UsernamePasswordToken $token )
    {
        if ( !$user instanceof EzUser ) {
            return parent::checkAuthentication( $user, $token );
        }

        // $currentUser can either be an instance of UserInterface or just the username/email (e.g. during form login).
        /** @var EzUser|string $currentUser */
        $currentUser = $token->getUser();
        if ( $currentUser instanceof UserInterface ) {
            if ($currentUser->getPassword() !== $user->getPassword()) {
                throw new BadCredentialsException( 'The credentials were changed from another session.' );
            }

            $apiUser = $currentUser->getAPIUser();
        } else  {
            // Try logging in by username first
            try {
                $apiUser = $this->repository->getUserService()
                    ->loadUserByCredentials( $token->getUsername(), $token->getCredentials() );
            } catch ( NotFoundException $e ) {
                // User was not found by username, try to get the login and load by credentials again
                try {
                    $users = $this->repository->getUserService()->loadUsersByEmail( $token->getUsername() );
                    if (count($users)) {
                        $userLogin = $users[0]->login;
                        $apiUser = $this->repository->getUserService()
                            ->loadUserByCredentials($userLogin, $token->getCredentials());
                    } else {
                        throw new BadCredentialsException('Invalid credentials', 0, $e);
                    }
                } catch( NotFoundException $e ) {
                    throw new BadCredentialsException( 'Invalid credentials', 0, $e );
                }
            }
        }

        //store session id the session before it will be refreshed by Symfony
        $this->storeAnonymousSessionId();
        // Finally inject current user in the Repository
        $this->repository->setCurrentUser( $apiUser );

        return true;
    }

    /**
     * stores the session id of the anonymous user in the session
     */
    protected function storeAnonymousSessionId()
    {
        $sessionId = $this->session->getId();
        $this->session->set( self::SES_ANONYMOUS_SESSION_ID, $sessionId );
    }
}
