<?php

namespace App\Controller;

use App\Service\Youtube;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/import/youtube/{id}", name="importYoutube")
     */
    public function importYoutube($id, Youtube $youtube)
    {
        dump($youtube->setId($id)->import());
        exit();
    }
}
