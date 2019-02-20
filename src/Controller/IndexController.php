<?php

namespace App\Controller;

use App\Service\{Youtube, Soundcloud};
use App\Form\{YoutubeLinkType, SoundcloudLinkType};

use DaveRandom\Resume\{DefaultOutputWriter, RangeSet, ResourceServlet, InvalidRangeHeaderException, UnsatisfiableRangeException, NonExistentFileException, UnreadableFileException, SendFileFailureException};

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IndexController extends AbstractController
{
    public function __construct(Youtube $youtube, Soundcloud $soundcloud)
    {
        $this->youtube = $youtube;
        $this->soundcloud = $soundcloud;
    }
    /**
     * @Route("/", name="index")
     */
    public function index(Request $request)
    {
        return $this->render('index.html.twig', [
            'youtube' => $this->youtube->fetchAll(),
            'soundcloud' => $this->soundcloud->fetchAll(),
        ]);
    }

    /**
     * @Route("/import/youtube", name="importYoutube")
     */
    public function importYoutube(Request $request)
    {
        $form = $this->createForm(YoutubeLinkType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $import = $this->youtube->setUrl($data['url'])->import();

            return $this->redirectToRoute('playYoutube', ['id' => $this->youtube->getId()]);
        }

        return $this->render('youtube.html.twig', [
           'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/import/soundcloud", name="importSoundcloud")
     */
    public function importSoundcloud(Request $request)
    {
        $form = $this->createForm(SoundcloudLinkType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $import = $this->soundcloud->setUrl($data['url'])->import();

            return $this->redirectToRoute('playSoundcloud', ['filename' => $this->youtube->getId()]);
        }

        return $this->render('soundcloud.html.twig', [
           'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/play/youtube/{id}", name="playYoutube")
     */
    public function playYoutube($id, Request $request)
    {
        $url = $this->generateUrl('streamYoutube', [
            'id' => $id
        ]);

        return $this->render('youtube-play.html.twig', [
           'url' => $url
        ]);
    }

    /**
     * @Route("/play/soundcloud/{id}", name="playSoundcloud")
     */
    public function playSoundcloud($id, Request $request)
    {
        $url = $this->generateUrl('streamSoundcloud', [
            'filename' => $id
        ]);

        return $this->render('soundcloud-play.html.twig', [
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
    public function streamYoutube($id, Request $request)
    {
        $this->resource = $this->youtube->setId($id)->stream();
        $this->streamResource();

        exit();
    }

    /**
     * @Route("/stream/soundcloud/{filename}", name="streamSoundcloud")
     */
    public function streamSoundcloud($filename, Request $request)
    {
        $this->resource = $this->soundcloud->stream($filename);
        $this->streamResource();

        exit();
    }

    private function streamResource()
    {
        $outputWriter = new DefaultOutputWriter();
        $rangeSet = RangeSet::createFromHeader('bytes=0-');

        if ($rangeSet === null) {
            // No ranges requested, just send the whole file
            $outputWriter->setResponseCode(200);
            $this->sendStreamHeaders();
            echo stream_get_contents($this->resource);

            exit();
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
