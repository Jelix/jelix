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

class jDb_sqlite3Test extends jUnitTestCase {

    public static function setUpBeforeClass() {
        self::initJelixConfig();
    }

    function setUp (){
        jProfiles::clear();
        try {
            jProfiles::get('jdb', 'testapp_sqlite3', true);
        }
        catch(Exception $e) {
            $this->markTestSkipped(get_class($this).' cannot be run: undefined testapp_sqlite3 profile');
            return;
        }

        if (!class_exists('SQLite3')) {
            $this->markTestSkipped(get_class($this).' cannot be run: sqlite3 extension is not installed');
        }
        parent::setUp();
    }

    function testTools(){
        $tools = jDb::getConnection('testapp_sqlite3')->tools();
        $fields = $tools->getFieldList('products');
        $structure = '<array>
    <object key="id" class="jDbFieldProperties">
        <string property="type" value="integer" />
        <string property="name" value="id" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="true" />
        <boolean property="autoIncrement" value="true" />
        <boolean property="hasDefault" value="false" />
        <string property="default" value="" />
        <integer property="length" value="0" />
    </object>
    <object key="name" class="jDbFieldProperties">
        <string property="type" value="varchar" />
        <string property="name" value="name" />
        <boolean property="notNull" value="true" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="false" />
        <string property="default" value="" />
        <integer property="length" value="150" />
    </object>
    <object key="price" class="jDbFieldProperties">
        <string property="type" value="float" />
        <string property="name" value="price" />
        <boolean property="notNull" value="false" />
        <boolean property="primary" value="false" />
        <boolean property="autoIncrement" value="false" />
        <boolean property="hasDefault" value="true" />
        <string property="default" value="0" />
        <integer property="length" value="0" />
    </object>
</array>';
        $this->assertComplexIdenticalStr($fields, $structure, 'bad results');
    }

    function testSelectRowCount(){
        $db = jDb::getConnection('testapp_sqlite3');
        
        $db->exec("DELETE FROM products");
        $db->exec("INSERT INTO products (id, name, price) VALUES(1,'bateau', 1.23)");
        $db->exec("INSERT INTO products (id, name, price) VALUES(2,'vélo', 2.34)");
        $db->exec("INSERT INTO products (id, name, price) VALUES(3,'auto', 3.45)");

        $res = $db->query("SELECT count(*) as cnt FROM products");
        $rec = $res->fetch();
        $this->assertNotEquals(false, $rec);
        $this->assertEquals(3, $rec->cnt);
        unset($rec);
        unset($res);

        $res = $db->query("SELECT id, name, price FROM products");
        $all = $res->fetchAll();
        $this->assertEquals(3, count($all));
        unset($res);
        
        $res = $db->query("SELECT id, name, price FROM products");
        $first = $res->fetch();
        $this->assertNotEquals(false, $first);
        $second = $res->fetch();
        $this->assertNotEquals(false, $second);
        $third = $res->fetch();
        $this->assertNotEquals(false, $third);
        $last = $res->fetch();
        $this->assertFalse($last);
        $last = $res->fetch(); // the sqlite driver of jelix doesn't rewind after reaching the end, contrary to the sqlite3 api of php
        $this->assertFalse($last);

        $this->assertEquals(3, $res->rowCount());
        unset($res);

        $res = $db->query("SELECT id, name, price FROM products");
        $first = $res->fetch();
        $this->assertNotEquals(false, $first);
        $this->assertEquals(3, $res->rowCount());
        $all = $res->fetchAll();
        $this->assertEquals(2, count($all));
        unset($res);
    }

    /**

     */
    function testPreparedQueries(){
        $cnx = jDb::getConnection('testapp_sqlite3');
        $cnx->exec("DELETE FROM products");

        $stmt = $cnx->prepare('INSERT INTO products (id, name, price) VALUES(:i, :na, :pr)');

        $stmt->bindValue('i',1, PDO::PARAM_INT);
        $stmt->bindValue('na', 'assiettes');
        $stmt->bindValue('pr',3.87);
        $stmt->execute();

        $name = 'fourchettes';
        $price = 1.54;
        $stmt->bindValue('i',2, PDO::PARAM_INT);
        $stmt->bindParam('na', $name);
        $stmt->bindParam('pr',$price);
        $stmt->execute();

        $stmt->execute(array('i'=>3, 'na'=>'verres', 'pr'=>2.43));

        $res = $cnx->query("SELECT count(*) as cnt FROM products");
        $rec = $res->fetch();
        $this->assertNotEquals(false, $rec);
        $this->assertEquals(3, $rec->cnt);
        unset($rec);
        unset($res);

        $res = $cnx->query("SELECT id, name, price FROM products ORDER by id asc");
        $all = $res->fetchAll();
        $this->assertEquals(3, count($all));
        unset($res);
        $this->assertEquals($all[0]->id, 1);
        $this->assertEquals($all[0]->name, 'assiettes');
        $this->assertEquals($all[0]->price, 3.87);
        $this->assertEquals($all[1]->id, 2);
        $this->assertEquals($all[1]->name, 'fourchettes');
        $this->assertEquals($all[1]->price, 1.54);
        $this->assertEquals($all[2]->id, 3);
        $this->assertEquals($all[2]->name, 'verres');
        $this->assertEquals($all[2]->price, 2.43);
    }

}
