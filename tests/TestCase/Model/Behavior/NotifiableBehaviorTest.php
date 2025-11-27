<?php
declare(strict_types=1);

namespace Crustum\Notification\Test\TestCase\Model\Behavior;

use Cake\ORM\Association\HasMany;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * NotifiableBehavior Test Case
 *
 * Tests that the behavior correctly creates associations and provides notification methods
 */
class NotifiableBehaviorTest extends TestCase
{
    /**
     * Fixtures to load
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Crustum/Notification.Notifications',
    ];

    /**
     * Test that behavior creates hasMany association to Notifications
     *
     * @return void
     */
    public function testCreatesNotificationsAssociation(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $table->addBehavior('Crustum/Notification.Notifiable');

        $this->assertTrue($table->hasAssociation('Notifications'));

        $association = $table->getAssociation('Notifications');
        $this->assertInstanceOf(HasMany::class, $association);
        $this->assertEquals('foreign_key', $association->getForeignKey());
        $this->assertEquals('Crustum/Notification.Notifications', $association->getClassName());
    }

    /**
     * Test that association has proper conditions for model filtering
     *
     * @return void
     */
    public function testAssociationHasModelCondition(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $table->addBehavior('Crustum/Notification.Notifiable');

        $association = $table->getAssociation('Notifications');
        $conditions = $association->getConditions();

        $this->assertArrayHasKey('Notifications.model', $conditions);
        $this->assertEquals('Users', $conditions['Notifications.model']);
    }

    /**
     * Test that behavior implements required methods
     *
     * @return void
     */
    public function testImplementsRequiredMethods(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $table->addBehavior('Crustum/Notification.Notifiable');

        $behavior = $table->getBehavior('Notifiable');
        $implementedMethods = $behavior->implementedMethods();

        $this->assertArrayHasKey('notify', $implementedMethods);
        $this->assertArrayHasKey('notifyNow', $implementedMethods);
        $this->assertArrayHasKey('routeNotificationFor', $implementedMethods);
    }

    /**
     * Test routeNotificationFor returns association for database channel
     *
     * @return void
     */
    public function testRouteNotificationForDatabase(): void
    {
        $table = TableRegistry::getTableLocator()->get('Users');
        $table->addBehavior('Crustum/Notification.Notifiable');

        $user = $table->newEntity(['id' => 1, 'username' => 'test']);
        $route = $table->routeNotificationFor($user, 'database');

        $this->assertInstanceOf(HasMany::class, $route);
        $this->assertEquals('Notifications', $route->getName());
    }
}
