<?php
namespace App\Controller\Ui\Course;

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
     * @var \App\Db\Course
     */
    private $course = null;

    /**
     * @var \App\Db\Institution
     */
    private $institution = null;

    /**
     * @var \Tk\Table
     */
    protected $table = null;


    /**
     *
     */
    public function __construct()
    {
        parent::__construct('Course Edit');
    }

    /**
     *
     * @param Request $request
     * @return \App\Page\Iface|Template|string
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        $this->institution = $this->getUser()->getInstitution();

        $this->course = new \App\Db\Course();
        $this->course->institutionId = $this->institution->id;
        if ($request->get('courseId')) {
            $this->course = \App\Db\CourseMap::create()->find($request->get('courseId'));
            if ($this->institution->id != $this->course->institutionId) {
                throw new \Tk\Exception('You do not have permission to edit this course.');
            }
        }

        $this->form = new Form('formEdit');

        $this->form->addField(new Field\Input('name'))->setRequired(true);
        $this->form->addField(new Field\Input('code'))->setRequired(true);
        $this->form->addField(new Field\Input('email'))->setRequired(true);
        $this->form->addField(new Field\Input('start'))->addCss('date')->setRequired(true);
        $this->form->addField(new Field\Input('finish'))->addCss('date')->setRequired(true);
        $this->form->addField(new Field\Textarea('description'));

        $this->form->addField(new Event\Button('update', array($this, 'doSubmit')));
        $this->form->addField(new Event\Button('save', array($this, 'doSubmit')));
        $url = \App\Uri::createHomeUrl('/courseManager.html');
        $this->form->addField(new Event\Link('cancel', $url));

        $this->form->load(\App\Db\CourseMap::create()->unmapForm($this->course));
        $this->form->execute();


        if ($this->course->id) {

            $this->table = new \Tk\Table('tableOne');

            $this->table->addCell(new \Tk\Table\Cell\Checkbox('id'));
            $this->table->addCell(new \Tk\Table\Cell\Text('name'))->addCellCss('key');
            //$this->table->addCell(new \Tk\Table\Cell\Text('username'));
            $this->table->addCell(new \Tk\Table\Cell\Text('email'));
            $this->table->addCell(new \Tk\Table\Cell\Text('role'));
            $this->table->addCell(new \Tk\Table\Cell\Text('uid'))->setLabel('UID');
            $this->table->addCell(new \Tk\Table\Cell\Boolean('active'));
            $this->table->addCell(new \Tk\Table\Cell\Date('lastLogin'));

            // Filters
            $this->table->addFilter(new Field\Input('keywords'))->setLabel('')->setAttr('placeholder', 'Keywords');

            $list = array('-- Role --' => '', 'Staff' => \App\Auth\Acl::ROLE_STAFF, 'Student' => \App\Auth\Acl::ROLE_STUDENT);
            $this->table->addFilter(new Field\Select('role', $list))->setLabel('');

            // Actions
            $this->table->addAction(\Tk\Table\Action\Csv::getInstance($this->getConfig()->getDb()));

            $filter = $this->table->getFilterValues();
            $filter['institutionId'] = $this->getUser()->getInstitution()->id;
            if (empty($filter['role']))
                $filter['role'] = array(\App\Auth\Acl::ROLE_STAFF, \App\Auth\Acl::ROLE_STUDENT);

            $users = \App\Db\UserMap::create()->findFiltered($filter, $this->table->makeDbTool('a.name'));
            $this->table->setList($users);

        }

        return $this->show();
    }

    /**
     * @param \Tk\Form $form
     */
    public function doSubmit($form)
    {
        // Load the object with data from the form using a helper object
        \App\Db\CourseMap::create()->mapForm($form->getValues(), $this->course);

        $form->addFieldErrors(\App\Db\CourseValidator::create($this->course)->getErrors());

        if ($form->hasErrors()) {
            return;
        }

        $this->course->save();

        \App\Alert::addSuccess('Record saved!');

        if ($form->getTriggeredEvent()->getName() == 'update') {
            \App\Uri::createHomeUrl('/courseManager.html')->redirect();
        }

        \Tk\Uri::create()->set('courseId', $this->course->id)->redirect();
    }

    /**
     * @return \App\Page\Iface
     */
    public function show()
    {
        $template = $this->getTemplate();

        // Render the form
        $fren = new \Tk\Form\Renderer\Dom($this->form);
        $template->insertTemplate($this->form->getId(), $fren->show()->getTemplate());

//        $template->setAttr('userManager', 'href', \App\Uri::createHomeUrl('/userManager.html')->set('courseId', $this->course->id));
//        $template->setAttr('enrollmentList', 'href', \App\Uri::createHomeUrl('/enrollment.html')->set('courseId', $this->course->id));

        if ($this->course->id == 0) {
            $template->setChoice('new');
            $template->addClass('cols', 'col-md-12');
        } else {
            $template->setChoice('edit');
            $template->addClass('cols', 'col-md-4');

            $ren = \Tk\Table\Renderer\Dom\Table::create($this->table);
            $ren->show();
            $template->replaceTemplate('table', $ren->getTemplate());

        }

        if ($this->course->id) {
            $template->setChoice('update');
            $template->setAttr('enroll', 'href', \App\Uri::createHomeUrl('/courseEnrollment.html')->set('courseId', $this->course->id));
        } else {
            $template->setChoice('new');
        }

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

  <div class="col-sm-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-cogs"></i> Actions
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
            <a href="javascript: window.history.back();" class="btn btn-default"><i class="fa fa-arrow-left"></i> <span>Back</span></a>
            <a href="javascript:;" class="btn btn-default" var="enroll" choice="update"><i class="fa fa-list"></i> <span>Enrollment List</span></a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="" var="cols">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-graduation-cap"></i> Course Edit
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
            <div var="formEdit"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-8" choice="edit">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-graduation-cap"></i> Course Enrollments
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-lg-12">
            <div var="table">test</div>
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