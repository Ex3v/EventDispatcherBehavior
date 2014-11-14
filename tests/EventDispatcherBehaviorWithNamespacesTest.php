<?php

use Symfony\Component\EventDispatcher\Event;
use My\Post;

/**
 * @author William Durand <william.durand1@gmail.com>
 */
class EventDispatcherBehaviorWithNamespacesTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('My\Post')) {
            $schema = <<<EOF
<database name="event_dispatcher_behavior" defaultIdMethod="native" namespace="My">
    <table name="post">
        <column name="id" required="true" primaryKey="true" autoIncrement="true" type="INTEGER" />
        <column name="name" type="VARCHAR" required="true" />

        <behavior name="\EventDispatcherBehavior" />
    </table>
</database>
EOF;

            $builder = new \Propel\Generator\Util\QuickBuilder();
            $config  = $builder->getConfig();
            $builder->setConfig($config);
            $builder->setSchema($schema);

            $builder->build();
        }
    }

    public function testObjectMethods()
    {
        $this->assertTrue(method_exists('My\Post', 'getEventDispatcher'));
        $this->assertTrue(method_exists('My\Post', 'setEventDispatcher'));
        $this->assertTrue(defined('My\Post::EVENT_PRE_SAVE'));
        $this->assertTrue(defined('My\Post::EVENT_POST_SAVE'));
        $this->assertTrue(defined('My\Post::EVENT_PRE_UPDATE'));
        $this->assertTrue(defined('My\Post::EVENT_POST_UPDATE'));
        $this->assertTrue(defined('My\Post::EVENT_PRE_INSERT'));
        $this->assertTrue(defined('My\Post::EVENT_POST_INSERT'));
        $this->assertTrue(defined('My\Post::EVENT_PRE_DELETE'));
        $this->assertTrue(defined('My\Post::EVENT_POST_DELETE'));
    }

    public function testGetDispatcher()
    {
        $post = new Post();
        $dispatcher = $post->getEventDispatcher();

        $this->assertNotNull($dispatcher);
        $this->assertInstanceOf('Symfony\Component\EventDispatcher\EventDispatcher', $dispatcher);
    }

    public function testFireEvent()
    {
        $preSaveFired  = false;
        $postSaveFired = false;

        $that = $this;
        Post::getEventDispatcher()->addListener(Post::EVENT_PRE_SAVE, function (Event $event) use (& $preSaveFired, $that) {
            $preSaveFired = true;

            $that->assertInstanceOf('Symfony\Component\EventDispatcher\GenericEvent', $event);
            $that->assertInstanceOf('My\Post', $event->getSubject());
            $that->assertInstanceOf('Propel\Runtime\Connection\ConnectionInterface', $event->getArgument('connection'));
        });

        Post::getEventDispatcher()->addListener(Post::EVENT_POST_SAVE, function (Event $event) use (& $postSaveFired, $that) {
            $postSaveFired = true;

            $that->assertInstanceOf('Symfony\Component\EventDispatcher\GenericEvent', $event);
            $that->assertInstanceOf('My\Post', $event->getSubject());
            $that->assertInstanceOf('Propel\Runtime\Connection\ConnectionInterface', $event->getArgument('connection'));
        });

        $post = new Post();
        $post->setName('a-name');
        $post->save();

        $this->assertTrue($preSaveFired);
        $this->assertTrue($postSaveFired);
    }

    public function testObjectImplementsAnInterface()
    {
        $reflClass = new ReflectionClass('My\Post');
        $this->assertTrue($reflClass->implementsInterface('EventDispatcherAwareModelInterface'));
    }
}
