<?php


namespace App\Service;


use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class JoinService
{
    private $parameterBag;
    private $em;
    private $translator;
    private $urlGenerator;
    private $roomService;
    private $response;
    public function __construct(RouterInterface $response, RoomService $roomService, UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag,EntityManagerInterface $entityManager,TranslatorInterface $translator)
    {
        $this->parameterBag = $parameterBag;
        $this->em = $entityManager;
        $this->translator = $translator;
        $this->urlGenerator = $urlGenerator;
        $this->roomService = $roomService;
        $this->response = $response;
    }

    public function join(FormInterface  $form,?Rooms $room,?User $aktUser,&$snack, &$color){
        $errors = array();
        if ($room) {
            $now = new \DateTime();
            $start = null;
            if($room->getStart()){
                $start = (clone $room->getStart())->modify('-30min');
            }
            if (
                ($start && $start < $now && $room->getEnddate() > $now)
                || $aktUser === $room->getModerator()
                || ($room->getPersistantRoom())
            ) {
                if ($form->isSubmitted() && $form->isValid()) {
                    $search = $form->getData();
                    $room = $this->em->getRepository(Rooms::class)->findOneBy(['uid' => $search['uid']]);
                    $user = $this->em->getRepository(User::class)->findOneBy(['email' => $search['email']]);

                    if ($form->get('joinApp')->isClicked()) {
                        $type = 'a';
                    } elseif ($form->get('joinBrowser')->isClicked()) {
                        $type = 'b';
                    }

                    if (
                        count($errors) == 0
                        && $room
                        && $user
                        && (in_array($user, $room->getUser()->toarray()) || $room->getTotalOpenRooms())
                    ) {
                        if ($this->onlyWithUserAccount($room) || $this->userAccountLogin($room, $user)) {
                            return new RedirectResponse($this->urlGenerator->generate('room_join', ['room' => $room->getId(), 't' => $type]));
                        }

                        $url = $this->roomService->join($room, $user, $type, $search['name']);

                        $res = new RedirectResponse($url);
                        $res->headers->setCookie(new Cookie('name', $search['name'], (new \DateTime())->modify('+365 days')));
                        return $res;

                    }
                    if($room->getTotalOpenRooms()){
                        $url = $this->urlGenerator->generate('room_waiting',array('name'=>$search['name'],'uid'=>$room->getUid(),'type'=>$type));
                        $res = new RedirectResponse(($url));
                        $res->headers->setCookie(new Cookie('name', $search['name'], (new \DateTime())->modify('+365 days')));
                        return $res;
                    }
                    $snack = $this->translator->trans('Konferenz nicht gefunden. Zugangsdaten erneut eingeben');
                }
            } else {
                try {
                    $snack = $this->translator->trans('Der Beitritt ist nur von {from} bis {to} möglich',
                        array(
                            '{from}' => $start->format('d.m.Y H:i'),
                            '{to}' => $room->getEnddate()->format('d.m.Y H:i')
                        )
                    );
                    $color = 'danger';
                }catch (\Exception $exception){

                }
            }
        }
        return null;
    }
    /**
     * function onlyWithUserAccount
     * Return if only users with account can join the conference
     * @return boolean
     * @author Andreas Holzmann
     */
    function onlyWithUserAccount(?Rooms $room)
    {
        if ($room) {
            return $this->parameterBag->get('laF_onlyRegisteredParticipents') == 1 || //only registered Users globally set
                $room->getOnlyRegisteredUsers();
        }
        return false;
    }

    /**
     * function userAccountLogin
     * Return boolean if account must login to join the conference
     * @return boolean
     * @author Andreas Holzmann
     */
    function userAccountLogin(?Rooms $room, ?User $user)
    {
        if ($room) {
            return $user && $user->getKeycloakId() !== null; // Registered Users have to login before they can join the conference
        }
        return false;
    }
}