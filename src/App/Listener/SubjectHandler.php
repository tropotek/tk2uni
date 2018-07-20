<?php
namespace App\Listener;

use Tk\Event\Subscriber;
use Tk\Kernel\KernelEvents;
use Tk\Event\GetResponseEvent;
use Tk\Event\AuthEvent;
use Tk\Auth\AuthEvents;
use Uni\Listener\MasqueradeHandler;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class SubjectHandler implements Subscriber
{

    /**
     * If we are in a subject URL then get the subject object and set it in the config
     * for global accessibility.
     *
     * @param GetResponseEvent $event
     * @throws \Exception
     */
    public function onRequest(GetResponseEvent $event)
    {
        $config = \App\Config::getInstance();
        $request = $event->getRequest();

        if ($config->getUser()) {
            \Tk\Log::info('- User: ' . $config->getUser()->getName() . ' <' . $config->getUser()->getEmail() . '> [ID: ' . $config->getUser()->getId() . ']');
            if (MasqueradeHandler::isMasquerading()) {
                $msq = MasqueradeHandler::getMasqueradingUser();
                \Tk\Log::info('  └ Msq: ' . $msq->getName() . ' [ID: ' . $msq->getId() . ']');
            }
        }
        if ($config->getInstitution()) {
            \Tk\Log::info('- Institution: ' . $config->getInstitution()->getName() . ' [ID: ' . $config->getInstitution()->getId() . ']');
        }
        if ($request->hasAttribute('subjectCode') && $config->getSubject()) {
            \Tk\Log::info('- Subject: ' . $config->getSubject()->name . ' [ID: ' . $config->getSubject()->getId() . ']');
        }

    }

    /**
     * Ensure this is run after App\Listener\CrumbsHandler::onFinishRequest()
     *
     * @param AuthEvent $event
     * @throws \Tk\Db\Exception
     * @throws \Tk\Exception
     */
    public function onLoginSuccess(AuthEvent $event)
    {
        $result = $event->getResult();
        /* @var \Uni\Db\User $user */
        $user = \Uni\Db\UserMap::create()->find($result->getIdentity());
        $institution = $user->getInstitution();

        // Enroll to any pending subjects
        if ($institution && $user->hasRole(array(\Uni\Db\User::ROLE_STUDENT, \Uni\Db\User::ROLE_STAFF)) ) {
            // Get any alias email addresses
            $ldapData = $user->getData()->get('ldap.data');
            $alias = array();
            if ($ldapData && !empty($ldapData['mailalternateaddress'][0])) {
                $alias[] = $ldapData['mailalternateaddress'][0];
            }
            $emailList = array_merge(array($user->email), $alias);
            foreach ($emailList as $i => $email) {
                $subjectList = \Uni\Db\SubjectMap::create()->findPendingPreEnrollments($institution->getId(), $email);
                /* @var \Uni\Db\Subject $subject */
                foreach ($subjectList as $subject) {
                    \Uni\Db\SubjectMap::create()->addUser($subject->getId(), $user->getId());
                }
            }
        }

    }


    /**
     * getSubscribedEvents
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            AuthEvents::LOGIN_SUCCESS => 'onLoginSuccess',
            KernelEvents::REQUEST => array('onRequest', -1)
        );
    }
}
