<?php

namespace Bolt\Extension\Bobdenotter\Chmodinator;

use Bolt\Application;
use Bolt\BaseExtension;
use Symfony\Component\Finder\Finder;

class Extension extends BaseExtension
{

    var $locations;

    public function initialize()
    {

        $root = $this->app['resources']->getUrl('bolt');

        // Admin menu
        $this->addMenuOption('Chmodinator', $root . 'chmodinator', 'fa:hand-stop-o');

        $this->app->get($root . 'chmodinator', array($this, 'index'))
            ->bind('chmodinator')
        ;
        $this->app->get($root . 'chmodinator/check', array($this, 'check'))
            ->bind('chmodinator-check')
        ;

        $this->basepath = $this->app['resources']->getPath('root');

        $this->locations = array(
                $this->app['resources']->getPath('cachepath'),
                $this->app['resources']->getPath('configpath'),
                $this->app['resources']->getPath('extensionspath'),
                $this->app['resources']->getPath('filespath'),
                $this->app['resources']->getPath('databasepath'),
                $this->app['resources']->getPath('themepath'),
            );

        // dump($this->locations);

    }

    public function getName()
    {
        return "Chmødïna✝oR!!1";
    }

    public function index()
    {

        return $this->render('index.twig');

    }


    public function check()
    {

        $result = $this->app['cache']->clearCache();

        $files = '';
        $finder = new Finder();
        $finder->depth('<1');

        foreach($this->locations as $loc) {
            $finder->in($loc);
        }

        foreach ($finder as $file) {
            $res = $this->getPrintInfoFile($file);
            $files .= $res;
        }

        $data = array('files' => $files);

        return $this->render('index.twig', $data);

    }

    public function fix()
    {

        $result = $this->app['cache']->clearCache();

        $files = '';
        $finder = new Finder();
        $finder->depth('<1');

        foreach($this->locations as $loc) {
            $finder->in($loc);
        }

        foreach ($finder as $file) {
            $res = $this->getPrintInfoFile($file);
            $files .= $res;
        }

        $data = array('files' => $files);

        return $this->render('index.twig', $data);

    }



    public function render($template, $data = array())
    {

        $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/templates');


        return $this->app['render']->render($template, $data);

    }


    public function getPrintInfoFile($file)
    {

        $path = str_replace($this->basepath, '…/', $file->getRealPath());
        $dirname = dirname($path);
        $basename = basename($path);

        $owner = $file->getOwner();
        try {
            $owner_info = posix_getpwuid($owner);
            $owner = $owner_info['name'];
        } catch (\Exception $e) {
            // nothing
        }

        $group = $file->getGroup();
        try {
            $group_info = posix_getgrgid($group);
            $group = $group_info['name'];
        } catch (\Exception $e) {
            // nothing
        }


        $res = sprintf("<i class='fa fa-fw fa-%s %s'></i> %-21s %-7s <i class='fa fa-fw fa-%s'></i> %s/<b>%s</b>\n",
            $file->isWritable() ? 'check' : 'close',
            $file->isWritable() ? 'green' : 'red',
            $owner . ':' . $group,
            substr(sprintf('%o', $file->getPerms()), -4),
            $file->isDir() ? 'folder-open-o' : 'file-o',
            $dirname,
            $basename
        );


        return $res;

    }

}






