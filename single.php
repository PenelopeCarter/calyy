<?php
// --- MODIFIED: Replaced the old connection block with a single include ---
include 'config.php';

// The rest of the PHP logic is unchanged
$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if ($post_id <= 0) {
    header("Location: gallery.php");
    exit;
}

if (isset($_POST['delete_screenshot']) && isset($_SESSION['admin'])) {
    $screenshot_id = (int)$_POST['screenshot_id_to_delete'];
    $stmt = $pdo->prepare("SELECT image FROM screenshots WHERE id = ? AND post_id = ?");
    $stmt->execute([$screenshot_id, $post_id]);
    $shot = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($shot && !empty($shot['image']) && file_exists($shot['image'])) {
        unlink($shot['image']);
    }
    $pdo->prepare("DELETE FROM screenshots WHERE id = ?")->execute([$screenshot_id]);
    header("Location: single.php?post_id=$post_id");
    exit;
}

if (isset($_POST['upload_screenshot']) && isset($_SESSION['admin'])) {
    if (isset($_FILES["screenshot"]) && $_FILES["screenshot"]["error"] == 0) {
        $targetDir = "screenshots/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["screenshot"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["screenshot"]["tmp_name"], $targetFilePath)) {
            $stmt = $pdo->prepare("INSERT INTO screenshots (post_id, image) VALUES (?, ?)");
            $stmt->execute([$post_id, $targetFilePath]);
            header("Location: single.php?post_id=$post_id");
            exit;
        } else {
            $upload_error = "Failed to move uploaded screenshot.";
        }
    } else {
        $upload_error = "Please select a file to upload.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die("Post not found.");
}

$stmt = $pdo->prepare("SELECT * FROM screenshots WHERE post_id = ? ORDER BY id ASC");
$stmt->execute([$post_id]);
$screenshots = $stmt->fetchAll(PDO::FETCH_ASSOC);

$screenshots_to_show_initially = 6;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($post['title']) ?> | Caly</title> <!-- Updated for better branding -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        :root{--brand-red:#ff4747;--brand-red-hover:#ff1a1a;--dark-bg:#121212;--dark-card:#1e1e1e;--dark-subtle:#2c2c2c;--text-light:#ccc;--text-white:#fff;--status-green: #1ae98d;}
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--dark-bg); color: var(--text-white); margin: 0; }
        .page-header { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; margin-bottom: 25px; color: var(--text-light); font-weight: 600; text-decoration: none; transition: all 0.2s ease; }
        .back-link:hover { color: var(--brand-red); transform: translateX(-5px); }
        .single-post-container { display: grid; grid-template-columns: 1fr 2fr; gap: 40px; max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .post-media-column { position: sticky; top: 20px; align-self: start; }
        .post-details-column { min-width: 0; }
        .post-cover-image { width: 100%; border-radius: 12px; margin-bottom: 30px; display: block; border: 1px solid var(--dark-subtle); cursor: pointer; transition: all .3s ease; }
        .post-cover-image:hover { border-color: var(--brand-red); box-shadow: 0 0 20px rgba(255,71,71,0.2); }
        .post-details-column h1 { font-size: 3rem; margin: 0 0 15px 0; line-height: 1.2; background: linear-gradient(90deg, var(--brand-red), #ff7070); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .post-meta-bar { display: flex; flex-wrap: wrap; align-items: center; gap: 20px; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid var(--dark-subtle); }
        .meta-item { display: flex; align-items: center; gap: 8px; color: var(--text-light); font-size: 0.9rem; }
        .meta-item i { color: var(--brand-red); font-size: 1.1rem; }
        .star-rating i { font-size: 1.1rem; color: #444; }
        .star-rating i.filled { color: var(--brand-red); }
        .status-badge { padding: 4px 10px; border-radius: 5px; font-size: .8rem; font-weight: 700; text-transform: uppercase; }
        .status-completed { background-color: rgba(26, 233, 141, 0.1); border: 1px solid var(--status-green); color: var(--status-green); }
        .status-ongoing { background-color: rgba(255, 71, 71, 0.1); border: 1px solid var(--brand-red); color: var(--brand-red); }
        .post-tags { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
        .tag-pill { display: inline-block; background: var(--dark-subtle); color: var(--text-light); padding: 5px 12px; border-radius: 15px; text-decoration:none; font-size:.8rem; transition: all .2s ease; }
        .tag-pill:hover { background: var(--brand-red); color: var(--text-white); }
        .content { font-size: 1.1rem; line-height: 1.8; color: var(--text-light); margin-bottom: 40px; }
        .content p { margin: 0 0 1em 0; }
        .download-links-container { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 50px; }
        .download-link-btn { display: inline-flex; align-items: center; gap: 10px; font-weight: 600; font-size: 1.1rem; text-decoration: none; padding: 12px 25px; border-radius: 8px; transition: all 0.3s ease; cursor: pointer; background: var(--brand-red); color: var(--text-white); border: none; }
        .download-link-btn:hover { background: var(--brand-red-hover); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(255, 71, 71, 0.4); }
        .screenshots-section h2 { font-size: 1.5rem; margin: 0 0 20px 0; color: var(--text-white); padding-bottom: 10px; border-bottom: 2px solid var(--dark-subtle); }
        .screenshots-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; }
        .screenshot { border: 1px solid var(--dark-subtle); border-radius: 8px; overflow: hidden; position: relative; transition: all 0.3s ease; background: #111; aspect-ratio: 16 / 9; }
        .screenshot:hover { transform: scale(1.05); box-shadow: 0 0 20px rgba(255, 71, 71, 0.4); border-color: var(--brand-red); }
        .screenshot-link { display: block; width: 100%; height: 100%; }
        .screenshot-link img { width: 100%; height: 100%; display: block; cursor: pointer; object-fit: cover; }
        .delete-screenshot-form { position: absolute; top: 8px; right: 8px; z-index: 10; margin: 0; }
        .delete-btn { background: rgba(214, 44, 44, 0.85); border: none; color: white; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 1rem; line-height: 24px; text-align: center; opacity: 0; transition: opacity 0.3s ease; padding: 0; }
        .screenshot:hover .delete-btn { opacity: 1; }
        .screenshot-hidden { display: none; }
        .show-more-container { text-align: center; margin-top: 20px; margin-bottom: 40px; }
        .show-more-btn { background: var(--dark-subtle); color: var(--text-light); border: 1px solid #444; border-radius: 8px; padding: 10px 25px; font-weight: 600; cursor: pointer; transition: all .2s ease; }
        .show-more-btn:hover { background: var(--brand-red); color: var(--text-white); border-color: var(--brand-red); }
        .admin-section { margin-top: 40px; padding-top: 30px; border-top: 1px solid var(--dark-subtle); }
        .admin-upload-form{ background: var(--dark-card); padding: 30px; border-radius: 10px; border: 1px solid var(--dark-subtle); }
        .admin-upload-form h3 { font-size: 1.5rem; text-align: left; margin: 0 0 25px 0; background: none; color: var(--brand-red); }
        .admin-upload-form input[type=file]{ font-family:'Poppins',sans-serif;background:#333;border:1px solid #444;border-radius:5px;padding:10px;margin-bottom:20px;cursor:pointer;width:100%;color:#fff}
        .admin-upload-form input[type=file]::file-selector-button{font-weight:700;color:var(--brand-red);background-color:#1e1e1e;padding:8px 12px;border:none;border-radius:3px;cursor:pointer;margin-right:10px}
        .admin-upload-form button[type=submit]{font-weight:600;padding:12px 20px;border-radius:5px;transition:all .2s ease;cursor:pointer;border:none;background:var(--brand-red);color:var(--text-white);width:100%}
        .admin-upload-form button[type=submit]:hover{background:var(--brand-red-hover);}
        .error{color:var(--brand-red);text-align:center;margin-bottom:15px;font-weight:700}
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .fade-in { animation: fadeIn 0.6s ease-out forwards; opacity: 0; }
        @media (max-width: 900px) { .single-post-container { grid-template-columns: 1fr; gap: 0; } .post-media-column { position: static; margin-bottom: 30px; } .post-details-column h1 { font-size: 2.5rem; } }
    </style>
</head>
<body>
    <div class="page-header">
        <a href="gallery.php" class="back-link fade-in"><i class="fa-solid fa-arrow-left"></i> Back to Gallery</a>
    </div>

    <div class="single-post-container">
        <div class="post-media-column fade-in" style="animation-delay: 0.1s;">
            <?php if (!empty($post['image'])): ?>
                <a href="<?= htmlspecialchars($post['image']) ?>" data-fancybox="post-main" data-caption="<?= htmlspecialchars($post['title']) ?>">
                    <img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="post-cover-image">
                </a>
            <?php endif; ?>
            
            <div class="screenshots-section">
                <h2>Screenshots</h2>
                <?php if (count($screenshots) > 0): ?>
                    <div class="screenshots-grid">
                        <?php 
                        $ss_counter = 0;
                        foreach ($screenshots as $shot):
                            $ss_counter++;
                            $hidden_class = ($ss_counter > $screenshots_to_show_initially) ? 'screenshot-hidden' : '';
                        ?>
                            <div class="screenshot <?= $hidden_class ?>">
                                <a href="<?=htmlspecialchars($shot['image'])?>" data-fancybox="screenshots" data-caption="Screenshot" class="screenshot-link">
                                    <img src="<?=htmlspecialchars($shot['image'])?>" alt="Screenshot">
                                </a>
                                <?php if (isset($_SESSION['admin'])):?>
                                    <form method="POST" onsubmit="return confirm('Are you sure?');" class="delete-screenshot-form">
                                        <input type="hidden" name="screenshot_id_to_delete" value="<?=$shot['id']?>">
                                        <button type="submit" name="delete_screenshot" class="delete-btn" title="Delete Screenshot">&times;</button>
                                    </form>
                                <?php endif;?>
                            </div>
                        <?php endforeach;?>
                    </div>

                    <?php if (count($screenshots) > $screenshots_to_show_initially): ?>
                        <div class="show-more-container">
                            <button id="showMoreScreenshots" class="show-more-btn">
                                Show More (<?= count($screenshots) - $screenshots_to_show_initially ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else:?>
                    <p style="text-align:center; color: #888;">No screenshots have been uploaded.</p>
                <?php endif;?>
            </div>

            <?php if (isset($_SESSION['admin'])):?>
                <div class="admin-section">
                    <form method="POST" enctype="multipart/form-data" autocomplete="off" class="admin-upload-form">
                        <h3>Upload Screenshot</h3>
                        <?php if (!empty($upload_error)) echo '<p class="error">' . htmlspecialchars($upload_error) . '</p>'; ?>
                        <input type="file" name="screenshot" required accept="image/*">
                        <button type="submit" name="upload_screenshot">Upload</button>
                    </form>
                </div>
            <?php endif;?>
        </div>

        <div class="post-details-column fade-in" style="animation-delay: 0.2s;">
            <h1><?= htmlspecialchars($post['title']) ?></h1>
            <div class="post-meta-bar">
                <div class="meta-item star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-solid fa-star <?= ($i <= $post['rating']) ? 'filled' : '' ?>"></i><?php endfor; ?>
                </div>
                <div class="meta-item">
                    <div class="status-badge <?= $post['status'] == 1 ? 'status-completed' : 'status-ongoing' ?>">
                        <?= $post['status'] == 1 ? 'Completed' : 'Ongoing' ?>
                    </div>
                </div>
                <div class="meta-item">
                    <i class="fa-solid fa-calendar-days"></i>
                    <span>Posted on <?= date('M d, Y', strtotime($post['created_at'])) ?></span>
                </div>
            </div>
            <?php if (!empty($post['tags'])): ?>
                <div class="post-tags">
                    <?php $post_tags = array_map('trim', explode(',', $post['tags']));?>
                    <?php foreach ($post_tags as $tag):?>
                        <a href="gallery.php?tag=<?=urlencode($tag)?>"><span class="tag-pill"><?=htmlspecialchars($tag)?></span></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>
            <?php if (!empty($post['pc_link']) || !empty($post['mobile_link'])): ?>
                <div class="download-links-container">
                    <?php if (!empty($post['pc_link'])): ?>
                        <a href="<?= htmlspecialchars($post['pc_link']) ?>" target="_blank" class="download-link-btn">
                            <i class="fa-solid fa-desktop"></i> PC Version
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($post['mobile_link'])): ?>
                        <a href="<?= htmlspecialchars($post['mobile_link']) ?>" target="_blank" class="download-link-btn">
                            <i class="fa-solid fa-mobile-screen-button"></i> Mobile Version
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer style="text-align:center; color:#444; padding: 40px 20px 20px 20px; font-size:0.9rem;">&copy; <?= date('Y') ?> Calyy — All Rights Reserved</footer>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const showMoreBtn = document.getElementById('showMoreScreenshots');
        if (showMoreBtn) {
            showMoreBtn.addEventListener('click', () => {
                const hiddenScreenshots = document.querySelectorAll('.screenshot.screenshot-hidden');
                hiddenScreenshots.forEach(shot => {
                    shot.classList.remove('screenshot-hidden');
                    shot.style.animation = 'fadeIn 0.5s ease-out forwards';
                });
                showMoreBtn.parentElement.style.display = 'none';
            });
        }
    });
    </script>
</body>
</html>