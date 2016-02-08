<?php
/**
* @package     testapp
* @subpackage  jelix_tests module
* @author      Laurent Jouanneau
* @contributor
* @copyright   2007-2012 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
*/

/**
 * CAREFULL ! DON'T CHANGE THE ORDER OF METHODS
 */
abstract class jdao_main_api_base extends jUnitTestCaseDb {

    function setUp() {
        self::initJelixConfig();
        jApp::pushCurrentModule('jelix_tests');
        if ($this->getName() == 'testInstanciation')
            $this->emptyTable('product_test');
    }

    function tearDown() {
        jApp::popCurrentModule();
    }

    function testInstanciation() {
        $dao = jDao::create ('products');
        $this->assertInstanceOf('jDaoFactoryBase', $dao);

        $dao = jDao::get ('products');
        $this->assertInstanceOf('jDaoFactoryBase', $dao);

        $dao = jDao::createRecord ('products');
        $this->assertInstanceOf('jDaoRecordBase', $dao);
    }

    function testFindAllEmpty() {
        $dao = jDao::create ('products');
        $res = $dao->findAll();
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(0, count($list), 'findAll doesn\'t return an empty list');
        $this->assertEquals(0, $dao->countAll(), 'countAll doesn\'t return 0');
    }

    protected static $prod1;
    protected static $prod2;
    protected static $prod3;

    /**
     * @depends testFindAllEmpty
     */
    function testInsert() {
        $dao = jDao::create ('products');

        self::$prod1 = jDao::createRecord ('products');
        self::$prod1->name ='assiette';
        self::$prod1->price = 3.87;
        self::$prod1->promo = false;
        $res = $dao->insert(self::$prod1);

        $this->assertEquals(1, $res, 'jDaoBase::insert does not return 1');
        $this->assertNotEquals('', self::$prod1->id, 'jDaoBase::insert : id not set');
        $this->assertNotEquals('', self::$prod1->create_date, 'jDaoBase::insert : create_date not updated');

        self::$prod2 = jDao::createRecord ('products');
        self::$prod2->name ='fourchette';
        self::$prod2->price = 1.54;
        self::$prod2->promo = true;
        self::$prod2->dummy = 'started';
        $res = self::$prod2->save();

        $this->assertEquals(1, $res, 'jDaoBase::insert does not return 1');
        $this->assertNotEquals('', self::$prod2->id, 'jDaoBase::insert : id not set');
        $this->assertNotEquals('', self::$prod2->create_date, 'jDaoBase::insert : create_date not updated');

        self::$prod3 = jDao::createRecord ('products');
        self::$prod3->name ='verre';
        self::$prod3->price = 2.43;
        self::$prod3->promo = false;
        $res = $dao->insert(self::$prod3);

        $this->assertEquals(1, $res, 'jDaoBase::insert does not return 1');
        $this->assertNotEquals('', self::$prod3->id, 'jDaoBase::insert : id not set');
        $this->assertNotEquals('', self::$prod3->create_date, 'jDaoBase::insert : create_date not updated');

        $records = array(
            array('id'=>self::$prod1->id,
            'name'=>'assiette',
            'price'=>3.87,
            'promo'=>0),
            array('id'=>self::$prod2->id,
            'name'=>'fourchette',
            'price'=>1.54,
            'promo'=>1),
            array('id'=>self::$prod3->id,
            'name'=>'verre',
            'price'=>2.43,
            'promo'=>0),
        );
        $this->assertTableContainsRecords('product_test', $records);

    }

    /**
     * @depends testInsert
     */
    function testGet() {
        $dao = jDao::create ('products');

        $prod = $dao->get(self::$prod1->id);
        $this->assertInstanceOf('jDaoRecordBase', $prod,'jDao::get doesn\'t return a jDaoRecordBase object');
        $this->assertEquals(self::$prod1->id, $prod->id, 'jDao::get : bad id on record');
        $this->assertEquals('assiette', $prod->name,'jDao::get : bad name property on record');
        $this->assertEquals(3.87, $prod->price,'jDao::get : bad price property on record');
        $this->assertEquals(0, $prod->promo,'jDao::get : bad promo property on record');
    }

    /**
     * @depends testGet
     */
    function testUpdate(){
        $dao = jDao::create ('products');
        $prod = jDao::createRecord ('products');
        $prod->name ='assiette nouvelle';
        $prod->price = 5.90;
        $prod->promo = true;
        $prod->id = self::$prod1->id;

        $dao->update($prod);

        $prod2 = $dao->get(self::$prod1->id);
        $this->assertInstanceOf('jDaoRecordBase', $prod2,'jDao::get doesn\'t return a jDaoRecordBase object');
        $this->assertEquals(self::$prod1->id, $prod2->id, 'jDao::get : bad id on record');
        $this->assertEquals('assiette nouvelle', $prod2->name,'jDao::get : bad name property on record');
        $this->assertEquals(5.90, $prod2->price,'jDao::get : bad price property on record');
        $this->assertEquals(1, $prod2->promo,'jDao::get : bad promo property on record');
        
        $prod->promo = 't';
        $prod->save();
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(1, $prod2->promo,'jDao::get : bad promo property on record : %');
        
        $prod->promo = 'f';
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(0, $prod2->promo,'jDao::get : bad promo property on record : %');

        $prod->promo = false;
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(0, $prod2->promo,'jDao::get : bad promo property on record : %');

        $prod->promo = 'true';
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(1, $prod2->promo,'jDao::get : bad promo property on record : %');

        $prod->promo = 'on';
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(1, $prod2->promo,'jDao::get : bad promo property on record : %');

        $prod->promo = 'false';
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(0, $prod2->promo,'jDao::get : bad promo property on record : %');

        $prod->promo = 0;
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(0, $prod2->promo,'jDao::get : bad promo property on record : '.var_export($prod2->promo,true).' ');

        $prod->promo = 1;
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(1, $prod2->promo,'jDao::get : bad promo property on record : %');

        $prod->promo = '0';
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(0, $prod2->promo,'jDao::get : bad promo property on record : %');

        $prod->promo = '1';
        $dao->update($prod);
        $prod2 = $dao->get(self::$prod1->id);
        $this->assertEquals(1, $prod2->promo,'jDao::get : bad promo property on record : %');

    }

    /**
     * @depends testUpdate
     */
    function testFindAllNotEmpty() {
        $dao = jDao::create ('products');

        $res = $dao->findAll();
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(3, count($list), 'findAll doesn\'t return all products. %s ');
        $this->assertEquals(3, $dao->countAll(), 'countAll doesn\'t return 3');

    $verif='<array>
    <object>
        <string property="id" value="'.self::$prod1->id.'" />
        <string property="name" value="assiette nouvelle" />
        <string property="price" value="5.90" />
        <string property="promo" value="1" />
    </object>
    <object>
        <string property="id" value="'.self::$prod2->id.'" />
        <string property="name" value="fourchette" />
        <string property="price" value="1.54" />
        <string property="promo" value="1" />
    </object>
    <object>
        <string property="id" value="'.self::$prod3->id.'" />
        <string property="name" value="verre" />
        <string property="price" value="2.43" />
        <string property="promo" value="0" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);
    }

    /**
     * @depends testFindAllNotEmpty
     */
    function testEqualityOnValue() {
        $dao = jDao::create ('products');

        $res = $dao->findFourchette();
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(1, count($list), 'findFourchette doesn\'t return one record. %s ');

    $verif='<array>
    <object>
        <string property="id" value="'.self::$prod2->id.'" />
        <string property="name" value="fourchette" />
        <string property="price" value="1.54" />
        <string property="promo" value="1" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);


        $res = $dao->findStarted();
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(1, count($list), 'findStarted doesn\'t return one record. %s ');

    $verif='<array>
    <object>
        <string property="id" value="'.self::$prod2->id.'" />
        <string property="name" value="fourchette" />
        <string property="price" value="1.54" />
        <string property="promo" value="1" />
        <string property="dummy" value="started" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);
    }

    /**
     * @depends testEqualityOnValue
     */
    function testFindByCountByOrder(){
        $dao = jDao::create ('products');

        $conditions = jDao::createConditions();
        $conditions->addItemOrder('id','DESC');

        $count = $dao->countBy($conditions);
        $this->assertEquals(3, $count, 'countBy: %s');

        $res = $dao->findBy($conditions);
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(3, count($list), 'findBy doesn\'t return all products. %s ');

        $verif='<array>
    <object>
        <string property="id" value="'.self::$prod3->id.'" />
        <string property="name" value="verre" />
        <string property="price" value="2.43" />
    </object>
    <object>
        <string property="id" value="'.self::$prod2->id.'" />
        <string property="name" value="fourchette" />
        <string property="price" value="1.54" />
    </object>
    <object>
        <string property="id" value="'.self::$prod1->id.'" />
        <string property="name" value="assiette nouvelle" />
        <string property="price" value="5.90" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);
    }

    /**
     * @depends testFindByCountByOrder
     */
    function testFindByCountByConditionsOrder(){
        $dao = jDao::create ('products');

        $conditions = jDao::createConditions();
        $conditions->addItemOrder('id','DESC');
        $conditions->addCondition ('id', '>=', self::$prod2->id);

        $count = $dao->countBy($conditions);
        $this->assertEquals(2, $count, 'countBy: %s');

        $res = $dao->findBy($conditions);
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(2, count($list), 'findBy doesn\'t return all products. %s ');

        $verif='<array>
    <object>
        <string property="id" value="'.self::$prod3->id.'" />
        <string property="name" value="verre" />
        <string property="price" value="2.43" />
    </object>
    <object>
        <string property="id" value="'.self::$prod2->id.'" />
        <string property="name" value="fourchette" />
        <string property="price" value="1.54" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);
    }

    /**
     * @depends testFindByCountByConditionsOrder
     */
    function testFindWithIn(){
        $dao = jDao::create ('products');
        $res = $dao->findBySomeNames();
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(1, count($list), 'findBySomeNames doesn\'t return default product. %s ');
        $this->assertEquals($list[0]->id, self::$prod2->id);
        $verif='<array>
    <object>
        <string property="id" value="'.self::$prod2->id.'" />
        <string property="name" value="fourchette" />
        <string property="price" value="1.54" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);

        $res = $dao->findBySomeNames(array('verre', 'assiette nouvelle'));
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }

        $this->assertEquals(2, count($list), 'findBySomeNames doesn\'t return selected products. %s ');
        $verif='<array>
    <object>
        <string property="id" value="'.self::$prod1->id.'" />
        <string property="name" value="assiette nouvelle" />
        <string property="price" value="5.90" />
    </object>
    <object>
        <string property="id" value="'.self::$prod3->id.'" />
        <string property="name" value="verre" />
        <string property="price" value="2.43" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);
    }

    /**
     * @depends testFindWithIn
     */
    function testDelete(){
        $dao = jDao::create ('products');
        $dao->delete(self::$prod1->id);
        $this->assertEquals(2, $dao->countAll(), 'countAll doesn\'t return 2');

        $records = array(
            array('id'=>self::$prod2->id,
            'name'=>'fourchette',
            'price'=>1.54),
            array('id'=>self::$prod3->id,
            'name'=>'verre',
            'price'=>2.43),
        );
        $this->assertTableContainsRecords('product_test', $records);


        $res = $dao->findAll();
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(2, count($list), 'findAll doesn\'t return all products. %s ');

    $verif='<array>
    <object>
        <string property="id" value="'.self::$prod2->id.'" />
        <string property="name" value="fourchette" />
        <string property="price" value="1.54" />
    </object>
    <object>
        <string property="id" value="'.self::$prod3->id.'" />
        <string property="name" value="verre" />
        <string property="price" value="2.43" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);
    }

    /**
     * @depends testDelete
     */
    function testDeleteBy(){
        $dao = jDao::create ('products');

        $conditions = jDao::createConditions();
        $conditions->addCondition ('id', '=', self::$prod2->id);

        $dao->deleteBy($conditions);
        $this->assertEquals(1, $dao->countAll(), 'countAll doesn\'t return 1');

        $records = array(
            array('id'=>self::$prod3->id,
            'name'=>'verre',
            'price'=>2.43),
        );
        $this->assertTableContainsRecords('product_test', $records);

        $res = $dao->findAll();
        $list = array();
        foreach($res as $r){
            $list[] = $r;
        }
        $this->assertEquals(1, count($list), 'findAll doesn\'t return all products. %s ');

    $verif='<array>
    <object>
        <string property="id" value="'.self::$prod3->id.'" />
        <string property="name" value="verre" />
        <string property="price" value="2.43" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($list, $verif);
    }

    function testRecordCheck() {

        $record = jDao::createRecord ('products');
        $this->assertEquals('', $record->id);
        $record->setPk(5);
        $this->assertEquals(5, $record->id);

        $this->assertEquals(5, $record->getPk());
 
        $record = jDao::createRecord ('description');
        $this->assertEquals('', $record->id);
        $this->assertEquals('fr', $record->lang);

        $record->setPk(5,'es');
        $this->assertEquals(5, $record->id);
        $this->assertEquals('es', $record->lang);

        $record->setPk(array(4,'en'));
        $this->assertEquals(4, $record->id);
        $this->assertEquals('en', $record->lang);

        $pk = $record->getPk();
        $this->assertEquals(array(4,'en'), $pk);
    }

    function testErrorCheck() {

        $record = jDao::createRecord('products');
        $check = $record->check();
        $expected = array('name'=>array(jDaoRecordBase::ERROR_REQUIRED));
        $this->assertEquals($expected,$check);

        $record->name = 'Foo';
        $check = $record->check();
        $this->assertFalse($check);

        $record->create_date = 'foo';
        $check = $record->check();
        $expected = array('create_date'=>array(jDaoRecordBase::ERROR_BAD_FORMAT));
        $this->assertEquals($expected,$check);

        $record->create_date = '2008-02-15';
        $check = $record->check();
        $expected = array('create_date'=>array(jDaoRecordBase::ERROR_BAD_FORMAT));
        $this->assertEquals($expected,$check);

        $record->create_date = '2008-02-15 12:03:34';
        $check = $record->check();
        $this->assertFalse($check);

        $record->price='foo';
        $check = $record->check();
        $expected = array('price'=>array(jDaoRecordBase::ERROR_BAD_TYPE));
        $this->assertEquals($expected,$check);

        $record->price=56;
        $check = $record->check();
        $this->assertFalse($check);
    }

    function testBinaryField() {
        $this->emptyTable('jsessions');

        $dao = jDao::create ('jelix~jsession');

        $sess1 = jDao::createRecord ('jelix~jsession');
        $sess1->id ='sess_02939873A32B';
        $sess1->creation = '2010-02-09 10:28';
        $sess1->access = '2010-02-09 11:00';
        $sess1->data = chr(0).chr(254).chr(1);

        $res = $dao->insert($sess1);
        $this->assertEquals(1, $res, 'jDaoBase::insert does not return 1');

        $sess2 = $dao->get('sess_02939873A32B');
        $this->assertEquals($sess1->id, $sess2->id, 'jDao::get : bad id on record');
        $this->assertEquals(bin2hex($sess1->data), bin2hex($sess2->data), 'jDao::get : bad binary value on record');
    }
}
