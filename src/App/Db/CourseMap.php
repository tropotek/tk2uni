<?php
namespace App\Db;

use Tk\Db\Map\Model;
use Tk\Db\Tool;
use Tk\Db\Map\ArrayObject;
use Tk\DataMap\Db;
use Tk\DataMap\Form;

/**
 * Class CourseMap
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class CourseMap extends Mapper
{

    /**
     *
     * @return \Tk\DataMap\DataMap
     */
    public function getDbMap()
    {
        if (!$this->dbMap) {
            $this->dbMap = new \Tk\DataMap\DataMap();
            $this->dbMap->addProperty(new Db\Number('id'), 'key');
            $this->dbMap->addProperty(new Db\Number('institutionId', 'institution_id'));
            $this->dbMap->addProperty(new Db\Text('name'));
            $this->dbMap->addProperty(new Db\Text('code'));
            $this->dbMap->addProperty(new Db\Text('email'));
            $this->dbMap->addProperty(new Db\Text('description'));

            $this->dbMap->addProperty(new Db\Date('start'));
            $this->dbMap->addProperty(new Db\Date('finish'));
            $this->dbMap->addProperty(new Db\Date('modified'));
            $this->dbMap->addProperty(new Db\Date('created'));

            $this->setPrimaryKey($this->dbMap->currentProperty('key')->getColumnName());
        }
        return $this->dbMap;
    }

    /**
     *
     * @return \Tk\DataMap\DataMap
     */
    public function getFormMap()
    {
        if (!$this->formMap) {
            $this->formMap = new \Tk\DataMap\DataMap();
            $this->formMap->addProperty(new Form\Number('id'), 'key');
            $this->formMap->addProperty(new Form\Number('institutionId'));
            $this->formMap->addProperty(new Form\Text('name'));
            $this->formMap->addProperty(new Form\Text('code'));
            $this->formMap->addProperty(new Form\Text('email'));
            $this->formMap->addProperty(new Form\Text('description'));
            $this->formMap->addProperty(new Form\Date('start'));
            $this->formMap->addProperty(new Form\Date('finish'));

            $this->setPrimaryKey($this->formMap->currentProperty('key')->getColumnName());
        }
        return $this->formMap;
    }




    /**
     *
     * @param string $code
     * @param int $institutionId
     * @return Model
     */
    public function findByCode($code, $institutionId)
    {
        $where = sprintf('code = %s AND  institution_id = %d', $this->getDb()->quote($code), (int)$institutionId);
        return $this->select($where)->current();
    }

    /**
     *
     * @param int $userId
     * @param int $institutionId
     * @param Tool $tool
     * @return ArrayObject
     */
    public function findByUserId($userId, $institutionId = 0, $tool = null)
    {
        $from = sprintf('%s a, user_course b', $this->getDb()->quoteParameter($this->getTable()));
        $where = sprintf('a.id = b.course_id AND b.user_id = %d', (int)$userId);
        if ($institutionId) {
            $where .= sprintf(' AND a.institution_id = %d', (int)$institutionId);
        }
        return $this->selectFrom($from, $where, $tool);
    }

    /**
     * Find filtered records
     *
     * @param array $filter
     * @param Tool $tool
     * @return ArrayObject
     */
    public function findFiltered($filter = array(), $tool = null)
    {
        $from = sprintf('%s a ', $this->getDb()->quoteParameter($this->getTable()));
        $where = '';

        if (!empty($filter['keywords'])) {
            $kw = '%' . $this->getDb()->escapeString($filter['keywords']) . '%';
            $w = '';
            $w .= sprintf('a.name LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.code LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.email LIKE %s OR ', $this->getDb()->quote($kw));
            $w .= sprintf('a.description LIKE %s OR ', $this->getDb()->quote($kw));
            if (is_numeric($filter['keywords'])) {
                $id = (int)$filter['keywords'];
                $w .= sprintf('a.id = %d OR ', $id);
            }
            if ($w) {
                $where .= '(' . substr($w, 0, -3) . ') AND ';
            }
        }

        if (!empty($filter['code'])) {
            $where .= sprintf('a.code = %s AND ', $this->getDb()->quote($filter['code']));
        }

        if (!empty($filter['email'])) {
            $where .= sprintf('a.email = %s AND ', $this->getDb()->quote($filter['email']));
        }

        if (!empty($filter['institutionId'])) {
            $where .= sprintf('a.institution_id = %s AND ', (int)$filter['institutionId']);
        }

        if ($where) {
            $where = substr($where, 0, -4);
        }

        $res = $this->selectFrom($from, $where, $tool);
        return $res;
    }

    

    /**
     * @param $courseId
     * @param $userId
     * @return boolean
     */
    public function hasUser($courseId, $userId)
    {
        $sql = sprintf('SELECT * FROM user_course WHERE course_id = %d AND user_id = %d', (int)$courseId, (int)$userId);
        return ($this->getDb()->query($sql)->rowCount() > 0);
    }

    /**
     * @param $courseId
     * @param $userId
     * @return \Tk\Db\PDOStatement
     */
    public function deleteUser($courseId, $userId)
    {
        $query = sprintf('DELETE FROM user_course WHERE user_id = %d AND course_id = %d', (int)$userId, (int)$courseId);
        return $this->getDb()->exec($query);
    }

    /**
     * @param $courseId
     * @param $userId
     * @return \Tk\Db\PDOStatement
     */
    public function addUser($courseId, $userId)
    {
        $query = sprintf('INSERT INTO user_course (user_id, course_id)  VALUES (%d, %d) ', (int)$userId, (int)$courseId);
        return $this->getDb()->exec($query);
    }

    /**
     * @param int $courseId
     * @return \Tk\Db\PDOStatement
     */
    public function deleteAllUsers($courseId)
    {
        $query = sprintf('DELETE FROM user_course WHERE course_id = %d ', (int)$courseId);
        return $this->getDb()->exec($query);
    }
    

}