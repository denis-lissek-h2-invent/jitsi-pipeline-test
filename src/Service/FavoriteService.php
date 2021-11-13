<?php

namespace App\Service;

use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class FavoriteService
{
    private $em;
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    public function changeFavorite(User $user, Rooms $room)
    {
        if (in_array($user, $room->getUser()->toArray())) {
            if (in_array($room, $user->getFavorites()->toArray())) {
                $user->removeFavorite($room);
            } else {
                $user->addFavorite($room);
            }
            $this->em->persist($user);
            $this->em->flush();
        }else{
            return false;
        }
        return true;
    }
    public function cleanFavorites(User $user){
        $favs = $user->getFavorites();
        $now = (new \DateTime())->setTimezone(new \DateTimeZone('utc'));
        foreach ($favs as $data){
            if($data->getEndDateUtc() < $now && $data->getPersistantRoom() !== true && $data->getScheduleMeeting() !== true){
                $user->removeFavorite($data);
            }
        }
        $this->em->persist($user);
        $this->em->flush();
    }
}