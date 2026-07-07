<?php

require_once './controllers/AppController.php';
require_once './utils/DBConnector.php';
require_once './utils/Response.php';

class ClubController extends AppController
{
    public function joinClub()
    {
        if (!empty($this->data['club'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $user = $_SESSION['user'];
            
            $sql = "SELECT * FROM clubs WHERE name='$club'";
            $result = mysqli_query($this->conn, $sql);
            if (mysqli_num_rows($result) === 1) {
                $userEscaped = mysqli_real_escape_string($this->conn, $user);
                $sql = "INSERT IGNORE INTO club_user (user, club) VALUES ('$userEscaped', '$club')";
                if (mysqli_query($this->conn, $sql)) {
                    $state = 'success';
                    $message = 'Joined the club.';
                    $this->response->send($state, $message);
                } else {
                    $state = 'error';
                    $message = 'Error joining club.';
                    $this->response->send($state, $message);
                }
            } else {
                $state = 'error';
                $message = 'Club not found.';
                $this->response->send($state, $message);
            }
        }
    }
   
    public function getClub()
    {
        if (isset($this->data['user'])) {
            $findClubOf = mysqli_real_escape_string($this->conn, $this->data['user']);
            $sql = "SELECT * FROM clubs WHERE name IN(SELECT club FROM club_user WHERE user='$findClubOf')";
            $data = array();

            if ($result = mysqli_query($this->conn, $sql)) {
                while ($row = mysqli_fetch_array($result)) {
                    $data[] = $row;
                }
                $state = 'success';
                $message = 'Clubs found';
                $this->response->send($state, $message, ['data' => $data]);
            }
        } else if (isset($this->data['name'])) {
            error_log('User found in getClub');
            $club = mysqli_real_escape_string($this->conn, $this->data['name']);
            $sql = "SELECT * FROM clubs WHERE name='$club'";
            $data = array();
            if ($result = mysqli_query($this->conn, $sql)) {
                if (mysqli_num_rows($result) == 0) {
                    $state = 'error';
                    $message = 'No clubs found';
                    $this->response->send($state, $message, []);
                    return;
                }
                while ($row = mysqli_fetch_array($result)) {
                    $data[] = $row;
                }
                $state = 'success';
                $message = 'Clubs found';
                $this->response->send($state, $message, ['data' => $data]);
            }
        } else {
            $state = 'error';
            $message = 'No club found';
            $this->response->send($state, $message, []);
        }
    }

    public function createClub()
    {
        if (!empty($this->data['name'])) {
            $name = str_replace(' ', '', $this->data['name']);
            $nameEscaped = mysqli_real_escape_string($this->conn, $name);
            $sql = "SELECT * FROM clubs WHERE name='$nameEscaped'";
            $result = mysqli_query($this->conn, $sql);
            if (mysqli_num_rows($result) === 0) {
                $founder = $_SESSION['user'];
                $description = 'The club of ' . $founder;
                $card = 'Crumbs';
                $point = 0;
                $image = 'default.png';
                
                $founderEscaped = mysqli_real_escape_string($this->conn, $founder);
                $descriptionEscaped = mysqli_real_escape_string($this->conn, $description);
                
                $sql = "INSERT INTO clubs (name, founder, description, card, point, photo) VALUES ('$nameEscaped', '$founderEscaped', '$descriptionEscaped', '$card','$point', '$image')";
                if (mysqli_query($this->conn, $sql)) {
                    $sql = "INSERT INTO club_user (user, club) VALUES ('$founderEscaped', '$nameEscaped')";
                    if (mysqli_query($this->conn, $sql)) {
                        $state = 'success';
                        $message = 'Club created successfully';
                        $this->response->send($state, $message, []);
                    } else {
                        $state = 'error';
                        $message = 'Error creating club user';
                        $this->response->send($state, $message, []);
                    }
                } else {
                    $state = 'error';
                    $message = 'Error creating club';
                    $this->response->send($state, $message, []);
                }
            } else {
                $state = 'error';
                $message = 'Club already exists';
                $this->response->send($state, $message, []);
            }
        }
    }

    public function updateClub()
    {
        if (!empty($this->data['club'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $user = $_SESSION['user'];
            $userEscaped = mysqli_real_escape_string($this->conn, $user);
            
            $sql = "SELECT * FROM clubs WHERE name='$club' AND founder='$userEscaped'";
            $result = mysqli_query($this->conn, $sql);
            if (mysqli_num_rows($result) === 1) {
                $updates = [];

                if (isset($this->data['card'])) {
                    $allowedCards = ['pumpkin', 'cardinal', 'night', 'pacific', 'green'];
                    $card = in_array($this->data['card'], $allowedCards) ? $this->data['card'] : 'crumbs';
                    $updates[] = "card='$card'";
                }
                
                if (isset($this->data['description'])) {
                    $description = mysqli_real_escape_string($this->conn, $this->data['description']);
                    $updates[] = "description='$description'";
                }

                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $directory = dirname(__DIR__, 2) . "/club-images/";
                    $fileName = $_FILES["photo"]["name"];
                    $filetype = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    
                    if ($_FILES["photo"]["size"] < 1200000) {
                        if (in_array($filetype, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
                            $newName = uniqid('club_', true) . '.' . $filetype;
                            $file = $directory . $newName;

                            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $file)) {
                                $updates[] = "photo='$newName'";
                            } else {
                                $this->response->send('error', 'File transfer failed.', []);
                                exit;
                            }
                        } else {
                            $this->response->send('error', 'File format is not supported.', []);
                            exit;
                        }
                    } else {
                        $this->response->send('error', 'File should be smaller than 1100kb.', []);
                        exit;
                    }
                }

                if (!empty($updates)) {
                    $sql = "UPDATE clubs SET " . implode(', ', $updates) . " WHERE founder='$userEscaped' AND name='$club'";
                    mysqli_query($this->conn, $sql);
                }

                $state = 'success';
                $message = 'Club updated.';
                $this->response->send($state, $message, []);
            } else {
                $state = 'error';
                $message = 'You can only edit your clubs.';
                $this->response->send($state, $message, []);
            }
        }
    }

    public function leaveClub()
    {
        if (!empty($this->data['club'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $user = $_SESSION['user'];
            $userEscaped = mysqli_real_escape_string($this->conn, $user);
            
            $sql = "SELECT * FROM club_user WHERE club='$club' AND user='$userEscaped'";
            $result = mysqli_query($this->conn, $sql);
            if (mysqli_num_rows($result) === 1) {
                $sql = "DELETE FROM clubs WHERE name='$club'";
                if (mysqli_query($this->conn, $sql)) {
                    $sql = "DELETE FROM club_user WHERE club='$club'";
                    if (mysqli_query($this->conn, $sql)) {
                        $state = 'success';
                        $message = 'Club left.';
                        $this->response->send($state, $message);
                    } else {
                        $state = 'error';
                        $message = 'Error deleting club user.';
                        $this->response->send($state, $message);
                    }
                } else {
                    $state = 'error';
                    $message = 'Error deleting club.';
                    $this->response->send($state, $message);
                }
            } else {
                $sql = "DELETE FROM club_user WHERE club='$club' AND user='$userEscaped'";
                if (mysqli_query($this->conn, $sql)) {
                    $state = 'success';
                    $message = 'Left the club.';
                    $this->response->send($state, $message);
                } else {
                    $state = 'error';
                    $message = 'Error leaving club.';
                    $this->response->send($state, $message);
                }
            }
        }
    }

    public function getClubMembers()
    {
        if (!empty($this->data['name'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['name']);
            $sql = "SELECT profile.* FROM club_user JOIN profile ON club_user.user=profile.name WHERE club='$club'";
            $data = array();
            if ($result = mysqli_query($this->conn, $sql)) {
                while ($row = mysqli_fetch_array($result)) {
                    $data[] = $row;
                }
                $state = 'success';
                $message = 'Members found.';
                $this->response->send($state, $message, ['data' => $data]);
            } else {
                $state = 'error';
                $message = 'Error getting members.';
                $this->response->send($state, $message, []);
            }
        }
    }

    public function banClubMembers()
    {
        if (!empty($this->data['club']) && !empty($this->data['users'])) {
            $club = mysqli_real_escape_string($this->conn, $this->data['club']);
            $user = $_SESSION['user'];
            $userEscaped = mysqli_real_escape_string($this->conn, $user);
            $usersToBan = $this->data['users'];
            
            $sql = "SELECT * FROM clubs WHERE name='$club' AND founder='$userEscaped'";
            $result = mysqli_query($this->conn, $sql);
            if(mysqli_num_rows($result) > 0) {
                $count = count($usersToBan);
                $i = 0;
                foreach ($usersToBan as $userToBan) {
                    $userToBanEscaped = mysqli_real_escape_string($this->conn, $userToBan);
                    $sql = "DELETE FROM club_user WHERE club='$club' AND user='$userToBanEscaped'";           
                    if (mysqli_query($this->conn, $sql)) {
                        if(++$i === $count) {
                            $state = 'success';
                            $message = 'Users banned';
                            $this->response->send($state, $message, []);
                        }
                    } else {
                        $state = 'error';
                        $message = 'Error banning user: ' . $userToBan;
                        $this->response->send($state, $message, []);
                        exit;
                    }
                }
            } else {
                $state = 'error';
                $message = 'You are not the founder of this club.';
                $this->response->send($state, $message, []);
            }
        }
    }
}
?>
