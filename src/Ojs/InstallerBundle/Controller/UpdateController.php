<?php

namespace Ojs\InstallerBundle\Controller;

use Ojs\CoreBundle\Controller\OjsController as Controller;

class UpdateController extends Controller
{
    public function indexAction()
    {
        $data = [];
        $pwd = $this->get('kernel')->getRootDir();
        $consoleApp = $pwd.'/console';
        shell_exec("php $consoleApp doctrine:cache:clear-metadata --env=prod");
        $diff = shell_exec("/usr/bin/php $consoleApp doctrine:schema:update --dump-sql --env=prod");
        $data['diff'] = $diff;
        $data['data']['page'] = 'home';

        return $this->render('OjsInstallerBundle:Update:index.html.twig', $data);
    }

    public function updateAction()
    {
        $data = [];
        $pwd = $this->get('kernel')->getRootDir();
        $consoleApp = $pwd.'/console';
        shell_exec("php $consoleApp doctrine:cache:clear-metadata");
        $diff = shell_exec("/usr/bin/php $consoleApp doctrine:schema:update --force --env=prod");
        $data['data']['page'] = 'update';
        $data['diff'] = $diff;

        return $this->render('OjsInstallerBundle:Update:update.html.twig', $data);
    }
}
