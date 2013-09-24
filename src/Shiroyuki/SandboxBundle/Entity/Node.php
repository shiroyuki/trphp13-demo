<?php
namespace Shiroyuki\SandboxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="product")
 */
class Node {
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var integer
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     * @var string
     */
    private $name;

    /**
     * @var \Shiroyuki\SandboxBundle\Entity\Node
     */
    private $next;

    public function getId() {
        return $this->id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setNext(Node $next) {
        $this->next = $next;
    }

    public function getNext() {
        return $this->next;
    }
}
