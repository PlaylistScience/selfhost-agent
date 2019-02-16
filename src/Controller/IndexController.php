<?php

namespace App\Controller;

use App\Service\Youtube;
use App\Form\YoutubeLinkType;

use DaveRandom\Resume\{DefaultOutputWriter, RangeSet, ResourceServlet, InvalidRangeHeaderException, UnsatisfiableRangeException, NonExistentFileException, UnreadableFileException, SendFileFailureException};


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
            $data = $form->getData();
            $import = $youtube->setUrl($data['url'])->import();

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
        $url = $this->generateUrl('streamYoutube', [
            'id' => $id
        ]);

        return $this->render('youtube-play.html.twig', [
           'url' => $url
        ]);
    }

    private function sendStreamHeaders($rangeSet = null)
    {
        $stat = fstat($this->resource);
        $size = $stat['size'];
        $headers = [
            'content-type' => get_resource_type($this->resource),
            'content-length' => $size,

        ];

        if (null !== $rangeSet) {
            $headers['accept-ranges'] = 'bytes';
        }

        foreach ($headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    /**
     * @Route("/stream/youtube/{id}", name="streamYoutube")
     */
    public function streamYoutube($id, Request $request, Youtube $youtube)
    {
        $this->resource = $youtube->setId($id)->stream();
        $outputWriter = new DefaultOutputWriter();
        $rangeSet = RangeSet::createFromHeader('bytes=0-');

        if ($rangeSet === null) {
            // No ranges requested, just send the whole file
            $outputWriter->setResponseCode(200);
            $this->sendStreamHeaders();
            echo stream_get_contents($this->resource);

            return;
        }

        // Send the requested ranges
        $stat = fstat($this->resource);
        $size = $stat['size'];
        $ranges = $rangeSet->getRangesForSize($size);
        $contentRange = $this->getContentRangeHeader($rangeSet->getUnit(), $ranges, $size);

        $outputWriter->setResponseCode(206);
        $outputWriter->sendHeader('Content-Range', $contentRange);
        $this->sendStreamHeaders($rangeSet);

        foreach ($rangeSet->getRangesForSize($size) as $range) {
            echo stream_get_contents($this->resource, $range->getLength(), $range->getStart());
        }
    }

    /**
     * Create a Content-Range header corresponding to the specified unit and ranges
     *
     * @param string $unit
     * @param Range[] $ranges
     * @param int $size
     * @return string
     */
    public function getContentRangeHeader(string $unit, array $ranges, int $size): string
    {
        return $unit . ' ' . \implode(',', $ranges) . '/' . $size;
    }
}
