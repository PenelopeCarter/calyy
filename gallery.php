<?php
// --- PHP Section is UNCHANGED ---
include 'config.php';

$admin_user = "persiankat123";
$admin_pass = "eternum best game";

if (isset($_POST['login'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['admin'] = true;
        header("Location: gallery.php");
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: gallery.php");
    exit;
}

if (isset($_POST['create_post']) && isset($_SESSION['admin'])) {
    $title = $_POST["title"];
    $content = $_POST["content"];
    $pc_link = $_POST["pc_link"] ?? null;
    $mobile_link = $_POST["mobile_link"] ?? null;
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $tags = $_POST["tags"] ?? '';
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $imagePath = null;

    if (!empty($_FILES["image"]["name"])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
            $imagePath = $targetFilePath;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO posts (title, content, image, pc_link, mobile_link, rating, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $content, $imagePath, $pc_link, $mobile_link, $rating, $tags, $status]);

    header("Location: gallery.php");
    exit;
}

if (isset($_POST['delete_post']) && isset($_SESSION['admin'])) {
    $post_id_to_delete = (int)$_POST['post_id'];
    $stmt = $pdo->prepare("SELECT image FROM posts WHERE id = ?");
    $stmt->execute([$post_id_to_delete]);
    $post_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($post_to_delete && !empty($post_to_delete['image']) && file_exists($post_to_delete['image'])) {
        unlink($post_to_delete['image']);
    }
    $stmt = $pdo->prepare("SELECT image FROM screenshots WHERE post_id = ?");
    $stmt->execute([$post_id_to_delete]);
    $screenshots_to_delete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($screenshots_to_delete as $shot) {
        if (file_exists($shot['image'])) {
            unlink($shot['image']);
        }
    }
    $pdo->prepare("DELETE FROM screenshots WHERE post_id = ?")->execute([$post_id_to_delete]);
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$post_id_to_delete]);
    header("Location: gallery.php");
    exit;
}

$selected_tag = $_GET['tag'] ?? null;
$sql = "SELECT * FROM posts";
$params = [];
if ($selected_tag) {
    $sql .= " WHERE tags LIKE ?";
    $params[] = '%' . $selected_tag . '%';
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$all_tags_stmt = $pdo->query("SELECT tags FROM posts WHERE tags IS NOT NULL AND tags != ''");
$all_tags_raw = $all_tags_stmt->fetchAll(PDO::FETCH_COLUMN);
$tags_array = [];
foreach ($all_tags_raw as $tag_string) {
    $tags_array = array_merge($tags_array, array_map('trim', explode(',', $tag_string)));
}
$unique_tags = array_unique(array_filter($tags_array));
sort($unique_tags);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Caly</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        :root{--brand-red:#ff4747;--brand-red-hover:#ff1a1a;--dark-bg:#121212;--dark-card:#1e1e1e;--dark-subtle:#2c2c2c;--text-light:#ccc;--text-white:#fff;--status-green: #1ae98d;}
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--dark-bg); color: var(--text-white); margin: 0; }
        .container { max-width: 1400px; margin: auto; padding: 20px; }
        
        /* --- MODIFIED: Header Layout for Centering --- */
        .header { 
            display: flex; 
            justify-content: center; /* This centers the h1 */
            align-items: center; 
            padding: 20px; 
            position: relative; /* This is crucial for positioning the side elements */
            max-width: 1400px; 
            margin: 0 auto; 
        }
        .header-left { 
            position: absolute; /* Takes element out of flow */
            left: 20px; 
        }
        .header-right { 
            position: absolute; /* Takes element out of flow */
            right: 20px; 
        }
        .header h1 { 
            font-size: 2.2rem; 
            margin: 0; 
            background: linear-gradient(90deg, var(--brand-red), #ff7070); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
            background-clip: text; 
            padding-bottom: 5px; 
            border-bottom: 3px solid var(--brand-red); 
            /* Add padding so long titles don't go under the icons */
            padding-left: 60px;
            padding-right: 60px;
        }

        .back-to-home-link { color: var(--text-light); font-size: 1.5rem; text-decoration: none; transition: all 0.3s ease; }
        .back-to-home-link:hover { color: var(--brand-red); transform: translateX(-5px); }
        .admin-login-icon { color: #aaa; font-size: 1.5rem; text-decoration: none; transition: all 0.3s ease; }
        .admin-login-icon:hover { color: var(--brand-red); transform: rotate(45deg); }
        
        .filter-toggle-wrapper { display: flex; justify-content: flex-end; margin-bottom: 20px; }
        .filter-toggle-btn { background: var(--dark-card); border: 1px solid var(--dark-subtle); color: var(--text-light); width: 50px; height: 50px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; cursor: pointer; transition: all 0.3s ease; }
        .filter-toggle-btn:hover, .filter-toggle-btn.active { background: var(--brand-red); color: var(--text-white); border-color: var(--brand-red); }
        .filter-controls { width: 100%; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; background: var(--dark-card); border: 1px solid var(--dark-subtle); max-height: 0; opacity: 0; overflow: hidden; padding: 0 15px; margin-top: -10px; border-radius: 10px; transition: max-height 0.4s ease-out, opacity 0.3s ease-out, padding 0.4s ease-out, margin-top 0.4s ease-out; }
        .filter-controls.active { max-height: 500px; opacity: 1; padding: 15px; margin-top: 15px; }
        .filter-controls > * { flex-grow: 1; }
        .filter-controls input, .filter-controls select, .filter-controls .tag-btn { padding: 12px; border-radius: 5px; border: 1px solid #444; background: #333; color: var(--text-white); font-family: 'Poppins', sans-serif; font-size: 1rem; transition: all .3s ease; }
        .filter-controls input:focus, .filter-controls select:focus { outline: none; border-color: var(--brand-red); box-shadow: 0 0 8px rgba(255,71,71,.5); }
        .filter-controls .tag-btn { background: var(--dark-subtle); cursor: pointer; text-align: center; }
        .filter-controls .tag-btn.active { background: var(--brand-red); color: white; border-color: var(--brand-red); }
        .filter-controls .tag-btn:hover { background: #444; }

        .tag-modal-backdrop { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s ease; }
        .tag-modal-backdrop.active { display: flex; opacity: 1; }
        .tag-modal-content { background: var(--dark-card); padding: 30px; border-radius: 10px; width: 90%; max-width: 600px; box-shadow: 0 0 25px rgba(255, 71, 71, 0.5); position: relative; border: 1px solid var(--dark-subtle); transform: scale(0.95); transition: transform 0.3s ease; }
        .tag-modal-backdrop.active .tag-modal-content { transform: scale(1); }
        .tag-modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .tag-modal-header h3 { margin: 0; font-size: 1.5rem; color: var(--brand-red); }
        #tagSearchInput { width: 100%; padding: 10px; margin-bottom: 20px; }
        .tag-modal-list { max-height: 40vh; overflow-y: auto; display: flex; flex-wrap: wrap; gap: 10px; padding: 5px; }
        .tag-modal-list a { text-decoration: none; }
        .tag-pill { display: inline-block; background: var(--dark-subtle); color: var(--text-light); padding: 8px 15px; border-radius: 20px; font-size: .9rem; transition: all .2s ease; }
        .tag-pill:hover, .tag-pill.active { background: var(--brand-red); color: var(--text-white); transform: translateY(-2px); }
        .tag-pill.all-tags { background: #555; }
        .close-modal { font-size: 2rem; color: #888; cursor: pointer; transition: color .2s ease; line-height: 1; } .close-modal:hover { color: #fff; }
        .gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 30px; margin-top: 20px; }
        .post { background: var(--dark-card); border: 1px solid var(--dark-subtle); border-radius: 12px; display: flex; flex-direction: column; transition: all 0.3s ease; overflow: hidden; animation: fadeIn 0.5s ease-out forwards; opacity: 0; position: relative; }
        .post:hover { transform: translateY(-10px); box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 0 0 2px var(--brand-red); border-color: var(--brand-red); }
        .post-image-link { display: block; overflow: hidden; background: #111; aspect-ratio: 16 / 9; position: relative; }
        .post-image-link img { width: 100%; height: 100%; display: block; object-fit: cover; transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .post:hover .post-image-link img { transform: scale(1.05); }
        .post-image-link::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 50%); opacity: 0; transition: opacity 0.3s ease; }
        .post:hover .post-image-link::after { opacity: 1; }
        .status-badge { position: absolute; top: 15px; right: 15px; padding: 5px 12px; border-radius: 5px; font-size: .8rem; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: .5px; backdrop-filter: blur(5px); }
        .status-completed { background-color: rgba(26, 233, 141, 0.2); border: 1px solid var(--status-green); color: var(--status-green); }
        .status-ongoing { background-color: rgba(255, 71, 71, 0.2); border: 1px solid var(--brand-red); color: var(--brand-red); }
        .post-rating-overlay { position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.6); padding: 4px 8px; border-radius: 5px; backdrop-filter: blur(5px); }
        .star-rating i { font-size: 0.85rem; color: #555; }
        .star-rating i.filled { color: var(--brand-red); }
        .post-content-wrapper { padding: 1.25rem; display: flex; flex-direction: column; flex-grow: 1; }
        .post-title-link { text-decoration: none; }
        .post h3 { color: var(--text-white); font-weight: 600; margin: 0 0 10px 0; font-size: 1.3rem; transition: color 0.3s ease; }
        .post:hover h3 { color: var(--brand-red); }
        .post-tags { margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 8px; }
        .post-tags .tag-pill { font-size: .75rem; padding: 4px 10px; margin: 0; }
        .description { color: var(--text-light); font-size: 0.9rem; line-height: 1.6; margin-bottom: 20px; flex-grow: 1; }
        .post-actions { margin-top: auto; padding-top: 15px; border-top: 1px solid var(--dark-subtle); display: flex; justify-content: space-between; align-items: center; }
        .button-link, .delete-post-btn, .edit-post-btn { font-family: 'Poppins', sans-serif; font-weight: 600; font-size: .9rem; text-decoration: none; padding: 8px 18px; border-radius: 5px; transition: all .2s ease; cursor: pointer; border: 1px solid var(--dark-subtle); }
        .button-link, .edit-post-btn { background: var(--dark-subtle); color: var(--text-light); }
        .button-link:hover, .edit-post-btn:hover { background: var(--brand-red); color: var(--text-white); border-color: var(--brand-red); }
        .delete-post-btn { background: transparent; color: #ff6b6b; border: 1px solid #ff6b6b; }
        .delete-post-btn:hover { background: #ff6b6b; color: var(--text-white); }
        .post-content-wrapper small { font-size: .8rem; color: #666; margin-top: 15px; text-align: center; width: 100%; }
        #noResultsMessage { display: none; text-align: center; padding: 50px 20px; color: var(--text-light); }
        #noResultsMessage i { font-size: 3rem; margin-bottom: 15px; color: var(--dark-subtle); }
        #noResultsMessage p { font-size: 1.2rem; margin: 0; }
        @keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .modal-backdrop{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.7);justify-content:center;align-items:center}.modal-backdrop.active{display:flex}.login-box{background:var(--dark-card);padding:30px;border-radius:10px;width:90%;max-width:400px;box-shadow:0 0 15px #ff4747cc;position:relative;color:var(--text-white);transition:box-shadow .3s ease}.admin-panel{max-width:500px;margin:20px auto 40px;box-shadow:0 0 15px #ff4747cc;background:var(--dark-card);padding:30px;border-radius:10px}.login-box form,.admin-panel form{display:flex;flex-direction:column}.login-box h3,.admin-panel h3{font-size:1.5rem;color:var(--brand-red);text-align:center;margin-top:0;margin-bottom:25px}input[type=text],input[type=password],textarea,input[type=number],select{width:100%;padding:12px;margin-bottom:15px;background:#333;border:1px solid #444;border-radius:5px;color:#fff;font-family:'Poppins',sans-serif;font-size:1rem;transition:all .3s ease}input[type=text]:focus,input[type=password]:focus,textarea:focus,input[type=number]:focus,select:focus{outline:none;border-color:var(--brand-red);box-shadow:0 0 8px rgba(255,71,71,.5)}textarea{resize:vertical}input[type=file]{font-family:'Poppins',sans-serif;background:#333;border:1px solid #444;border-radius:5px;padding:10px;margin-bottom:20px;cursor:pointer}input[type=file]::file-selector-button{font-weight:700;color:var(--brand-red);background-color:#1e1e1e;padding:8px 12px;border:none;border-radius:3px;cursor:pointer;margin-right:10px}form button[type=submit]{font-family:'Poppins',sans-serif;font-weight:600;font-size:1rem;padding:12px 20px;border-radius:5px;transition:all .2s ease;cursor:pointer;border:none;background:var(--brand-red);color:var(--text-white);text-transform:uppercase;letter-spacing:1px;margin-top:10px}form button[type=submit]:hover{background:#d62c2c;box-shadow:0 0 10px #d62c2c}.close-modal:hover{color:#fff}
        
        .mobile-action-bar { display: none; }
        
        @media (max-width: 600px) {
            .header-right { display: none; }
            .header-left .admin-login-icon { display: none; }
            .filter-toggle-wrapper { display: none; }
            .gallery-grid {grid-template-columns:1fr;}
            
            body { padding-bottom: 80px; }
            .mobile-action-bar { display: block; position: fixed; bottom: 0; left: 0; width: 100%; background-color: var(--dark-card); border-top: 1px solid var(--dark-subtle); padding: 5px 0; z-index: 100; }
            .mobile-action-bar-inner { display: flex; justify-content: space-around; align-items: center; }
            .action-bar-icon { color: var(--text-light); font-size: 1.3rem; text-decoration: none; padding: 10px 15px; border-radius: 8px; transition: color 0.2s ease; }
            .action-bar-icon.active, .action-bar-icon:active { color: var(--brand-red); }
        }
        @media (max-width:768px){.filter-controls{flex-direction:column; align-items: stretch;}}
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <a href="index.php" class="back-to-home-link" title="Back to Home"><i class="fa-solid fa-arrow-left"></i></a>
        </div>
        <h1>Discover</h1>
        <div class="header-right">
            <?php if (isset($_SESSION['admin'])): ?>
                <a href="?logout" class="admin-login-icon" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            <?php else: ?>
                <a href="#" class="admin-login-icon" id="adminLoginBtn" title="Admin Login"><i class="fa-solid fa-gear"></i></a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Rest of the HTML is unchanged -->
    <?php if (isset($_SESSION['admin'])): ?>
    <div class="container">
        <div class="admin-panel">
            <p style="text-align:center;">Logged in as admin.</p>
            <form method="POST" enctype="multipart/form-data" autocomplete="off">
                <h3>Create New Post</h3>
                <input type="text" name="title" placeholder="Title" required>
                <textarea name="content" placeholder="Description" required rows="4"></textarea>
                <input type="text" name="pc_link" placeholder="PC Link (Optional)">
                <input type="text" name="mobile_link" placeholder="Mobile Link (Optional)">
                <select name="status" required>
                    <option value="1">Completed</option>
                    <option value="0">Ongoing</option>
                </select>
                <input type="number" name="rating" placeholder="Rating (0-5)" min="0" max="5" value="0" required>
                <input type="text" name="tags" placeholder="Tags (comma-separated)">
                <label style="margin-bottom: 5px; font-size: 0.9rem; color: #ccc;">Cover Image:</label>
                <input type="file" name="image" accept="image/*">
                <button type="submit" name="create_post">Create Post</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!isset($_SESSION['admin'])): ?>
    <div class="modal-backdrop" id="loginModal">
        <form method="POST" class="login-box" autocomplete="off" id="loginForm">
            <span class="close-modal" id="closeModalBtn">×</span>
            <h3>Admin Login</h3>
            <?php if (!empty($error)) echo "<p style='color:red; text-align:center; margin-bottom: 15px;'>$error</p>"; ?>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>
    </div>
    <?php endif; ?>
    <div class="container">
        <div class="filter-toggle-wrapper">
            <div class="filter-toggle-btn" id="toggleFilterBtn" title="Show/Hide Filters">
                <i class="fa-solid fa-magnifying-glass"></i>
            </div>
        </div>
        <div class="filter-controls" id="filterControls">
            <input type="text" id="searchInput" placeholder="Search by title...">
            <select id="sortSelect">
                <option value="newest">Sort by Newest</option>
                <option value="oldest">Sort by Oldest</option>
                <option value="rating_desc">Sort by Rating (High-Low)</option>
                <option value="rating_asc">Sort by Rating (Low-High)</option>
                <option value="title_asc">Sort by Title (A-Z)</option>
                <option value="title_desc">Sort by Title (Z-A)</option>
            </select>
            <select id="statusSelect">
                <option value="all">All Statuses</option>
                <option value="1">Completed</option>
                <option value="0">Ongoing</option>
            </select>
            <div class="tag-btn <?= $selected_tag ? 'active' : '' ?>" id="tagFilterBtn">
                <i class="fa-solid fa-tags"></i> <?= $selected_tag ? htmlspecialchars($selected_tag) : 'Filter by Tag' ?>
            </div>
        </div>
        <div class="gallery-grid">
            <?php foreach ($posts as $post): ?>
            <div class="post" data-title="<?= strtolower(htmlspecialchars($post['title'])) ?>" data-rating="<?= (int)$post['rating'] ?>" data-date="<?= strtotime($post['created_at']) ?>" data-status="<?= (int)$post['status'] ?>">
                <a href="<?= htmlspecialchars($post['image'] ?? '') ?>" class="post-image-link" data-fancybox="gallery" data-caption="<?= htmlspecialchars($post['title']) ?>">
                    <?php if (!empty($post['image'])): ?><img src="<?= htmlspecialchars($post['image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>"><?php endif; ?>
                    <div class="post-rating-overlay">
                        <div class="star-rating">
                            <?php for ($i = 1; $i <= 5; $i++):?><i class="fa-solid fa-star <?= ($i <= $post['rating']) ? 'filled' : '' ?>"></i><?php endfor; ?>
                        </div>
                    </div>
                    <?php if ($post['status'] == 1): ?><div class="status-badge status-completed">Completed</div><?php else: ?><div class="status-badge status-ongoing">Ongoing</div><?php endif; ?>
                </a>
                <div class="post-content-wrapper">
                    <a href="single.php?post_id=<?= $post['id'] ?>" class="post-title-link">
                        <h3><?= htmlspecialchars($post['title']) ?></h3>
                    </a>
                    <div class="post-tags">
                        <?php if (!empty($post['tags'])): $post_tags = array_map('trim', explode(',', $post['tags']));?>
                            <?php foreach ($post_tags as $tag):?>
                                <a href="?tag=<?=urlencode($tag)?>"><span class="tag-pill"><?=htmlspecialchars($tag)?></span></a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="description"><?=nl2br(htmlspecialchars(substr($post['content'], 0, 100) . (strlen($post['content']) > 100 ? '...' : '')))?></div>
                    <div class="post-actions">
                        <a href="single.php?post_id=<?= $post['id'] ?>" class="button-link">View More</a>
                        <?php if (isset($_SESSION['admin'])):?>
                            <a href="edit.php?id=<?= $post['id'] ?>" class="edit-post-btn">Edit</a>
                            <form method="POST" onsubmit="return confirm('Are you sure?');" style="margin:0;">
                                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                <button type="submit" name="delete_post" class="delete-post-btn">Delete</button>
                            </form>
                        <?php endif;?>
                    </div>
                    <small>Posted on <?= date('Y-m-d', strtotime($post['created_at'])) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div id="noResultsMessage">
            <i class="fa-solid fa-box-open"></i>
            <p>No results found. Try adjusting your filters!</p>
        </div>
    </div>
    <div class="tag-modal-backdrop" id="tagModal">
        <div class="tag-modal-content">
            <div class="tag-modal-header">
                <h3>Filter by Tag</h3>
                <span class="close-modal" id="closeTagModal">×</span>
            </div>
            <input type="text" id="tagSearchInput" placeholder="Search tags...">
            <div class="tag-modal-list">
                <a href="gallery.php"><span class="tag-pill all-tags <?= !$selected_tag ? 'active' : '' ?>">All Tags</span></a>
                <?php foreach ($unique_tags as $tag): ?>
                    <a href="?tag=<?=urlencode($tag)?>" data-tag-name="<?= strtolower(htmlspecialchars($tag)) ?>">
                        <span class="tag-pill <?= $selected_tag === $tag ? 'active' : '' ?>"><?= htmlspecialchars($tag) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="mobile-action-bar">
        <div class="mobile-action-bar-inner">
            <a href="#" class="action-bar-icon" id="mobileToggleFilterBtn" title="Search & Filter">
                <i class="fa-solid fa-magnifying-glass"></i>
            </a>
            <a href="#" class="action-bar-icon" id="mobileTagFilterBtn" title="Filter by Tag">
                <i class="fa-solid fa-tags"></i>
            </a>
            <?php if (isset($_SESSION['admin'])): ?>
                <a href="?logout" class="action-bar-icon" title="Logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            <?php else: ?>
                <a href="#" class="action-bar-icon" id="mobileAdminLoginBtn" title="Admin Login">
                    <i class="fa-solid fa-gear"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <footer style="text-align:center; color:#444; padding:20px; font-size:0.9rem;">© <?= date('Y') ?> AVN Gallery — All Rights Reserved</footer>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById("searchInput");
        const sortSelect = document.getElementById("sortSelect");
        const statusSelect = document.getElementById("statusSelect");
        const galleryGrid = document.querySelector(".gallery-grid");
        const allPosts = Array.from(galleryGrid.querySelectorAll(".post"));
        const noResultsMessage = document.getElementById("noResultsMessage");
        
        function filterAndSort() {
            const searchTerm = searchInput.value.toLowerCase();
            const sortValue = sortSelect.value;
            const statusValue = statusSelect.value;
            let visiblePosts = 0;
            
            allPosts.forEach(post => {
                const title = post.dataset.title;
                const status = post.dataset.status;
                const matchesSearch = title.includes(searchTerm);
                const matchesStatus = (statusValue === "all" || status === statusValue);
                const isVisible = matchesSearch && matchesStatus;
                post.style.display = isVisible ? "flex" : "none";
                if(isVisible) visiblePosts++;
            });
            
            noResultsMessage.style.display = visiblePosts === 0 ? 'block' : 'none';
            
            let sortedPosts = allPosts.slice().sort((a, b) => {
                switch (sortValue) {
                    case "rating_desc": return b.dataset.rating - a.dataset.rating;
                    case "rating_asc": return a.dataset.rating - b.dataset.rating;
                    case "title_asc": return a.dataset.title.localeCompare(b.dataset.title);
                    case "title_desc": return b.dataset.title.localeCompare(a.dataset.title);
                    case "oldest": return a.dataset.date - b.dataset.date;
                    case "newest": default: return b.dataset.date - a.dataset.date;
                }
            });
            
            sortedPosts.forEach(post => galleryGrid.appendChild(post));
        }

        searchInput.addEventListener("keyup", filterAndSort);
        sortSelect.addEventListener("change", filterAndSort);
        statusSelect.addEventListener("change", filterAndSort);
        filterAndSort();

        const loginBtn = document.getElementById("adminLoginBtn");
        const loginModal = document.getElementById("loginModal");
        const closeModalBtn = document.getElementById("closeModalBtn");
        if (loginBtn) {
            loginBtn.addEventListener("click", (e) => { e.preventDefault(); loginModal.classList.add("active"); });
        }
        if(closeModalBtn) {
            closeModalBtn.addEventListener("click", () => loginModal.classList.remove("active"));
            loginModal.addEventListener("click", (e) => { if (e.target === loginModal) loginModal.classList.remove("active"); });
        }
        <?php if(!empty($error)):?>loginModal.classList.add("active");<?php endif;?>
        
        const tagFilterBtn = document.getElementById('tagFilterBtn');
        const tagModal = document.getElementById('tagModal');
        const closeTagModalBtn = document.getElementById('closeTagModal');
        const tagSearchInput = document.getElementById('tagSearchInput');
        const allTagLinks = tagModal.querySelectorAll('.tag-modal-list a');
        
        if (tagFilterBtn) {
            tagFilterBtn.addEventListener('click', () => tagModal.classList.add('active'));
        }
        if (closeTagModalBtn) {
            closeTagModalBtn.addEventListener('click', () => tagModal.classList.remove('active'));
            tagModal.addEventListener('click', e => { if(e.target === tagModal) tagModal.classList.remove('active'); });
        }
        if(tagSearchInput) {
            tagSearchInput.addEventListener('keyup', () => {
                const searchTerm = tagSearchInput.value.toLowerCase();
                allTagLinks.forEach(link => {
                    const tagName = link.dataset.tagName || '';
                    link.style.display = tagName.includes(searchTerm) || link.querySelector('.all-tags') ? 'inline-block' : 'none';
                });
            });
        }

        const toggleFilterBtn = document.getElementById('toggleFilterBtn');
        const filterControls = document.getElementById('filterControls');
        if(toggleFilterBtn) {
            toggleFilterBtn.addEventListener('click', () => {
                toggleFilterBtn.classList.toggle('active');
                filterControls.classList.toggle('active');
            });
        }

        const mobileToggleFilterBtn = document.getElementById('mobileToggleFilterBtn');
        const mobileTagFilterBtn = document.getElementById('mobileTagFilterBtn');
        const mobileAdminLoginBtn = document.getElementById('mobileAdminLoginBtn');
        
        if (mobileToggleFilterBtn) {
            mobileToggleFilterBtn.addEventListener('click', (e) => {
                e.preventDefault();
                mobileToggleFilterBtn.classList.toggle('active');
                filterControls.classList.toggle('active');
            });
        }

        if (mobileTagFilterBtn) {
            mobileTagFilterBtn.addEventListener('click', (e) => {
                e.preventDefault();
                tagModal.classList.add('active');
            });
        }

        if (mobileAdminLoginBtn) {
            mobileAdminLoginBtn.addEventListener('click', (e) => {
                e.preventDefault();
                loginModal.classList.add('active');
            });
        }
    });
    </script>
</body>
</html>