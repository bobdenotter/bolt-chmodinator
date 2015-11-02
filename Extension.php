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

        $this->app->get($root . 'chmodinator', array($this, 'index'))->bind('chmodinator');
        $this->app->get($root . 'chmodinator/check', array($this, 'check'))->bind('chmodinator-check');
        $this->app->get($root . 'chmodinator/fix', array($this, 'fix'))->bind('chmodinator-fix');

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

        $msg = "Welcome to the <strong>Chmodinator</strong>! If you're having
        issues with your Bolt files not being removable using your FTP client or
        vice-versa, this extension will help you sort it out. Clicking the 'Fix'
        button will try to make all files in the data folders writable to 'all',
        and it will inform you of any files it couldn't modify, so you can
        change them using the command line or your (S)FTP client.<br><br>

        We are aware that using this tool is not considered 'good practice'. If
        possible, you should work with your system administrator to get things
        set up properly. If that's not an option, of if you're on shared
        hosting, this extension will help you out!";

        $data = array('msg' => $msg);

        return $this->render('index.twig', $data);

    }


    public function check()
    {
        // Clear the cache beforehand, ar this will make sure we have a lot less files to deal with.
        $result = $this->app['cache']->clearCache();

        $files = '';
        $finder = new Finder();
        $finder->depth('<4');

        foreach($this->locations as $loc) {
            $finder->in($loc);
        }

        foreach ($finder as $file) {
            $res = $this->getPrintInfoFile($file);
            $files .= $res;
        }

        $msg = "Below you'll see the output of the checks. Lines marked with a
        red '<i class='fa fa-close red'></i>' are not writable, and should be
        fixed. For files owned by you, you should use the command-line or your
        (S)FTP client to make sure these files are set correctly.";

        $data = array('files' => $files, 'msg' => $msg);

        return $this->render('index.twig', $data);

    }

    public function fix()
    {
        // Clear the cache beforehand, ar this will make sure we have a lot less files to deal with.
        $result = $this->app['cache']->clearCache();

        $files = '';
        $finder = new Finder();
        $finder->depth('<4');

        foreach($this->locations as $loc) {
            $finder->in($loc);
        }

        foreach ($finder as $file) {

            $perms = substr(sprintf('%o', $file->getPerms()), -3);

            if ($file->isDir() && $perms != '777') {
                @chmod($file, 0777);
                if (!$file->isWritable()) {
                    $res = $this->getPrintInfoFile($file);
                    $files .= $res;
                }
            } else if (!$file->isDir() && !$this->checkFilePerms($perms)) {
                // echo "joe! " . $this->checkFilePerms($perms);
                @chmod($file, 0666);
                $res = $this->getPrintInfoFile($file);
                $files .= $res;
            }


        }

        $msg = "Below you'll see the output of the changes. If there are lines
        left with a red '<i class='fa fa-close red'></i>', then these files /
        folders could now be modified by Bolt or the Chmodinator. You should use
        the command-line or your (S)FTP client to make sure these files are set
        correctly.";

        $data = array('files' => $files, 'msg' => $msg);

        return $this->render('index.twig', $data);

    }



    public function render($template, $data = array())
    {

        $this->app['twig.loader.filesystem']->addPath(__DIR__ . '/templates');


        return $this->app['render']->render($template, $data);

    }


    public function getPrintInfoFile($file)
    {

        $path = str_replace($this->basepath, '…/', $file->getPathname());
        $dirname = dirname($path);
        $basename = basename($path);

        try {
            $owner = @$file->getOwner();
            try {
                $owner_info = posix_getpwuid($owner);
                $owner = $owner_info['name'];
            } catch (\Exception $e) {
                // nothing
            }
        } catch (\Exception $e) {
            $owner = '-';
        }


        try {
            $group = @$file->getGroup();
            try {
                $group_info = posix_getgrgid($group);
                $group = $group_info['name'];
            } catch (\Exception $e) {
                // nothing
            }
        } catch (\Exception $e) {
            $group = '-';
        }

        try {
            $perms = substr(sprintf('%o', @$file->getPerms()), -3);
        } catch (\Exception $e) {
            $perms = "000";
        }

        $res = sprintf("<i class='fa fa-fw fa-%s %s'></i> %-21s %-7s <i class='fa fa-fw fa-%s'></i> %s/<b>%s</b>\n",
            $file->isWritable() ? 'check' : 'close',
            $file->isWritable() ? 'green' : 'red',
            $owner . ':' . $group,
            $perms,
            $file->isDir() ? 'folder-open-o' : 'file-o',
            $dirname,
            $basename
        );


        return $res;

    }

    private function checkFilePerms($perms)
    {
        $ok = array('666', '667', '676', '677', '766', '767', '776', '777');

        return in_array($perms, $ok);

    }

}






