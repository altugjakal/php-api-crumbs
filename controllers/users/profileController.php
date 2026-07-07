<?php

require_once './utils/DBConnector.php';
require_once './utils/Response.php';
require_once './controllers/AppController.php';

class ProfileController extends AppController
{
    public function getProfile()
    {
        if (isset($this->data['user'])) {
            $userToFind = mysqli_real_escape_string($this->conn, $this->data['user']);
            $sql = "SELECT profile.*, diary.message
                    FROM profile
                    LEFT JOIN diary ON diary.name = profile.name
                    WHERE profile.name = '$userToFind'
                    ORDER BY date DESC
                    LIMIT 1;";
            $result = mysqli_query($this->conn, $sql);
            if ($result) {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                $state = 'success';
                $message = 'User details found.';
                $this->response->send($state, $message, ['data' => $data]);
            } else {
                $state = 'error';
                $message = 'Query failed.';
                $this->response->send($state, $message);
            }
        }
    }

    public function updateProfile()
    {
        $user = $_SESSION['user'] ?? null;
        if (empty($user)) {
            $this->response->send('error', 'Unauthorized.');
            exit;
        }

        $updates = [];

        if (isset($this->data['description']) && $this->data['description'] !== '') {
            $description = mysqli_real_escape_string($this->conn, $this->data['description']);
            $updates[] = "description = '$description'";
        }

        if (isset($this->data['home']) && $this->data['home'] !== '') {
            $home = mysqli_real_escape_string($this->conn, $this->data['home']);
            $updates[] = "home = '$home'";
        }

        if (isset($this->data['relationship']) && $this->data['relationship'] !== '0' && $this->data['relationship'] !== '') {
            $relationship = mysqli_real_escape_string($this->conn, $this->data['relationship']);
            $updates[] = "relationship = '$relationship'";
        }

        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $directory = dirname(__DIR__, 2) . "/profile-images/";
            $fileName = $_FILES["photo"]["name"];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExtension, $allowedExtensions, true)) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($_FILES["photo"]["tmp_name"]);
                if (strpos($mimeType, 'text/php') !== false || strpos($mimeType, 'application/x-php') !== false) {
                    $this->response->send('error', 'Invalid file content signature.');
                    exit;
                }

                $newName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
                $file = $directory . $newName;

                if (!is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                if (move_uploaded_file($_FILES["photo"]["tmp_name"], $file)) {
                    $updates[] = "photo = '$newName'";
                } else {
                    $this->response->send('error', 'File transfer failed.');
                    exit;
                }
            } else {
                $this->response->send('error', 'Invalid file type.');
                exit;
            }
        }

        if (empty($updates)) {
            $this->response->send('success', 'No changes detected.');
            exit;
        }

        $userEscaped = mysqli_real_escape_string($this->conn, $user);
        $sql = "UPDATE profile SET " . implode(', ', $updates) . " WHERE name = '$userEscaped'";

        if (mysqli_query($this->conn, $sql)) {
            $this->response->send('success', 'Profile updated successfully.');
        } else {
            $this->response->send('error', 'Failed to update database.');
        }
    }

    public function getClubs($findClubOf)
    {
        $findClubOfEscaped = mysqli_real_escape_string($this->conn, $findClubOf);
        $sql = "SELECT * FROM clubs WHERE name IN(SELECT club FROM club_user WHERE user='$findClubOfEscaped')";
        $result = mysqli_query($this->conn, $sql);
        $clubs = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $clubs[] = $row;
            }
        }
        return $clubs;
    }

    public function getContacts($findContactsOf)
    {
        $findContactsOfEscaped = mysqli_real_escape_string($this->conn, $findContactsOf);
        $sql = "SELECT url FROM channel_user WHERE user='$findContactsOfEscaped'";
        $result = mysqli_query($this->conn, $sql);
        $clubs = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $clubs[] = $row;
            }
        }
        return $clubs;
    }
}
?>
