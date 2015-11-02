<?php

namespace Bolt\Extension\Bobdenotter\Chmodinator;

use Bolt\Application;
use Bolt\BaseExtension;

class Extension extends BaseExtension
{


    public function initialize() {


        $root = $this->app['resources']->getUrl('bolt');

        // Admin menu
        $this->addMenuOption('Chmodinator', $root . 'labels', 'fa:hand-stop-o');

        $this->app->get($root . 'chmodinator', array($this, 'index'))
            ->bind('labels')
        ;

    }

    public function getName()
    {
        return "Chmødïna✝oR!!1";
    }

    public function index()
    {



        return $this->render('index.twig');

    }

    public function render($template, $data = array())
    {

        $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/templates');


        return $this->app['render']->render($template, $data);

    }

}






