<?php

class Post extends BaseModel {

    public $id, $message, $created, $edited, $user_id, $thread_id, $creator, $thread, $numberInThread;

    public function __construct($attributes) {
        parent::__construct($attributes);
    }

    /**
     * Fetches all the info of a given post. Besides the info that is on the 'post' table in the database, also
     * the post's creator's username and the name of the thread that the post is in are added to the Post object
     * the method returns. Note that for the message of the post the line breaks are changed to br elements so that
     * the line breaks can be rendered correctly.
     * @param $id The id of the post the info is wanted from
     * @return null|Post
     */
    public static function find($id) {
        $query = DB::connection() -> prepare('SELECT p.id, p.message, p.created, p.edited, p.user_id, u.username AS creator, p.thread_id, t.title AS thread
                                                FROM post p, forum_user u, thread t
                                                  WHERE p.id = :id AND p.user_id = u.id AND p.thread_id = t.id');
        $query -> execute(array('id' => $id));

        $row = $query -> fetch();

        if (!$row) {
            return null;
        }

        return new Post(array(
            'id' => $id,
            'message' => nl2br($row['message']),
            'created' => $row['created'],
            'edited' => $row['edited'],
            'user_id' => $row['user_id'],
            'thread_id' => $row['thread_id'],
            'creator' => $row['creator'],
            'thread' => $row['thread']
            // if possible, include also the info on the number of the post within the thread here
        ));
    }

    public static function fetchIdAndMessage($id) {
        $query = DB::connection() -> prepare('SELECT id, message FROM post WHERE id = :id');
        $query -> execute(array('id' => $id));

        $row = $query -> fetch();

        if (!$row) {
            return null;
        }

        return new Post(array(
           'id' => $id,
            'message' => $row['message']
        ));
    }

    public function save() {
        $query = DB::connection() -> prepare('INSERT INTO post (user_id, thread_id, message, created)
                                                VALUES (:user_id, :thread_id, :message, now())');
        $query -> execute(array('user_id' => $this -> user_id, 'thread_id' => $this -> thread_id, 'message' => $this -> message));
    }

    public function update() {
        $query = DB::connection() -> prepare('UPDATE post SET message = :message, edited = now() WHERE id = :id RETURNING thread_id');
        $query -> execute(array('id' => $this -> id, 'message' => $this -> message));

        $this -> setThreadId($query);
    }

    public function destroy() {
        $query = DB::connection() -> prepare('DELETE FROM post WHERE id = :id RETURNING thread_id');
        $query -> execute(array('id' => $this -> id));

        $this -> setThreadId($query);
    }

    private function setThreadId($query) {
        $row = $query -> fetch();
        $this -> thread_id = $row['thread_id'];
    }
}
