<?php

require_once './controllers/AppController.php';
require_once './utils/DBConnector.php';
require_once './utils/Response.php';

class ContentController extends AppController
{
    public function getPosts()
    {
        $user = $_SESSION['user'];
        $userEscaped = mysqli_real_escape_string($this->conn, $user);

        if (!empty($this->data['parent'])) {
            $parent = mysqli_real_escape_string($this->conn, $this->data['parent']);
            
            if (!empty($this->data['user'])) {
                $userOfPosts = mysqli_real_escape_string($this->conn, $this->data['user']);
                $sql = "SELECT paths.*, profile.photo, ROUND(COALESCE(AVG(reaction.rating), 0)) as rating 
                        FROM paths 
                        INNER JOIN profile ON profile.name = paths.name 
                        LEFT JOIN reaction ON reaction.url = paths.url 
                        WHERE paths.parent='$parent' AND paths.name='$userOfPosts' AND (paths.access = 'public' 
                           OR (paths.access = 'friends' AND paths.name IN 
                              (SELECT CASE 
                                      WHEN user_2 = '$userEscaped' THEN user_1 
                                      ELSE user_2 
                                      END AS friend 
                               FROM friends 
                               WHERE user_1 = '$userEscaped' 
                               OR user_2 = '$userEscaped'))) OR ('$userOfPosts' = '$userEscaped' AND paths.name='$userEscaped') 
                        GROUP BY paths.id, profile.photo 
                        ORDER BY date DESC";
            } else if (!empty($this->data['club'])) {
                $club = mysqli_real_escape_string($this->conn, $this->data['club']);
                $sql = "SELECT paths.*, profile.photo, ROUND(COALESCE(AVG(reaction.rating), 0)) as rating 
                        FROM paths 
                        INNER JOIN profile ON profile.name = paths.name 
                        LEFT JOIN reaction ON reaction.url = paths.url 
                        WHERE paths.parent='$parent' AND paths.access='public' AND profile.name IN(SELECT user FROM club_user WHERE club='$club') 
                        GROUP BY paths.id, profile.photo 
                        ORDER BY date DESC";
            } else {
                $sql = "SELECT paths.*, profile.photo, ROUND(COALESCE(AVG(reaction.rating), 0)) as rating 
                        FROM paths 
                        INNER JOIN profile ON profile.name = paths.name 
                        LEFT JOIN reaction ON reaction.url = paths.url 
                        WHERE paths.parent='$parent' AND (paths.access = 'public' 
                           OR (paths.access = 'friends' AND paths.name IN 
                              (SELECT CASE 
                                      WHEN user_2 = '$userEscaped' THEN user_1 
                                      ELSE user_2 
                                      END AS friend 
                               FROM friends 
                               WHERE user_1 = '$userEscaped' 
                               OR user_2 = '$userEscaped'))) 
                        GROUP BY paths.id, profile.photo 
                        ORDER BY date DESC";
            }
        } else if (!empty($this->data['url'])) {
            $url = mysqli_real_escape_string($this->conn, $this->data['url']);
            $sql = "SELECT paths.*, profile.photo FROM paths INNER JOIN profile on profile.name=paths.name WHERE paths.url='$url' LIMIT 1";
        } else {
            $sql = "SELECT paths.*, profile.photo, ROUND(COALESCE(AVG(reaction.rating), 0)) as rating 
                    FROM paths 
                    INNER JOIN profile ON profile.name = paths.name 
                    LEFT JOIN reaction ON reaction.url = paths.url 
                    WHERE paths.name IN (SELECT CASE 
                        WHEN user_2 = '$userEscaped' THEN user_1 
                        ELSE user_2 
                        END AS friend FROM friends WHERE user_1='$userEscaped' OR user_2='$userEscaped') 
                    GROUP BY paths.id, profile.photo 
                    ORDER BY date DESC";
        }

        $result = mysqli_query($this->conn, $sql);
        if ($result) {
            $data = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $message = "Content fetched successfully";
            $state = 'success';
            $this->response->send($state, $message, ['data' => $data]);
        } else {
            $message = 'Error fetching content';
            $state = 'error';
            $this->response->send($state, $message);
        }
    }

    public function getPins()
    {
        if (!empty($this->data['club'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $sql = "SELECT paths.conf, paths.body, paths.title, paths.access, paths.name, pins.quote, paths.url, profile.photo as pinnerPhoto, profile.name as pinnerName, ROUND(COALESCE(AVG(reaction.rating), 0)) as rating 
                    FROM pins 
                    INNER JOIN paths ON paths.url = pins.url 
                    INNER JOIN profile ON profile.name = pins.name 
                    LEFT JOIN reaction ON reaction.url = paths.url 
                    WHERE pins.club='$club' 
                    GROUP BY paths.id, profile.photo, pins.id 
                    ORDER BY pins.date DESC";

            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                $message = "Content fetched successfully";
                $state = 'success';
                $this->response->send($state, $message, ['data' => $data]);
            } else {
                $message = 'Error fetching content';
                $state = 'error';
                $this->response->send($state, $message);
            }
        }
    }

    public function getGossips()
    {
        if (!empty($this->data['club'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $sql = "SELECT * FROM gossip WHERE club='$club' AND date >= NOW() - INTERVAL 1 DAY";

            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                $message = "Content fetched successfully";
                $state = 'success';
                $this->response->send($state, $message, ['data' => $data]);
            } else {
                $message = 'Error fetching content';
                $state = 'error';
                $this->response->send($state, $message);
            }
        }
    }

    public function getGallery()
    {
        $sql = "";
        if (!empty($this->data['club'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $sql = "SELECT paths.conf, paths.url FROM pins INNER JOIN paths ON paths.url = pins.url WHERE conf!='' AND paths.access='public' AND pins.club = '$club'";
        } else if (!empty($this->data['user'])) {
            $userToFind = mysqli_real_escape_string($this->conn, $this->data['user']);
            $sql = "SELECT conf, url FROM paths WHERE conf!='' AND access='public' AND name='$userToFind'";
        }

        if ($sql !== "") {
            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                $message = "Content fetched successfully";
                $state = 'success';
                $this->response->send($state, $message, ['data' => $data]);
                return;
            }
        }

        $message = 'Error fetching content';
        $state = 'error';
        $this->response->send($state, $message);
    }

    public function getDiary()
    {
        $user = $_SESSION['user'];
        $userEscaped = mysqli_real_escape_string($this->conn, $user);
        
        $sql = "SELECT profile.*, diary.message, diary.date
                FROM profile
                INNER JOIN diary ON profile.name = diary.name
                WHERE (
                    profile.name = '$userEscaped'
                    OR profile.name IN (
                        SELECT 
                            CASE
                                WHEN friends.user_2 = '$userEscaped' THEN friends.user_1
                                ELSE friends.user_2
                            END
                        FROM friends
                        WHERE friends.user_1 = '$userEscaped' 
                        OR friends.user_2 = '$userEscaped'
                    )
                )
                AND diary.date >= NOW() - INTERVAL 1 DAY
                ORDER BY diary.date DESC;";

        $result = mysqli_query($this->conn, $sql);
        if ($result) {
            $data = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $message = "Content fetched successfully";
            $state = 'success';
            $this->response->send($state, $message, ['data' => $data]);
        } else {
            $message = 'Error fetching content';
            $state = 'error';
            $this->response->send($state, $message);
        }
    }

    public function getTrends()
    {
        $sql = "SELECT collect, COUNT(*) as count FROM paths WHERE date >= NOW() - INTERVAL 1 DAY AND collect IS NOT NULL AND collect != '' GROUP BY collect ORDER BY count DESC LIMIT 5";

        $result = mysqli_query($this->conn, $sql);
        if ($result) {
            $data = array();
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $message = "Content fetched successfully";
            $state = 'success';
            $this->response->send($state, $message, ['data' => $data]);
        } else {
            $message = 'Error fetching content';
            $state = 'error';
            $this->response->send($state, $message);
        }
    }

    public function getReactions()
    {
        if (!empty($this->data['id'])) {
            $id = mysqli_real_escape_string($this->conn, $this->data['id']);
            $sql = "SELECT reaction.rating, reaction.name, profile.photo FROM reaction INNER JOIN profile on profile.name=reaction.name WHERE url='$id'";

            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                $message = "Content fetched successfully";
                $state = 'success';
                $this->response->send($state, $message, ['data' => $data]);
            } else {
                $message = 'Error fetching content';
                $state = 'error';
                $this->response->send($state, $message);
            }
        }
    }

    public function getFriends()
    {
        if (!empty($this->data['user'])) {
            $user = mysqli_real_escape_string($this->conn, $this->data['user']);

            $sql = "SELECT * FROM profile WHERE name IN (SELECT CASE 
                    WHEN user_2 = '$user' THEN user_1 
                    ELSE user_2 
                    END AS friend FROM friends WHERE user_1='$user' OR user_2='$user')";

            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                $message = "Content fetched successfully";
                $state = 'success';
                $this->response->send($state, $message, ['data' => $data]);
            } else {
                $message = 'Error fetching content';
                $state = 'error';
                $this->response->send($state, $message);
            }
        }
    }
}
?>
