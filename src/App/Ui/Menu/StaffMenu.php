<?php
namespace App\Ui\Menu;


/**
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2016 Michael Mifsud
 */
class StaffMenu extends Iface
{

    /**
     * @return \Dom\Template
     * @throws \Exception
     */
    public function show()
    {
        $template = parent::show();

        if($this->getConfig()->isSubjectUrl()) {
            $subject = $this->getConfig()->getSubject();
            $template->setAttr('subject-dashboard', 'href', \Uni\Uri::createSubjectUrl('/index.html', $subject));
            $template->setAttr('subject-settings', 'href', \Uni\Uri::createSubjectUrl('/subjectEdit.html', $subject));
            $template->setAttr('subject-students', 'href', \Uni\Uri::createSubjectUrl('/studentManager.html', $subject));
            $template->setText('subject-name', $subject->code);
            $template->setChoice('subject');
        } else {
            $permissions = $this->getUser()->getRole()->getPermissions();
            foreach ($permissions as $permission) {
                $template->setChoice($permission);
            }
            $template->setChoice('no-subject');
        }

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<XHTML
<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">

    <div class="navbar-header">
      <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/staff/index.html" var="site-title">Staff</a>
    </div>
    <!-- /.navbar-header -->

    <ul class="nav navbar-top-links navbar-right">
      <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
          <i class="fa fa-user fa-fw"></i> <span var="username">Admin</span> <i class="fa fa-caret-down"></i>
        </a>
        <ul class="dropdown-menu dropdown-user">
          <li><a href="/staff/profile.html"><i class="fa fa-user fa-fw"></i> My Profile</a></li>
          <li class="divider"></li>
          <li><a href="/logout.html"><i class="fa fa-sign-out fa-fw"></i> Logout</a></li>
        </ul>
      </li>
    </ul>

    <div class="navbar-default sidebar" role="navigation">
      <div class="sidebar-nav navbar-collapse">
        <ul class="nav" id="side-menu">
          <li><a href="/staff/index.html"><i class="fa fa-fw fa-dashboard"></i> Dashboard</a></li>
          <li choice="perm.manage.subject"><a href="/staff/subjectEdit.html"><i class="fa fa-fw fa-graduation-cap"></i> Create Subject</a></li>
          <li choice="perm.manage.roles"><a href="/staff/roleManager.html"><i class="fa fa-fw fa-id-card"></i> Roles</a></li>
          <li choice="perm.manage.staff"><a href="/staff/staffManager.html"><i class="fa fa-fw fa-user-md"></i> Staff</a></li>

          <li choice="subject"><a href="#"><i class="fa fa-cogs fa-fw"></i> <span var="subject-name">Subject</span> <span class="fa arrow"></span></a>
            <ul class="nav nav-second-level" var="subject-menu">
              <li><a href="/index.html" var="subject-dashboard"><i class="fa fa-fw fa-dashboard"></i> Subject Dashboard</a></li>
              <li><a href="/subjectEdit.html" var="subject-settings"><i class="fa fa-cog fa-fw"></i> Settings</a></li>
              <!--<li><a href="/studentManager.html" var="subject-students"><i class="fa fa-users fa-fw"></i> Students</a></li>-->
            </ul>
          </li>
          
        </ul>
      </div>
    </div>
    
</nav>
XHTML;

        return \Dom\Loader::load($xhtml);
    }

}