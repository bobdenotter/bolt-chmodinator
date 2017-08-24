<?php

namespace Bolt\Extension\Bobdenotter\Chmodinator;

use Bolt\Extension\SimpleExtension;
use Bolt\Menu\MenuEntry;
use Bolt\Version;
use Silex\Application;
use Silex\ControllerCollection;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Chmodinator extension class.
 *
 * @author Bob den Otter <bob@twokings.nl>
 */
class ChmodinatorExtension extends SimpleExtension
{
    protected $key = false;

    /**
     * Add a backend menu entry under 'extensions'.
     *
     * @see https://docs.bolt.cm/extensions/intermediate/admin-menus#registering-menu-entries
     *
     * @return array
     */
    protected function registerMenuEntries()
    {
        $menu = new MenuEntry('Chmodinator-menu', 'chmodinator');
        $menu->setLabel('Chmodinator')
            ->setIcon('fa:hand-stop-o')
            ->setPermission('settings')
        ;
        return [
            $menu,
        ];
    }


    protected function registerBackendRoutes(ControllerCollection $collection)
    {
        $baseUrl = Version::compare('3.2.999', '<')
            ? '/extensions/chmodinator'
            : '/extend/chmodinator'
        ;

        $collection->get($baseUrl, [$this, 'index']);
        $collection->get($baseUrl . '/check', [$this, 'check']);
        $collection->get($baseUrl . '/fix', [$this, 'fixAll']);
        $collection->get($baseUrl . '/wipe', [$this, 'wipeCache']);

        $collection->before([$this, 'before']);
    }


    /**
     * @param Request $request
     * @param Application $app
     * @return null|RedirectResponse
     */
    public function before(Request $request, Application $app)
    {
        $config = $this->getConfig();
        $allowed = $app['users']->isAllowed('dashboard') || ($config['key'] === $request->get('key'));

        $app['debug'] = false;

        if (!$allowed) {
            /** @var UrlGeneratorInterface $generator */
            $generator = $app['url_generator'];
            return new RedirectResponse($generator->generate('dashboard'), Response::HTTP_SEE_OTHER);
        }

        if ($config['key'] === $request->get('key')) {
            $this->key = true;
        }

        return null;
    }

    public function getLocations()
    {
        $app = $this->getContainer();

        $locations = array(
                $app['resources']->getPath('cachepath'),
                $app['resources']->getPath('configpath'),
                $app['resources']->getPath('extensionspath'),
                $app['resources']->getPath('filespath'),
                $app['resources']->getPath('databasepath'),
                $app['resources']->getPath('themepath'),
            );

        return $locations;
    }

    public function index()
    {
        $msg = "Welcome to the <strong>Chmødïna✝oR!!1</strong>! If you're having
        issues with your Bolt files not being removable using your FTP client or vice-
        versa, this extension will help you sort it out by applying 'chmod' to it.
        Clicking the 'Fix' button will try to make all files in the data folders
        writable to 'all', and it will inform you of any files it couldn't modify, so
        you can change them using the command line or your (S)FTP client.<br><br>

        You should be aware that using this tool is not considered 'good practice'. If
        possible, you should work with your system administrator to get things set up
        properly. If that's not an option, or if you're on shared hosting, this
        extension will help you out!";

        $data = array('msg' => $msg);

        return $this->renderTemplate('chmodinator.twig', $data);
    }

    public function check()
    {
        $app = $this->getContainer();

        // Clear the cache beforehand, ar this will make sure we have a lot less files to deal with.
        $result = $app['cache']->flushAll();

        $files = '';
        $finder = $this->getFinder(true);

        foreach ($finder as $file) {
            $res = $this->getPrintInfoFile($file);
            $files .= $res;
        }

        $msg = "Below you'll see the output of the checks. Lines marked with a
        red '<i class='fa fa-close red'></i>' are not writable, and should be
        fixed. For files owned by you, you should use the command-line or your
        (S)FTP client to make sure these files are set correctly.";

        $data = array('files' => $files, 'msg' => $msg);

        return $this->renderTemplate('chmodinator.twig', $data);
    }

    public function fixAll()
    {
        $app = $this->getContainer();

        // Clear the cache beforehand, as this will make sure we have a lot less files to deal with.
        $app['cache']->flushAll();

        $files = '';
        $finder = $this->getFinder(true);

        foreach ($finder as $file) {

            try {
                $perms = substr(sprintf('%o', @$file->getPerms()), -3);
            } catch (\Exception $e) {
                $perms = '000';
            }

            if ($file->isDir() && $perms != '777') {
                @chmod($file, 0777);
                if (!$file->isWritable()) {
                    $res = $this->getPrintInfoFile($file);
                    $files .= $res;
                }
            } else if (!$file->isDir() && !$this->checkFilePerms($perms)) {
                @chmod($file, 0666);
                $res = $this->getPrintInfoFile($file);
                $files .= $res;
            }


        }

        if ($this->key) {
            $result = "1";
        } else {
            $msg = "Below you'll see the output of the changes. If there are lines
                left with a red '<i class='fa fa-close red'></i>', then these files /
                folders could not be modified by Bolt or the Chmodinator. You should use
                the command-line or your (S)FTP client to make sure these files are set
                correctly.";
            $data = array('files' => $files, 'msg' => $msg);
            $result = $this->renderTemplate('chmodinator.twig', $data);
        }

        return $result;
    }


    /**
     * @return string
     */
    public function wipeCache()
    {
        $app = $this->getContainer();

        // Clear the cache beforehand, as this will make sure we have a lot less files to deal with.
        $app['cache']->flushAll();

        $files = '';
        $finder = $this->getFinder(true);

        foreach ($finder->files() as $file) {
            @unlink($file);
            $res = $this->getPrintInfoFile($file);
            $files .= $res;
        }

        foreach ($finder->directories() as $folder) {
            @unlink($folder);
            $res = $this->getPrintInfoFile($folder);
            $files .= $res;
        }

        if ($this->key) {
            $result = "1";
        } else {
            $msg = "Below you'll see the output of the changes. If there are lines
                left with a red '<i class='fa fa-close red'></i>', then these files /
                folders could not be modified by Bolt or the Chmodinator. You should use
                the command-line or your (S)FTP client to make sure these files are set
                correctly.";
            $data = array('files' => $files, 'msg' => $msg);
            $result = $this->renderTemplate('chmodinator.twig', $data);
        }

        return $result;
    }

    /**
     * @param SplFileInfo $file
     *
     * @return string
     */
    public function getPrintInfoFile(SplFileInfo $file)
    {
        $app = $this->getContainer();

        $basepath = $app['resources']->getPath('root');
        $path = str_replace($basepath, '…', $file->getPathname());
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

    protected function getDefaultConfig()
    {
        return [
            'key' => ''
        ];
    }

    /**
     * @param bool $cacheOnly
     *
     * @return Finder
     */
    private function getFinder($cacheOnly = false)
    {
        $app = $this->getContainer();

        $finder = new Finder();
        $finder->ignoreDotFiles(false);
        $finder->depth('<6');
        $finder->exclude(['node_modules', 'bower_components', '.sass-cache']);

        if ($cacheOnly) {
            $finder->in($app['resources']->getPath('cachepath'));
        } else {
            foreach($this->getLocations() as $loc) {
                $finder->in($loc);
            }
        }

        return $finder;
    }
}
