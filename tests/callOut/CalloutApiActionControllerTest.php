<?php

namespace App\Tests\callOut;

use App\Entity\CallerId;
use App\Entity\CalloutSession;
use App\Entity\User;
use App\Repository\CallerIdRepository;
use App\Repository\CalloutSessionRepository;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use function PHPUnit\Framework\assertEquals;

class CalloutApiActionControllerTest extends WebTestCase
{
    private $client;
    private $room;
    private $authHEader;
    private CalloutSession $calloutSession;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->authHEader = [
            'HTTP_AUTHORIZATION' => "Bearer 123456",
        ];
        $this->client = static::createClient();
        $manager = self::getContainer()->get(EntityManagerInterface::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $this->room = $roomRepo->findOneBy(array('name' => 'This is a room with Lobby'));
        $user = $userRepo->findOneBy(array('email' => 'ldapUser@local.de'));

        $callerUserId = new CallerId();
        $callerUserId->setCreatedAt(new \DateTime())
            ->setRoom($this->room)
            ->setUser($user)
            ->setCallerId('987654321');
        $manager->persist($callerUserId);
        $manager->flush();

        $this->client->loginUser($this->room->getModerator());
        $invite = $userRepo->findOneBy(array('email' => 'ldapUser@local.de'));
        $crawler = $this->client->request('POST', '/room/callout/invite/' . $this->room->getUidReal(), array('uid' => $invite->getEmail()));
        $calloutRepo = self::getContainer()->get(CalloutSessionRepository::class);
        $this->calloutSession = $calloutRepo->findOneBy(array('room' => $this->room, 'user' => $invite));

    }

    public function testDialin(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'OK',
            'links' => array('accept' => '/api/v1/lobby/sip/pin/' . $this->calloutSession->getRoom()->getCallerRoom()->getCallerId() . '?caller_id=987654321012&pin=987654321',
                'refuse' => '/api/v1/call/out/refuse/' . $this->calloutSession->getUid(),
                'timeout' => '/api/v1/call/out/timeout/' . $this->calloutSession->getUid(),
                'error' => '/api/v1/call/out/error/' . $this->calloutSession->getUid(),
                'later' => '/api/v1/call/out/later/' . $this->calloutSession->getUid(),
                'dial' => '/api/v1/call/out/dial/' . $this->calloutSession->getUid(),
                'occupied' => '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(),
                'ringing' => '/api/v1/call/out/ringing/' . $this->calloutSession->getUid(),
                'unreachable' => '/api/v1/call/out/unreachable/' . $this->calloutSession->getUid()
            ),
        ), json_decode($this->client->getResponse()->getContent(), true));
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
    }

    public function testError(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $crawler = $this->client->request('GET', '/api/v1/call/out/error/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'DELETED',
            'links' => array(),
        ), json_decode($this->client->getResponse()->getContent(), true));
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(0, $crawler->filter('.calloutsymbol')->count());
    }

    public function testUnreachable(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $crawler = $this->client->request('GET', '/api/v1/call/out/unreachable/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'DELETED',
            'links' => array(),
        ), json_decode($this->client->getResponse()->getContent(), true));
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(0, $crawler->filter('.calloutsymbol')->count());
    }


    public function testrefuse(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $crawler = $this->client->request('GET', '/api/v1/call/out/refuse/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'DELETED',
            'links' => array(),
        ), json_decode($this->client->getResponse()->getContent(), true));
        assertEquals(0, $crawler->filter('.calloutsymbol')->count());
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(0, $crawler->filter('.calloutsymbol')->count());
    }

    public function testTimeout(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $crawler = $this->client->request('GET', '/api/v1/call/out/timeout/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'ON_HOLD',
            'links' => array(
                'back' => '/api/v1/call/out/back/' . $this->calloutSession->getUid()
            ),
            'pin' => '987654321',
            'room_number' => $this->room->getCallerRoom()->getCallerId(),
        ), json_decode($this->client->getResponse()->getContent(), true));
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());

        $this->assertSelectorTextContains('.calloutsymbol .badge', 'Nicht abgenommen');
    }

    public function testLater(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $crawler = $this->client->request('GET', '/api/v1/call/out/later/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'ON_HOLD',
            'links' => array(
                'back' => '/api/v1/call/out/back/' . $this->calloutSession->getUid()
            ),
            'pin' => '987654321',
            'room_number' => $this->room->getCallerRoom()->getCallerId(),
        ), json_decode($this->client->getResponse()->getContent(), true));

        $this->client->loginUser(new User());
        $user = $this->calloutSession->getRoom()->getModerator();

        $this->client->loginUser($user);
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $this->assertSelectorTextContains('.calloutsymbol .badge', 'Später');
    }

    public function testBack(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $crawler = $this->client->request('GET', '/api/v1/call/out/later/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'ON_HOLD',
            'links' => array(
                'back' => '/api/v1/call/out/back/' . $this->calloutSession->getUid()
            ),
            'pin' => '987654321',
            'room_number' => $this->room->getCallerRoom()->getCallerId(),
        ), json_decode($this->client->getResponse()->getContent(), true));

        $this->client->loginUser(new User());
        $user = $this->calloutSession->getRoom()->getModerator();

        $this->client->loginUser($user);
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $this->assertSelectorTextContains('.calloutsymbol .badge', 'Später');

        $crawler = $this->client->request('GET', '/api/v1/call/out/back/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'DIALED',
            'links' => array('accept' => '/api/v1/lobby/sip/pin/' . $this->calloutSession->getRoom()->getCallerRoom()->getCallerId() . '?caller_id=987654321012&pin=987654321',
                'refuse' => '/api/v1/call/out/refuse/' . $this->calloutSession->getUid(),
                'timeout' => '/api/v1/call/out/timeout/' . $this->calloutSession->getUid(),
                'error' => '/api/v1/call/out/error/' . $this->calloutSession->getUid(),
                'later' => '/api/v1/call/out/later/' . $this->calloutSession->getUid(),
                'dial' => '/api/v1/call/out/dial/' . $this->calloutSession->getUid(),
                'occupied' => '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(),
                'ringing' => '/api/v1/call/out/ringing/' . $this->calloutSession->getUid(),
                'unreachable' => '/api/v1/call/out/unreachable/' . $this->calloutSession->getUid()
            ),
        ), json_decode($this->client->getResponse()->getContent(), true));

    }

    public function testRinging(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol .fa-phone')->count());
        $crawler = $this->client->request('GET', '/api/v1/call/out/ringing/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'RINGING',
            'pin' => '987654321',
            'room_number' => $this->room->getCallerRoom()->getCallerId(),
            'links' => array(
                'accept' => '/api/v1/lobby/sip/pin/' . $this->calloutSession->getRoom()->getCallerRoom()->getCallerId() . '?caller_id=987654321012&pin=987654321',
                'refuse' => '/api/v1/call/out/refuse/' . $this->calloutSession->getUid(),
                'ringing' => '/api/v1/call/out/ringing/' . $this->calloutSession->getUid(),
                'timeout' => '/api/v1/call/out/timeout/' . $this->calloutSession->getUid(),
                'error' => '/api/v1/call/out/error/' . $this->calloutSession->getUid(),
                'unreachable' => '/api/v1/call/out/unreachable/' . $this->calloutSession->getUid(),
                'later' => '/api/v1/call/out/later/' . $this->calloutSession->getUid(),
                'dial' => '/api/v1/call/out/dial/' . $this->calloutSession->getUid(),
                'occupied' => '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(),
            )
        ), json_decode($this->client->getResponse()->getContent(), true));

        $this->client->loginUser(new User());
        $user = $this->calloutSession->getRoom()->getModerator();

        $this->client->loginUser($user);
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $this->assertSelectorExists('.calloutsymbol .fa-phone-volume');


        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol .fa-phone-volume')->count());
        assertEquals(0, $crawler->filter('.calloutsymbol .fa-phone')->count());
    }

    public function testOccupied(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'status' => 'ON_HOLD',
            'links' => array(
                'back' => '/api/v1/call/out/back/' . $this->calloutSession->getUid()
            ),
            'pin' => '987654321',
            'room_number' => $this->room->getCallerRoom()->getCallerId(),
        ), json_decode($this->client->getResponse()->getContent(), true));
        $user = $this->calloutSession->getRoom()->getModerator();
        $this->client->loginUser($user);
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $this->assertSelectorTextContains('.calloutsymbol .badge', 'Besetzt');
    }

    public function testOccupiedthreeTimes(): void
    {
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $user = $this->calloutSession->getRoom()->getModerator();
        $this->client->loginUser($user);
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $this->assertSelectorNotExists('.no-more-retries');

        $crawler = $this->client->request('POST', '/room/callout/invite/' . $this->room->getUidReal(), array('uid' => $this->calloutSession->getUser()->getEmail()));
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        $this->assertSelectorNotExists('.no-more-retries');

        $crawler = $this->client->request('POST', '/room/callout/invite/' . $this->room->getUidReal(), array('uid' => $this->calloutSession->getUser()->getEmail()));
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        $this->assertSelectorTextContains('.no-more-retries', 'Keine weiteren Anrufversuche möglich');

        $crawler = $this->client->request('POST', '/room/callout/invite/' . $this->room->getUidReal(), array('uid' => $this->calloutSession->getUser()->getEmail()));
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/dial/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        $crawler = $this->client->request('GET', '/api/v1/call/out/occupied/' . $this->calloutSession->getUid(), [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();


        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
        $this->assertSelectorTextContains('.no-more-retries', 'Keine weiteren Anrufversuche möglich');


    }

    public function testDialPoolEmpty(): void
    {
        $this->client->request('GET', '/api/v1/call/out/dial/', [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'calls' => array()
        ), json_decode($this->client->getResponse()->getContent(), true));
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
    }
    public function testOnHoldPoolEmpty(): void
    {
        $this->client->request('GET', '/api/v1/call/out/on_hold/', [], [], $this->authHEader);
        $this->assertResponseIsSuccessful();
        self::assertEquals(array(
            'calls' => array()
        ), json_decode($this->client->getResponse()->getContent(), true));
        $url = '/room/join/b/' . $this->room->getId();
        $crawler = $this->client->request('GET', $url);
        assertEquals(1, $crawler->filter('.calloutsymbol')->count());
    }



}
