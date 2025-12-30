<?php
declare(strict_types=1);

namespace TestApp\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use TestApp\Notification\PostPublished;

/**
 * Posts Controller
 *
 * @property \TestApp\Model\Table\PostsTable $Posts
 */
class PostsController extends Controller
{
    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event Event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
    }

    /**
     * Publish action - publishes a post and triggers notification
     *
     * @param int|null $id Post ID
     * @return \Cake\Http\Response
     */
    public function publish(?int $id = null)
    {
        $this->request->allowMethod(['post', 'put']);

        $postId = (int)($id ?? $this->request->getData('id'));
        $post = $this->Posts->get($postId, ['contain' => ['Users']]);
        $post->published = true;
        /** @var \TestApp\Model\Table\UsersTable $UsersTable */
        $UsersTable = $this->Posts->Users;

        if ($this->Posts->save($post)) {
            $user = $post->user;
            $UsersTable->notify($user, new PostPublished($post->id, $post->title));

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'post_id' => $post->id,
                ]));
        }

        return $this->response
            ->withType('application/json')
            ->withStatus(400)
            ->withStringBody((string)json_encode(['success' => false]));
    }

    /**
     * Notify action - sends notification to user
     *
     * @return \Cake\Http\Response
     */
    public function notify(): Response
    {
        $this->request->allowMethod(['post']);

        $userId = $this->request->getData('user_id', 1);
        $postId = (int)$this->request->getData('post_id', 1);
        $title = $this->request->getData('title', 'Test Post');

        /** @var \TestApp\Model\Table\UsersTable $UsersTable */
        $UsersTable = $this->Posts->Users;

        $user = $UsersTable->get($userId);
        $UsersTable->notify($user, new PostPublished($postId, $title));

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['success' => true]));
    }
}
