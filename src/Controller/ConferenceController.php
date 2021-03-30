<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Form\CommentFormType;
use Doctrine\ORM\EntityManagerInterface;
use App\Message\CommentMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ConferenceRepository;
use Twig\Environment;

class ConferenceController extends AbstractController
{
    private $twig;
    private $entityManager;
    private $bus;
    private $conferenceRepository;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $bus, ConferenceRepository $conferenceRepository)
    {
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
        $this->$conferenceRepository = $conferenceRepository;
    }


    public function index(ConferenceRepository $conferenceRepository): Response
    {
        return new Response($this->twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]));
    }

    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, string $photoDir, string $conferenceRepository): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $e) {
                    // unable to upload the photo, give up
                }
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                    'user_ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('user-agent'),
                    'referrer' => $request->headers->get('referer'),
                    'permalink' => $request->getUri(),
                ];
              
                $this->bus->dispatch(new CommentMessage($comment->getId(), $context));
            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);
        
        return new Response($this->twig->render('conference/show.html.twig', [
            'conferences' => $this->$conferenceRepository->findAll(),
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView(),
        ]));
    }
}
