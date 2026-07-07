<?php

require_once './utils/DBConnector.php';
require_once './utils/Response.php';
require_once './controllers/AppController.php';

class PublishController extends AppController
{
    public function createPost()
    {
        $user = $_SESSION['user'];
        if (isset($this->data['title']) && !empty($this->data['body']) && isset($this->data['collect']) && !empty($user)) {

            $url = uniqid();
            $date = date("Y-m-d h:i");
            $directory = __DIR__ . "/images/";

            if (isset($_FILES['conf'])) {
                $fileName = $_FILES["conf"]["name"];
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png'];

                if (!in_array($fileExtension, $allowedExtensions)) {
                    $this->response->send('error', 'Invalid file type. Only JPG, JPEG, and PNG are allowed.');
                    exit;
                }

                $newName = $url . '.' . $fileExtension;
                $file = $directory . $newName;

                if (move_uploaded_file($_FILES["conf"]["tmp_name"], $file)) {
                    $conf = $newName;
                } else {
                    $this->response->send('error', 'File transfer failed.');
                    exit;
                }
            } else {
                $conf = null;
            }

            if (isset($this->data['access'])) {
                if ($this->data['access'] === 'public' || $this->data['access'] === 'friends') {
                    $access = $this->data['access'];
                } else {
                    $access = 'public';
                }
            } else {
                $access = 'public';
            }

            $title = mysqli_real_escape_string($this->conn, $this->data['title']);
            $collect = mysqli_real_escape_string($this->conn, $this->data['collect']);
            $body = mysqli_real_escape_string($this->conn, $this->data['body']);

            if (!empty($this->data['parent'])) {
                $parent = mysqli_real_escape_string($this->conn, $this->data['parent']);
                $sql = "SELECT name, body, title FROM paths WHERE url='$parent'";
                $result = mysqli_query($this->conn, $sql);
                if (mysqli_num_rows($result) === 1) {
                    $row = mysqli_fetch_assoc($result);
                    $parentName = mysqli_real_escape_string($this->conn, $row['name']);
                    $parentTitle = mysqli_real_escape_string($this->conn, $row['title']);
                    $parentBody = mysqli_real_escape_string($this->conn, $row['body']);
                    if ($parentName !== $user) {
                        if ($parentTitle) {
                            $message = $user . ' replied to your post: ' . $parentTitle;
                        } else {
                            $message = $user . ' replied to your post: ' . $parentBody;
                        }
                    }
                } else {
                    $parent = 'public';
                }
            } else {
                $parent = 'public';
            }

            $sql = "INSERT INTO paths (name, title, parent, url, body, date, conf, collect, access) VALUES ('$user', '$title', '$parent', '$url', '$body', '$date', '$conf', '$collect', '$access')";

            if (mysqli_query($this->conn, $sql)) {
                $message = 'Post created successfully.';
                $state = 'success';
                $this->response->send($state, $message, ['url' => $url]);
            } else {
                $message = 'Error in creating post.';
                $state = 'error';
                $this->response->send($state, $message);
            }
        } else {
            $state = 'error';
            $message = 'Please fill in all fields.';
            $this->response->send($state, $message);
        }
    }

    public function createPin()
    {
        $user = $_SESSION['user'];
        $date = date("Y-m-d h:i");
        if (!empty($this->data['url']) && !empty($this->data['club']) && !empty($this->data['category']) && !empty($_SESSION['user'])) {
            if ($this->data['type'] === 'post') {
                $club = mysqli_real_escape_string($this->conn, $this->data['club']);
                $type = mysqli_real_escape_string($this->conn, $this->data['type']);
                $url = mysqli_real_escape_string($this->conn, $this->data['url']);
                $sql = "SELECT * FROM club_user WHERE club='$club' AND user='$user'";
                $result = mysqli_query($this->conn, $sql);

                if (isset($this->data['quote'])) {
                    $quote = mysqli_real_escape_string($this->conn, $this->data['quote']);
                } else {
                    $quote = null;
                }
                if ($result) {
                    if (mysqli_num_rows($result) === 1) {
                        $sql = "INSERT INTO pins (name, quote, type, url, club, date) VALUES ('$user', '$quote', '$type', '$url', '$club', '$date')";
                        if (mysqli_query($this->conn, $sql)) {
                            $state = 'success';
                            $message = 'Pin created successfully.';
                            $this->response->send($state, $message, ['url' => $url]);
                        } else {
                            $state = 'error';
                            $message = 'Error in creating pin.';
                            $this->response->send($state, $message);
                        }
                    } else {
                        $state = 'error';
                        $message = 'You are not a member of this club.';
                        $this->response->send($state, $message);
                    }
                } else {
                    $state = 'error';
                    $message = 'Error in creating pin.';
                    $this->response->send($state, $message);
                }
            } else if ($this->data['category'] === 'note') {
                error_log('Not released yet');
                exit;
            }
        }
    }

    public function createDiary()
    {
        if (!empty($this->data['message'])) {
            $user = $_SESSION['user'];
            $date = date("Y-m-d h:i");
            $message = mysqli_real_escape_string($this->conn, $this->data['message']);
            $sql = "DELETE FROM diary WHERE name='$user'";
            if (mysqli_query($this->conn, $sql)) {
                $sql = "INSERT INTO diary (name, message, date) VALUES ('$user', '$message', '$date')";
                if (mysqli_query($this->conn, $sql)) {
                    $state = 'success';
                    $message = 'Diary Updated';
                    $this->response->send($state, $message);
                } else {
                    $state = 'error';
                    $message = 'Failed';
                    $this->response->send($state, $message);
                }
            } else {
                $state = 'error';
                $message = 'Failed';
                $this->response->send($state, $message);
            }
        }
    }

    public function createGossip()
    {
        if (!empty($this->data['note']) && !empty($this->data['club'])) {
            $user = $_SESSION['user'];
            $date = date("Y-m-d h:i");
            $note = mysqli_real_escape_string($this->conn, $this->data['note']);
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $sql = "INSERT INTO gossip (name, note, club, date) VALUES ('$user', '$note', '$club', '$date')";
            if (mysqli_query($this->conn, $sql)) {
                $state = 'success';
                $message = 'Note Sent';
                $this->response->send($state, $message);
            } else {
                $state = 'error';
                $message = 'Failed';
                $this->response->send($state, $message);
            }
        }
    }

    public function createReaction()
    {
        if (!empty($this->data['value']) && !empty($this->data['url'])) {
            $user = $_SESSION['user'];
            $url = $this->data['url'];
            $date = date("Y-m-d h:i");
            $rating = mysqli_real_escape_string($this->conn, $this->data['value']);
            $sql = "DELETE FROM reaction WHERE name='$user' AND url='$url'";
            if (mysqli_query($this->conn, $sql)) {
                $sql = "INSERT IGNORE INTO reaction (name, rating, url, date) VALUES ('$user', '$rating', '$url', '$date')";
                if (mysqli_query($this->conn, $sql)) {
                    $state = 'success';
                    $message = 'Reaction sent';
                    $this->response->send($state, $message);
                } else {
                    $state = 'error';
                    $message = 'Failed to send reaction';
                    $this->response->send($state, $message);
                }
            }
        }
    }
}
?>
