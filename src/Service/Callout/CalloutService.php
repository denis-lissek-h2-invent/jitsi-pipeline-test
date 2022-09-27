<?php

namespace App\Service\Callout;

use App\Entity\CalloutSession;
use App\Entity\Rooms;
use App\Entity\User;
use App\Service\adhocmeeting\AdhocMeetingService;
use App\Service\ThemeService;
use Doctrine\ORM\EntityManagerInterface;

class CalloutService
{




    public function __construct(
        private EntityManagerInterface $entityManager,
        private AdhocMeetingService    $adhocMeetingService,
        private ThemeService           $themeService,
    )
    {
    }

    /**
     * Starts the Callout Session Process.
     * @param Rooms $rooms
     * @param User $user
     * @param User $inviter
     * @return CalloutSession|null
     */
    public function initCalloutSession(Rooms $rooms, User $user, User $inviter): ?CalloutSession
    {
        $callout = $this->createCallout($rooms, $user, $inviter);
        return $callout;
    }

    /**
     * Creates a new CalloutSession and rings the calles user, if this user online and propably not a phone user
     * @param Rooms $rooms
     * @param User $user
     * @param User $inviter
     * @return CalloutSession|null
     */
    public function createCallout(Rooms $rooms, User $user, User $inviter): ?CalloutSession
    {
        $callout = $this->checkCallout($rooms, $user);
        if ($callout) {
            return $callout;
        }

        $this->adhocMeetingService->sendAddhocMeetingWebsocket($user, $inviter, $rooms);

        if (!$this->isAllowedToBeCalled($user)) {
            return null;
        }

        $callout = new CalloutSession();
        $callout->setUser($user)
            ->setRoom($rooms)
            ->setCreatedAt(new \DateTime())
            ->setInvitedFrom($inviter)
            ->setUid(md5(uniqid()))
            ->setState(CalloutSession::$INITIATED);
        $this->entityManager->persist($callout);
        $this->entityManager->flush();

        return $callout;
    }

    /**
     * checks is the callout session is already astablished
     * @param Rooms $rooms
     * @param User $user
     * @return CalloutSession|null
     */
    public function checkCallout(Rooms $rooms, User $user): ?CalloutSession
    {
        $callout = $this->entityManager->getRepository(CalloutSession::class)->findOneBy(array('room' => $rooms, 'user' => $user));
        return $callout;
    }

    /**
     * chechks either the user is alles to be called. is is done by check the env variable with the LDAP user properties
     * and the corresponding spezial fields, which are loaded from the ldap
     * @param User|null $user
     * @return bool
     */
    public function isAllowedToBeCalled(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        if (!$user->getLdapUserProperties()) {
            return false;
        }

        $calloutFields = $this->themeService->getApplicationProperties('LDAP_CALLOUT_FIELDS');
        foreach ($calloutFields as $ldapId => $fields) {
            foreach ($fields as $field) {
                if ($user->getLdapUserProperties()->getLdapNumber() === $ldapId) {
                    if (isset($user->getSpezialProperties()[$field]) && $user->getSpezialProperties()[$field] !== '') {
                        return true;
                    }
                }
            }
        }
        return false;
    }

}