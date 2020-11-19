<?php
/**
* @author      Laurent Jouanneau
* @copyright   2016 Laurent Jouanneau
* @link        http://jelix.org
* @licence     MIT
*/

use Jelix\Dependencies\Resolver;
use Jelix\Dependencies\Item;

class resolverTest extends PHPUnit_Framework_TestCase {

    /**
     *
     */
    public function testOneItemNoDeps() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_NONE);
        $resolver = new Resolver();
        $resolver->addItem($packA);
        $chain = $resolver->getDependenciesChainForInstallation();
        $this->assertEquals(array(), $chain);

        $packA->setAction(Resolver::ACTION_INSTALL);
        $chain = $resolver->getDependenciesChainForInstallation();
        $this->assertEquals(1, count($chain));
        $this->assertEquals('testA', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
    }

    public function testTwoDependItems() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(2, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testA', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
    }

    public function testUpgrade() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", true);
        $packC->setAction(Resolver::ACTION_UPGRADE, "1.1");

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(3, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testA', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
        $this->assertEquals('testC', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_UPGRADE, $chain[2]->getAction());
    }

    public function testUpgradeWithLowerVersion() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.1", true);
        $packC->setAction(Resolver::ACTION_UPGRADE, "1.0");

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(3, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testA', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
        $this->assertEquals('testC', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_UPGRADE, $chain[2]->getAction());
    }

    /**
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 11
     */
    public function testTwoDependItemsNoForceInstall() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation(false);

    }


    public function testTwoDependItemsForceInstall() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation(true);

        $this->assertEquals(2, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testA', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
    }


    public function testTwoDependItemsForceReinstall() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", true);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation(true);

        $this->assertEquals(1, count($chain));
        $this->assertEquals('testA', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
    }

    /**
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 11
     */
    public function testForbidInstallDependencies() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation(false);
    }


    /**
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 11
     */
    public function testInstallDependenciesThatCannotBeInstalled() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false, false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
    }

    /**
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 1
     */
    public function testCircularDependencies() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '1.0.*');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testC', '1.0.*');
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);
        $packC->addDependency('testA', '1.0.*');

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
    }

    public function testComplexInstallDependencies() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB');
        $packA->addDependency('testC');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);


        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_INSTALL);
        $packD->addDependency('testB');
        $packD->addDependency('testE');
        $packE = new Item('testE', "1.0", false);
        $packE->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $resolver->addItem($packD);
        $resolver->addItem($packE);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(5, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testC', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
        $this->assertEquals('testA', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[2]->getAction());
        $this->assertEquals('testE', $chain[3]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[3]->getAction());
        $this->assertEquals('testD', $chain[4]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[4]->getAction());
    }

    public function testRemoveOneItemNoDeps() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $resolver = new Resolver();
        $resolver->addItem($packA);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(1, count($chain));
        $this->assertEquals('testA', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[0]->getAction());
    }

    public function testRemoveUninstalledItem() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $resolver = new Resolver();
        $resolver->addItem($packA);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(0, count($chain));
    }


    public function testRemoveOneDependItems() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $packA->addDependency('testB', '1.0.*');

        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", true);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(1, count($chain));
        $this->assertEquals('testA', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[0]->getAction());
    }

    public function testRemoveOneAncesterDependItems() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testA', '1.0.*');
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(2, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[0]->getAction());
        $this->assertEquals('testA', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[1]->getAction());
    }

    public function testRemoveOneAncesterAltDependItems() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testA', '1.0.*');
        $packC = new Item('testC', "1.0", true);
        $packC->setAction(Resolver::ACTION_NONE);
        $packC->addAlternativeDependencies(array('testA' => '1.0.*', 'testD' => '1.0.*'));
        $packD = new Item('testD', false, "1.0", Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(3, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[0]->getAction());
        $this->assertEquals('testC', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[1]->getAction());
        $this->assertEquals('testA', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[2]->getAction());
    }

    public function testRemoveOneAncesterAltDependCascadeItems() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testA', '1.0.*');
        $packC = new Item('testC', "1.0", true);
        $packC->setAction(Resolver::ACTION_NONE);
        $packC->addAlternativeDependencies(array('testB' => '1.0.*', 'testD' => '1.0.*'));
        $packD = new Item('testD', false, "1.0", Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(3, count($chain));
        $this->assertEquals('testC', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[0]->getAction());
        $this->assertEquals('testB', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[1]->getAction());
        $this->assertEquals('testA', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[2]->getAction());
    }

    /**
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 5
     */
    public function testRemoveOneAncesterToInstallDependItems() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_INSTALL);
        $packB->addDependency('testA', '1.0.*');
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
    }

    /**
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 4
     */
    public function testRemoveCircularDependencies() {
        $packA = new Item('testA', "1.0", true);
        $packA->setAction(Resolver::ACTION_REMOVE);
        $packA->addDependency('testC', '1.0.*');
        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testA', '1.0.*');
        $packC = new Item('testC', "1.0", true);
        $packC->setAction(Resolver::ACTION_NONE);
        $packC->addDependency('testB', '1.0.*');

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
    }

    public function testInstallRemove() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB');
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", true);
        $packC->setAction(Resolver::ACTION_NONE);
        $packC->addDependency('testD');
        $packD = new Item('testD', "1.0", true);
        $packD->setAction(Resolver::ACTION_REMOVE);
        $packE = new Item('testE', "1.0", true);
        $packE->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packE);
        $resolver->addItem($packA);
        $resolver->addItem($packD);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(4, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testA', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
        $this->assertEquals('testC', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[2]->getAction());
        $this->assertEquals('testD', $chain[3]->getName());
        $this->assertEquals(Resolver::ACTION_REMOVE, $chain[3]->getAction());
    }


    /**
     *
     */
    public function testNoConflictItems() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packA->addIncompatibility('testB', '*');
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
        $this->assertEquals(array($packA), $chain);
    }

    /**
     *
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 8
     * @expectedExceptionMessage Item testB is in conflicts with item testA
     */
    public function testConflictItems() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_INSTALL);
        $packA->addIncompatibility('testB', '*');
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
    }

    /**
     *
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 7
     * @expectedExceptionMessage Item testB is in conflicts with item testA
     */
    public function testConflictItemAlreadyInstalled() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addIncompatibility('testB', '*');
        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
    }


    /**
     *
     */
    public function testNoConflictWithRemovedItem() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addIncompatibility('testB', '*');
        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_REMOVE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $chain = $resolver->getDependenciesChainForInstallation();
        $this->assertEquals(array($packA, $packB), $chain);
    }

    public function testChoiceOneItemInstalled() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addAlternativeDependencies(array(
            'testB'=>'1.0.*',
            'testC'=>'1.0.*',
            )
        );

        $packB = new Item('testB', "1.0", true);
        $packB->setAction(Resolver::ACTION_NONE);
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);
        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_INSTALL);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(2, count($chain));
        $this->assertEquals('testA', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testD', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
    }

    public function testChoiceOneItemToInstall() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addAlternativeDependencies(array(
                'testB'=>'1.0.*',
                'testC'=>'1.0.*',
            )
        );

        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testD');
        //$packC = new Item('testC', "1.0", false);
        //$packC->setAction(Resolver::ACTION_NONE);
        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        //$resolver->addItem($packC);
        $resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(3, count($chain));
        $this->assertEquals('testD', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testB', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
        $this->assertEquals('testA', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[2]->getAction());
    }

    /**
     *
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 10
     * @expectedExceptionMessage Item testA depends on alternative items but there are ambiguities to choose them. Installed one of them before installing it.
     */
    public function testChoiceAmbigusItems() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addAlternativeDependencies(array(
                'testB'=>'1.0.*',
                'testC'=>'1.0.*',
                'testE'=>'1.0.*'
            )
        );

        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testD');
        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);
        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();
    }

    /**
     *
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 9
     * @expectedExceptionMessage Item testA depends on alternative items but there are unknown or do not met installation criterias. Install or upgrade one of them before installing it
     */
    public function testChoiceBadVersionItem() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addAlternativeDependencies(array(
                'testB'=>'1.1.*',
                'testC'=>'1.2.*',
            )
        );

        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testD');
        //$packC = new Item('testC', "1.0", false);
        //$packC->setAction(Resolver::ACTION_NONE);
        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        //$resolver->addItem($packC);
        $resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();
    }


    /**
     *
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 9
     * @expectedExceptionMessage Item testA depends on alternative items but there are unknown or do not met installation criterias. Install or upgrade one of them before installing it
     */
    public function testChoiceUnknownItems() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addAlternativeDependencies(array(
                'testB'=>'1.1.*',
                'testC'=>'1.2.*',
            )
        );

        //$packB = new Item('testB', "1.0", false);
        //$packA->setAction(Resolver::ACTION_NONE);
        //$packB->addDependency('testD');
        //$packC = new Item('testC', "1.0", false);
        //$packC->setAction(Resolver::ACTION_NONE);
        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        //$resolver->addItem($packB);
        //$resolver->addItem($packC);
        $resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();
    }

    /**
     *
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 6
     * @expectedExceptionMessage For item testB, some items are missing: testD
     */
    public function testChoiceItemHasBadDependency() {
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addAlternativeDependencies(array(
                'testB'=>'1.0.*',
                'testC'=>'1.0.*',
            )
        );

        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);
        $packB->addDependency('testD');
        //$packC = new Item('testC', "1.0", false)
        //$packC->setAction(Resolver::ACTION_NONE);
        //$packD = new Item('testD', "1.0", false);
        //$packD->setAction(Resolver::ACTION_NONE);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        //$resolver->addItem($packC);
        //$resolver->addItem($packD);
        $chain = $resolver->getDependenciesChainForInstallation();
    }


    public function testOptionalDependencies() {
        /*
                A->B
                A->C
                D->B
                D->E optional
        */
        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB');
        $packA->addDependency('testC');

        $packB = new Item('testB', "1.0", false);
        $packB->setAction(Resolver::ACTION_NONE);

        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_INSTALL);
        $packD->addDependency('testB');
        $packD->addDependency('testE', '*', true);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packB);
        $resolver->addItem($packC);
        $resolver->addItem($packD);

        $chain = $resolver->getDependenciesChainForInstallation();

        $this->assertEquals(4, count($chain));
        $this->assertEquals('testB', $chain[0]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[0]->getAction());
        $this->assertEquals('testC', $chain[1]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[1]->getAction());
        $this->assertEquals('testA', $chain[2]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[2]->getAction());
        $this->assertEquals('testD', $chain[3]->getName());
        $this->assertEquals(Resolver::ACTION_INSTALL, $chain[3]->getAction());
    }

    /**
     *
     * @expectedException \Jelix\Dependencies\ItemException
     * @expectedExceptionCode 6
     * @expectedExceptionMessage For item testD, some items are missing: testB
     */
    public function testOptionalDependenciesWithMissingDependency() {
        /*
                A->B optional and missing
                A->C
                D->B
                D->E optional
        */

        $packA = new Item('testA', "1.0", false);
        $packA->setAction(Resolver::ACTION_INSTALL);
        $packA->addDependency('testB', '*', true);
        $packA->addDependency('testC');

        $packC = new Item('testC', "1.0", false);
        $packC->setAction(Resolver::ACTION_NONE);

        $packD = new Item('testD', "1.0", false);
        $packD->setAction(Resolver::ACTION_INSTALL);
        $packD->addDependency('testB');
        $packD->addDependency('testE', '*', true);

        $resolver = new Resolver();
        $resolver->addItem($packA);
        $resolver->addItem($packC);
        $resolver->addItem($packD);

        $chain = $resolver->getDependenciesChainForInstallation();
    }
}
