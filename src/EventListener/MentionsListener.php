<?php

namespace App\EventListener;

use App\Event\NewCommentEvent;
use App\Event\NewSubmissionEvent;
use App\Markdown\MarkdownConverter;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

class MentionsListener implements EventSubscriberInterface {
    /**
     * @var MarkdownConverter
     */
    private $converter;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var UserRepository
     */
    private $users;

    public static function getSubscribedEvents() {
        return [
            NewCommentEvent::class => ['onNewComment'],
            NewSubmissionEvent::class => ['onNewSubmission'],
        ];
    }

    public function __construct(
        EntityManagerInterface $manager,
        MarkdownConverter $converter,
        RequestStack $requestStack,
        RouterInterface $router,
        UserRepository $users
    ) {
        $this->converter = $converter;
        $this->manager = $manager;
        $this->requestStack = $requestStack;
        $this->router = $router;
        $this->users = $users;
    }

    public function onNewSubmission(NewSubmissionEvent $event) {
        $submission = $event->getSubmission();

        if ($submission->getBody() === null) {
            return;
        }

        $html = $this->converter->convertToHtml($submission->getBody(), [
            'context' => 'submission',
            'submission' => $submission,
        ]);

        $users = $this->getUsersToNotify($html);

        foreach ($users as $user) {
            $submission->addMention($user);
        }

        $this->manager->flush();
    }

    public function onNewComment(NewCommentEvent $event) {
        $comment = $event->getComment();

        $html = $this->converter->convertToHtml($comment->getBody(), [
            'context' => 'comment',
            'comment' => $comment,
        ]);

        $users = $this->getUsersToNotify($html);

        foreach ($users as $user) {
            $comment->addMention($user);
        }

        $this->manager->flush();
    }

    /**
     * @param string $html
     *
     * @return \App\Entity\User[]
     */
    public function getUsersToNotify(string $html): array {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return [];
        }

        $urlMatcher = new UrlMatcher(
            $this->router->getRouteCollection(),
            (new RequestContext())
                ->fromRequest($request)
                ->setMethod('GET')
        );

        $hrefs = (new Crawler($html))
            ->filterXPath(\sprintf(
                '//a[starts-with(@href,"%s/user/")]',
                $request->getBasePath()
            ))
            ->extract(['href']);

        $usernames = [];
        $count = 0;

        foreach ($hrefs as $href) {
            try {
                $params = $urlMatcher->match($href);

                if (($params['_route'] ?? null) === 'user') {
                    if (!isset($usernames[$params['username']])) {
                        $usernames[$params['username']] = true;
                        $count++;
                    }
                }
            } catch (ExceptionInterface $e) {
            }

            if ($count == 10) {
                break;
            }
        }

        $usernames = array_keys($usernames);

        return $this->users->findByUsername($usernames);
    }
}
