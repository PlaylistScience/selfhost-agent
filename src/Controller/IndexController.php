<?php

namespace App\Controller;

use App\Service\Youtube;
use App\Form\YoutubeLinkType;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class IndexController extends AbstractController
{
    /**
     * @Route("/import/youtube", name="importYoutube")
     */
    public function importYoutube(Request $request, Youtube $youtube)
    {
        $form = $this->createForm(YoutubeLinkType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $url = $form->getData()['url'];
            $import = $youtube->setUrl($url)->import();

            return $this->redirectToRoute('playYoutube', ['id' => $youtube->getId()]);
        }

        return $this->render('youtube.html.twig', [
           'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/play/youtube/{id}", name="playYoutube")
     */
    public function playYoutube($id, Request $request, Youtube $youtube)
    {
        $url = 'http://localhost:9000/medley/youtube/F2qISnvhoAg.ogg?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=medley%2F20190216%2F%2Fs3%2Faws4_request&X-Amz-Date=20190216T192119Z&X-Amz-Expires=604800&X-Amz-SignedHeaders=host&X-Amz-Signature=04d761f1c1cf7090f12c3172b3a86991e35678d02ce8d5b7a1b02b8c05b38b22';

        return $this->render('youtube-play.html.twig', [
           'url' => $url
        ]);
    }
}
