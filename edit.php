<?php
// --- MODIFIED: Replaced the old connection block with a single include ---
include 'config.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['admin'])) {
    header("Location: gallery.php");
    exit;
}

// The rest of the PHP logic is unchanged
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($post_id <= 0) {
    die("Invalid Post ID.");
}

if (isset($_POST['update_post'])) {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $pc_link = $_POST["pc_link"] ?? null;
    $mobile_link = $_POST["mobile_link"] ?? null;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $tags = $_POST["tags"] ?? '';
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $imagePath = $_POST["existing_image"];

    if (!empty($_FILES["image"]["name"])) {
        if (!empty($imagePath) && file_exists($imagePath)) {
            unlink($imagePath);
        }
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath;
        }
    }

    $stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, image = ?, pc_link = ?, mobile_link = ?, rating = ?, tags = ?, status = ? WHERE id = ?");
    $stmt->execute([$title, $content, $imagePath, $pc_link, $mobile_link, $rating, $tags, $status, $post_id]);

    header("Location: gallery.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Post | Caly</title> <!-- Updated for better branding -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body{font-family:'Poppins',sans-serif;background:#181818;color:white;margin:0;padding:20px}
        .edit-panel{max-width:600px;margin:20px auto 40px;box-shadow:0 0 15px #ff4747cc;background:#222;padding:30px;border-radius:10px}
        .edit-panel h3{font-size:1.8rem;color:#ff4747;text-align:center;margin-top:0;margin-bottom:25px}
        form{display:flex;flex-direction:column}
        input[type=text],input[type=password],textarea,input[type=number],select{width:100%;padding:12px;margin-bottom:15px;background:#333;border:1px solid #444;border-radius:5px;color:#fff;font-family:'Poppins',sans-serif;font-size:1rem;transition:all .3s ease}
        input[type=text]:focus,input[type=password]:focus,textarea:focus,input[type=number]:focus,select:focus{outline:none;border-color:#ff4747;box-shadow:0 0 8px rgba(255,71,71,.5)}
        textarea{resize:vertical;min-height:120px}
        label{margin-bottom:5px;font-size:.9rem;color:#ccc}
        input[type=file]{font-family:'Poppins',sans-serif;background:#333;border:1px solid #444;border-radius:5px;padding:10px;margin-bottom:20px;cursor:pointer;width:100%;color:#fff}
        input[type=file]::file-selector-button{font-weight:700;color:#ff4747;background-color:#1e1e1e;padding:8px 12px;border:none;border-radius:3px;cursor:pointer;margin-right:10px}
        button[type=submit]{font-family:'Poppins',sans-serif;font-weight:600;font-size:1rem;padding:12px 20px;border-radius:5px;transition:all .2s ease;cursor:pointer;border:none;background:#ff4747;color:#fff;text-transform:uppercase;letter-spacing:1px;margin-top:10px}
        button[type=submit]:hover{background:#d62c2c;box-shadow:0 0 10px #d62c2c}
        .current-image{max-width:200px;border-radius:8px;border:2px solid #444;margin-bottom:10px;display:block}
        .back-link{color:#ff4747;text-decoration:none;display:inline-block;margin-bottom:20px}
    </style>
</head>
<body>
    <div class="edit-panel">
        <a href="gallery.php" class="back-link">&larr; Back to The Library</a>
        <h3>Edit Post</h3>
        <form method="POST" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="existing_image" value="<?= htmlspecialchars($post['image'] ?? '') ?>">

            <label for="title">Title</label>
            <input type="text" name="title" id="title" required value="<?= htmlspecialchars($post['title'] ?? '') ?>">
            
            <label for="status">Status</label>
            <select name="status" id="status">
                <option value="1" <?= ($post['status'] ?? 1) == 1 ? 'selected' : '' ?>>Completed</option>
                <option value="0" <?= ($post['status'] ?? 1) == 0 ? 'selected' : '' ?>>Ongoing</option>
            </select>
            
            <label for="rating">Rating (0-5)</label>
            <input type="number" name="rating" id="rating" min="0" max="5" value="<?= (int)($post['rating'] ?? 0) ?>" required>
            
            <label for="tags">Tags (comma-separated)</label>
            <input type="text" name="tags" id="tags" placeholder="Harem, Sci-Fi, Corruption" value="<?= htmlspecialchars($post['tags'] ?? '') ?>">
            
            <label for="content">Description</label>
            <textarea name="content" id="content" required rows="6"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
            
            <label for="pc_link">PC Link</label>
            <input type="text" name="pc_link" id="pc_link" value="<?= htmlspecialchars($post['pc_link'] ?? '') ?>">

            <label for="mobile_link">Mobile Link</label>
            <input type="text" name="mobile_link" id="mobile_link" value="<?= htmlspecialchars($post['mobile_link'] ?? '') ?>">

            <label>Current Cover Image</label>
            <?php if (!empty($post['image'])): ?>
                <img src="<?= htmlspecialchars($post['image']) ?>" alt="Current Image" class="current-image">
            <?php else: ?>
                <p>No image uploaded.</p>
            <?php endif; ?>
            
            <label for="image">Upload New Cover Image (Optional)</label>
            <input type="file" name="image" id="image" accept="image/*">
            
            <button type="submit" name="update_post">Save Changes</button>
        </form>
    </div>
</body>
</html>