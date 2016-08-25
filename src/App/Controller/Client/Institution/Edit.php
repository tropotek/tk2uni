<?php
namespace App\Controller\Client\Institution;

use Dom\Template;
use Tk\Form;
use Tk\Form\Event;
use Tk\Form\Field;
use Tk\Request;
use \App\Controller\Iface;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Edit extends Iface
{

    /**
     * @var Form
     */
    protected $form = null;

    /**
     * @var \App\Db\Institution
     */
    private $institution = null;

    /**
     * @var \App\Db\user
     */
    private $owner = null;

    /**
     * @var \Tk\Table
     */
    protected $table = null;


    /**
     *
     */
    public function __construct()
    {
        parent::__construct('Institution Edit');
    }

    /**
     *
     * @param Request $request
     * @return \App\Page\Iface|Template|string
     */
    public function doDefault(Request $request)
    {
        $this->institution = \App\Db\InstitutionMap::create()->findByOwnerId($this->getUser()->id);
        $this->owner = $this->institution->getOwner();

        $this->form = new Form('formEdit');

        $this->form->addField(new Field\Input('name'))->setRequired(true)->setTabGroup('Details');
        $this->form->addField(new Field\Input('username'))->setRequired(true)->setTabGroup('Details');
        $this->form->addField(new Field\Input('email'))->setRequired(true)->setTabGroup('Details');
        $this->form->addField(new Field\File('logo', $request, $this->getConfig()->getDataPath()))->setAttr('accept', '.png,.jpg,.jpeg,.gif')->setTabGroup('Details');
        $insUrl = \Tk\Uri::create('/inst/'.$this->institution->getHash().'/login.html')->toString();
        if ($this->institution->domain)
            $insUrl = \Tk\Uri::create('http://'.$this->institution->domain.'/login.html')->toString();
        $this->form->addField(new Field\Input('domain'))->setTabGroup('Details')->setNotes('Your Institution login URL is: <a href="'.$insUrl.'">'.$insUrl.'</a>' );
        $this->form->addField(new Field\Textarea('description'))->setTabGroup('Details');
        $this->form->addField(new Field\Checkbox('active'))->setTabGroup('Details');

        $this->form->setAttr('autocomplete', 'off');
        $f = $this->form->addField(new Field\Password('newPassword'))->setAttr('placeholder', 'Click to edit')->setAttr('readonly', 'true')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")->setTabGroup('Password');
        if (!$this->owner->getId())
            $f->setRequired(true);
        $f = $this->form->addField(new Field\Password('confPassword'))->setAttr('placeholder', 'Click to edit')->setAttr('readonly', 'true')->setAttr('onfocus', "this.removeAttribute('readonly');this.removeAttribute('placeholder');")->setNotes('Change this users password.')->setTabGroup('Password');
        if (!$this->owner->getId())
            $f->setRequired(true);

        // TODO: Implement LTI tables for LMS access
        $this->form->addField(new Field\Checkbox(\App\Db\Institution::LTI_ENABLE))->setTabGroup('LTI')->setNotes('Enable the LTI V1 launch URL for LMS systems.');
        $lurl = \Tk\Uri::create('/lti/'.$this->institution->getHash().'/launch.html')->toString();
        if ($this->institution->domain)
            $lurl = \Tk\Uri::create('http://'.$this->institution->domain.'/lti/launch.html')->toString();
        $this->form->addField(new Field\Html(\App\Db\Institution::LTI_URL, $lurl))->setLabel('Launch Url')->setTabGroup('LTI');
        $this->institution->getData()->set(\App\Db\Institution::LTI_URL, $lurl);
        $this->form->addField(new Field\Input(\App\Db\Institution::LTI_KEY))->setTabGroup('LTI');
        $this->form->addField(new Field\Input(\App\Db\Institution::LTI_SECRET))->setTabGroup('LTI');

        $this->form->addField(new Field\Checkbox(\App\Db\Institution::LDAP_ENABLE))->setTabGroup('LDAP')->setNotes('Enable LDAP authentication for the institution staff and student login.');
        $this->form->addField(new Field\Input(\App\Db\Institution::LDAP_HOST))->setTabGroup('LDAP');
        $this->form->addField(new Field\Checkbox(\App\Db\Institution::LDAP_TLS))->setTabGroup('LDAP');
        $this->form->addField(new Field\Input(\App\Db\Institution::LDAP_PORT))->setTabGroup('LDAP');
        $this->form->addField(new Field\Input(\App\Db\Institution::LDAP_BASE_DN))->setTabGroup('LDAP');
        $this->form->addField(new Field\Input(\App\Db\Institution::LDAP_FILTER))->setTabGroup('LDAP')->setNotes('`{username}` will be replaced with the login request username.');

//        $this->form->addField(new Field\Checkbox(\App\Db\Institution::API_ENABLE))->setTabGroup('API')->setNotes('Enable the system API key for this Institution.');
//        $this->form->addField(new Field\Input(\App\Db\Institution::API_KEY))->setTabGroup('API');

        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $this->form->addField(new Event\Link('cancel', \Tk\Uri::create('/client/index.html')));

        $this->form->load(\App\Db\InstitutionMap::create()->unmapForm($this->institution));
        $this->form->load(\App\Db\UserMap::create()->unmapForm($this->owner));
        $this->form->load($this->institution->getData()->all());

        $this->form->execute();

        return $this->show();
    }

    /**
     * @param \Tk\Form $form
     */
    public function doSubmit($form)
    {
        // Load the object with data from the form using a helper object
        \App\Db\InstitutionMap::create()->mapForm($form->getValues(), $this->institution);
        \App\Db\UserMap::create()->mapForm($form->getValues(), $this->owner);
        $data = $this->institution->getData();
        $data->replace($form->getValues('/^(ldap|lti|api)/'));

        $form->addFieldErrors($this->institution->validate());
        $form->addFieldErrors($this->owner->validate());

        // Password validation needs to be here
        if ($this->form->getFieldValue('newPassword')) {
            if ($this->form->getFieldValue('newPassword') != $this->form->getFieldValue('confPassword')) {
                $form->addFieldError('newPassword', 'Passwords do not match.');
                $form->addFieldError('confPassword');
            }
        }
        if (!$this->owner->id && !$this->form->getFieldValue('newPassword')) {
            $form->addFieldError('newPassword', 'Please enter a new password.');
        }

        // validate LTI consumer key
        $lid = (int)$data->get(\App\Db\Institution::LTI_CURRENT_ID);
        if ($form->getFieldValue(\App\Db\Institution::LTI_ENABLE)) {
            if (!$form->getFieldValue(\App\Db\Institution::LTI_KEY)) {
                $form->addFieldError(\App\Db\Institution::LTI_KEY, 'Please enter a valid LTI Key');
            }
            if (!$form->getFieldValue(\App\Db\Institution::LTI_SECRET) && $lid > 0) {
                $form->addFieldError(\App\Db\Institution::LTI_SECRET, 'Please enter a valid LTI secret code');
            }
            if (\App\Db\InstitutionMap::create()->ltiKeyExists($form->getFieldValue(\App\Db\Institution::LTI_KEY), $lid)) {
                $form->addFieldError(\App\Db\Institution::LTI_KEY, 'This LTI key already exists for another Institution.');
            }
        }

        $form->getField('logo')->isValid();

        if ($form->hasErrors()) {
            return;
        }

        $rel = '/institution/logo/' . $this->institution->getVolatileId() . '/' . $form->getField('logo')->getUploadedFile()->getFilename();
        $form->getField('logo')->moveTo($rel);
        // Get the relative file path from the field
        $this->institution->logo = $form->getField('logo')->getValue();

        // Hash the password correctly
        if ($this->form->getFieldValue('newPassword')) {
            $this->owner->password = \App\Factory::hashPassword($this->form->getFieldValue('newPassword'), $this->owner);
        }

        $this->owner->save();
        $this->institution->save();

        $data->save();
        $this->institution->save();

        \App\Alert::addSuccess('Record saved!');
        if ($form->getTriggeredEvent()->getName() == 'update')
            \Tk\Uri::create('/client/index.html')->redirect();
        \Tk\Uri::create()->redirect();
    }

    /**
     * @return \App\Page\Iface
     */
    public function show()
    {
        $template = $this->getTemplate();

//        if ($this->institution->id) {
//            $courseTable = new \App\Ui\CourseTable($this->institution->id);
//            $template->insertTemplate('courseTable', $courseTable->show());
//
//            $staffTable = new \App\Ui\UserTable($this->institution->id, \App\Auth\Acl::ROLE_STAFF);
//            $template->insertTemplate('staffTable', $staffTable->show());
//
//            $studentTable = new \App\Ui\UserTable($this->institution->id, \App\Auth\Acl::ROLE_STUDENT);
//            $template->insertTemplate('studentTable', $studentTable->show());
//
//            $template->addClass('editPanel', 'col-md-4');
//            $template->setChoice('showInfo');
//        } else {
            $template->addClass('editPanel', 'col-md-12');
//        }

        // Render the form
        $fren = new \Tk\Form\Renderer\Dom($this->form);
        $template->insertTemplate($this->form->getId(), $fren->show()->getTemplate());
        
        return $this->getPage()->setPageContent($template);
    }

    /**
     * DomTemplate magic method
     *
     * @return Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<div class="row">
  <div class="" var="editPanel">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-university fa-fw"></i> Institution
      </div>
      <div class="panel-body ">
        <div class="row">
          <div class="col-lg-12">
            <div var="formEdit"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-lg-8" choice="showInfo">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-university fa-fw"></i> Institution
      </div>
      <div class="panel-body ">
        
          <!-- Nav tabs -->
          <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#courses" aria-controls="courses" role="tab" data-toggle="tab">Courses</a></li>
            <li role="presentation"><a href="#staff" aria-controls="staff" role="tab" data-toggle="tab">Staff</a></li>
            <li role="presentation"><a href="#students" aria-controls="students" role="tab" data-toggle="tab">Students</a></li>
          </ul>
        
          <!-- Tab panes -->
          <div class="tab-content">
            <div role="tabpanel" class="tab-pane active" id="courses">
              <div var="courseTable">Courses ...</div>
            </div>
            <div role="tabpanel" class="tab-pane" id="staff">
              <div var="staffTable">Staff ...</div>
            </div>
            <div role="tabpanel" class="tab-pane" id="students">
              <div var="studentTable">Students ...</div>
            </div>
          </div>

      </div>
    </div>
  </div>
</div>
XHTML;

        return \Dom\Loader::load($xhtml);
    }

}