<?php
namespace Shiroyuki\SandboxBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Shiroyuki\SandboxBundle\Entity\Node;

class LoadUserData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $n1 = new Node();
        $n1->setName('node 1');

        $n2 = new Node();
        $n2->setName('node 2');

        $n3 = new Node();
        $n3->setName('node 3');

        $manager->persist($n1);
        $manager->persist($n2);
        $manager->persist($n3);

        $manager->flush();
    }
}