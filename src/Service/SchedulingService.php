<?php


namespace App\Service;


use App\Entity\SchedulingTime;
use Doctrine\ORM\EntityManagerInterface;

class SchedulingService
{
    private $em;
    private $userService;

    public function __construct(EntityManagerInterface $entityManager,UserService $userService)
    {
        $this->em = $entityManager;
        $this->userService = $userService;
    }
    public function chooseTimeSlot(SchedulingTime $schedulingTime){
        $room = $schedulingTime->getScheduling()->getRoom();
        $room->setScheduleMeeting(false);
        $room->setStart($schedulingTime->getTime());
        $end = clone $schedulingTime->getTime();
        $end->modify('+'.$room->getDuration().'min');
        $room->setEnddate($end);
        foreach ($room->getUser() as $data){
            $this->userService->addUser($data,$room);
        }
        $this->em->persist($room);
        $this->em->flush();
    }
}