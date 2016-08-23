<?php
namespace App\Controller\Admin;

use Tk\Request;
use Dom\Template;
use \App\Controller\Iface;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Index extends Iface
{
    
    /**
     *
     */
    public function __construct()
    {
        parent::__construct('Dashboard');
    }
    
    /**
     *
     * @param Request $request
     * @return \App\Page\Iface|Template|string
     */
    public function doDefault(Request $request)
    {

//        $consumer = new \IMSGlobal\LTI\ToolProvider\ToolConsumer('Testing0001' , \App\Factory::getLtiDataConnector());
//        $consumer->name = 'Testing';
//        //$consumer->secret = 'ThisIsASecret!';
//        $consumer->enabled = TRUE;
//        $consumer->setKey('Testing0002');
//        vd($consumer);
//        $consumer->save();

        return $this->show();
    }

    /**
     * @return \App\Page\Iface
     */
    public function show()
    {
        $template = $this->getTemplate();
        return $this->getPage()->setPageContent($template);
    }


    /**
     * DomTemplate magic method
     * @return Template
     */
    public function __makeTemplate()
    {
        $tplFile =  $this->getPage()->getTemplatePath() . '/xtpl/admin/index.xtpl';
        return \Dom\Loader::loadFile($tplFile);
    }


}