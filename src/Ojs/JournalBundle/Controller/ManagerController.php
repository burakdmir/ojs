<?php

namespace Ojs\JournalBundle\Controller;

use Ojs\AdminBundle\Form\Type\QuickSwitchType;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\CoreBundle\Params\JournalStatuses;
use Ojs\JournalBundle\Entity\Journal;
use Ojs\JournalBundle\Event\JournalEvent;
use Ojs\JournalBundle\Event\JournalEvents;
use Ojs\JournalBundle\Form\Type\JournalType;
use Ojs\JournalBundle\Form\Type\JournalArticleTypesType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ManagerController extends Controller
{
    /**
     * @return Response
     */
    public function journalSettingsAction() 
    {
        $em = $this->getDoctrine()->getManager();
        $journal = $this->get("ojs.journal_service")->getSelectedJournal(false);
        $this->throw404IfNotFound($journal);
        if (!$this->isGranted('EDIT', $journal)) {
            throw new AccessDeniedException("You not authorized for this page!");
        }
        if($journal->getCountry() == null){
            $journal->setCountry($em->getRepository('BulutYazilimLocationBundle:Country')->find($this->getParameter('country_id')));
        }

        $form = $this->createJournalEditForm($journal);

        return $this->render(
            'OjsJournalBundle:Manager:journal_settings.html.twig',
            array(
                'entity' => $journal,
                'edit_form' => $form->createView(),
            )
        );
    }

    /**
     * @return Response
     */
    public function ArticleTypesSettingsAction()
    {
        $em = $this->getDoctrine()->getManager();
        $journal = $this->get("ojs.journal_service")->getSelectedJournal(false);
        $this->throw404IfNotFound($journal);
        if (!$this->isGranted('EDIT', $journal)) {
            throw new AccessDeniedException("You not authorized for this page!");
        }

        $form = $this->createArticleTypesEditForm($journal);

        return $this->render(
            'OjsJournalBundle:Manager:article_types.html.twig',
            array(
                'entity' => $journal,
                'form' => $form->createView(),
            )
        );
    }

    private function createJournalEditForm(Journal $journal)
    {
        $form = $this->createForm(
            new JournalType(),
            $journal,
            array(
                'action' => $this->generateUrl('ojs_journal_settings_update', ['journalId' => $journal->getId()]),
                'method' => 'PUT',
            )
        );
        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }


    private function createArticleTypesEditForm(Journal $journal)
    {
        $form = $this->createForm(
            new JournalArticleTypesType(),
            $journal,
            array(
                'action' => $this->generateUrl('ojs_article_types_settings_update', ['journalId' => $journal->getId()]),
                'method' => 'PUT',
            )
        );
        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * @param  Request $request
     * @return RedirectResponse|Response
     */
    public function updateJournalAction(Request $request)
    {
        $eventDispatcher = $this->get('event_dispatcher');

        $em = $this->getDoctrine()->getManager();
        $entity = $this->get('ojs.journal_service')->getSelectedJournal(false);
        $this->throw404IfNotFound($entity);

        if (!$this->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException("You are not authorized for this page!");
        }

        $editForm = $this->createJournalEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $entity->setLanguageCodeSet($entity->getLanguages());
            $entity->addLanguage($entity->getMandatoryLang());

            $event = new JournalEvent($entity);
            $eventDispatcher->dispatch(JournalEvents::PRE_UPDATE, $event);

            $em->persist($event->getJournal());
            $em->flush();

            $event = new JournalEvent($event->getJournal());
            $eventDispatcher->dispatch(JournalEvents::POST_UPDATE, $event);

            if ($event->getResponse()) {
                return $event->getResponse();
            }

            $this->successFlashBag('successful.update');
            return $this->redirectToRoute('ojs_journal_settings_index', ['journalId' => $entity->getId()]);
        }

        return $this->render(
            'OjsJournalBundle:Manager:journal_settings.html.twig',
            array(
                'entity' => $entity,
                'edit_form' => $editForm->createView(),
            )
        );
    }

    /**
     * @param  Request $request
     * @return RedirectResponse|Response
     */
    public function updateArticleTypesAction(Request $request)
    {
        $eventDispatcher = $this->get('event_dispatcher');

        $em = $this->getDoctrine()->getManager();
        $entity = $this->get('ojs.journal_service')->getSelectedJournal(false);
        $this->throw404IfNotFound($entity);

        if (!$this->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException("You are not authorized for this page!");
        }

        $form = $this->createArticleTypesEditForm($entity);
        $form->handleRequest($request);
        if ($form->isValid()) {

            $event = new JournalEvent($entity);
            $eventDispatcher->dispatch(JournalEvents::PRE_UPDATE, $event);

            $em->persist($event->getJournal());
            $em->flush();

            $event = new JournalEvent($event->getJournal());
            $eventDispatcher->dispatch(JournalEvents::POST_UPDATE, $event);

            if ($event->getResponse()) {
                return $event->getResponse();
            }

            $this->successFlashBag('successful.update');
            return $this->redirectToRoute('ojs_article_types_settings_index', ['journalId' => $entity->getId()]);
        }

        return $this->render(
            'OjsJournalBundle:Manager:article_types.html.twig',
            array(
                'entity' => $entity,
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @return Response
     */
    public function userIndexAction()
    {
        $switcher = $userJournals = null;
        $articles = $this
            ->getDoctrine()
            ->getRepository('OjsJournalBundle:Article')
            ->findBy(['submitterUser' => $this->getUser()], ['submissionDate' => 'DESC']);
        $journalUsers = array();

        if ($this->getUser()->isAdmin()) {
            $switcher = $this->createForm(new QuickSwitchType(), null)->createView();
        } else {
            $journalUsers = $this
                ->getDoctrine()
                ->getRepository('OjsJournalBundle:JournalUser')
                ->findBy(['user' => $this->getUser()]);

            foreach($journalUsers as $journalUserKey => $journalUser){
                if($journalUser->getJournal()->getStatus() == JournalStatuses::STATUS_REJECTED){
                    unset($journalUsers[$journalUserKey]);
                }
            }
        }

        $response = $this->render(
            'OjsJournalBundle:User:home.html.twig',
            [
                'switcher' => $switcher,
                'articles' => $articles,
                'data' => $this->createStats(),
                'journalUsers' => $journalUsers
            ]
        );

        return $response;
    }

    /**
     *  Arranges statistics
     * @return array
     */
    private function createStats()
    {
        $generator = $this->get('ojs.graph.data.generator');

        $lastMonth = ['x'];
        for ($i = 0; $i < 30; $i++) {
            $lastMonth[] = date($generator->getDateFormat(), strtotime('-'.$i.' days'));
        }

        $slicedLastMonth = array_slice($lastMonth, 1);

        $articles = $this
            ->getDoctrine()
            ->getRepository('OjsJournalBundle:Article')
            ->findBy(['submitterUser' => $this->getUser()]);

        $json = [
            'dates' => $lastMonth,
            'articleViews' => $generator->generateArticleBarChartDataDoctrine($articles, $slicedLastMonth),
            'articleFileDownloads' => $generator->generateArticleFilePieChartDataDoctrine($articles, $slicedLastMonth),
        ];

        $data = [
            'stats' => json_encode($json),
            'articles' => $generator->generateArticleViewsDataDoctrine($articles),
            'articleFiles' => $generator->generateArticleFileDownloadsDataDoctrine($articles),
            'articlesMonthly' => $generator->generateArticleViewsDataDoctrine($articles, $slicedLastMonth),
            'articleFilesMonthly' => $generator->generateArticleFileDownloadsDataDoctrine($articles, $slicedLastMonth),
        ];

        return $data;
    }

    /**
     * @return Response
     * @throws HttpException
     */
    public function myJournalsAction()
    {
        $user_id = $this->getUser()->getId();
        if (!$user_id) {
            throw new HttpException(403, 'ojs.403');
        }

        $entities = $this
            ->getDoctrine()
            ->getRepository('OjsJournalBundle:JournalUser')
            ->findBy(['user' => $this->getUser()]);

        return $this->render(
            'OjsJournalBundle:User:myjournals.html.twig',
            array(
                'entities' => $entities,
            )
        );
    }
}
