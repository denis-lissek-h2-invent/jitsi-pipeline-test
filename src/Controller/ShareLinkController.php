<?php

namespace App\Controller;

use App\Entity\Rooms;
use App\Entity\Subscriber;
use App\Entity\User;
use App\Form\Type\PublicRegisterType;
use App\Service\PexelService;
use App\Service\RoomService;
use App\Service\SubcriptionService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ShareLinkController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @Route("/room/share/link/{id}", name="share_link")
     * @ParamConverter("rooms")
     */
    public function index(Rooms $rooms): Response
    {
        if (!$rooms || !$rooms->getModerator() == $this->getUser() || $rooms->getPublic() != true) {
            throw new NotFoundHttpException('Not found');
        }
        return $this->render('share_link/__shareLinkModal.html.twig', array('room' => $rooms));

    }

    /**
     * @Route("/subscribe/self/{uid}", name="public_subscribe_participant")
     */
    public function participants($uid, Request $request, SubcriptionService $subcriptionService,TranslatorInterface $translator, PexelService $pexelService): Response
    {
        $rooms = null;
        $moderator = false;
        $rooms = $this->em->getRepository(Rooms::class)->findOneBy(array('uidParticipant'=>$uid));
        if(!$rooms){
            $rooms = $this->em->getRepository(Rooms::class)->findOneBy(array('uidModerator'=>$uid));
            if ($rooms){
                $moderator = true;
            }
        }
        $data = array('email' => '');
        $form = $this->createForm(PublicRegisterType::class, $data);
        $form->handleRequest($request);
        $errors = array();
        $snack = $translator->trans('Bitte geben Sie ihre Daten ein');
        $color = 'success';
        $server = $rooms->getServer();


        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $res = $subcriptionService->subscripe($data['email'], $rooms,$moderator);
            $snack = $res['text'];
            $color = $res['color'];
        }
        $image = $pexelService->getImageFromPexels();
        return $this->render('share_link/subscribe.html.twig', [
            'form' => $form->createView(),
            'snack' => $snack,
            'server' => $server,
            'image' => $image,
            'room' => $rooms,
            'color'=>$color,
        ]);
    }


    /**
     * @Route("/subscribe/optIn/{uid}", name="public_subscribe_doupleOptIn")
     */
    public function doupleoptin($uid, SubcriptionService $subcriptionService, TranslatorInterface $translator, UserService $userService,PexelService $pexelService): Response
    {
        $subscriber = $this->em->getRepository(Subscriber::class)->findOneBy(array('uid'=>$uid));
        $res = $subcriptionService->acceptSub($subscriber);

        $message = $res['message'];
        $title = $res['title'];
        $image = $pexelService->getImageFromPexels();
        return $this->render('share_link/subscribeSuccess.html.twig',array('message'=>$message,'title'=>$title,'image'=>$image));
    }
}
