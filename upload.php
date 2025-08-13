<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'avnlist'); // change DB name if needed

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $download_link = $_POST['download_link'];

    $stmt = $conn->prepare("INSERT INTO posts (title, description, download_link) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $title, $description, $download_link);
    $stmt->execute();

    echo "<p style='color:green;'>Post uploaded successfully!</p>";
}
?>

<h2>Upload New Post</h2>
<form method="POST">
    <input type="text" name="title" placeholder="Title" required><br><br>
    <textarea name="description" placeholder="Description" required></textarea><br><br>
    <input type="url" name="download_link" placeholder="Download Link" required><br><br>
    <button type="submit">Upload</button>
</form>
<a href="logout.php">Logout</a>
