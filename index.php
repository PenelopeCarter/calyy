<?php
// Start the session to check for admin login from gallery.php
session_start();

// Define the path to your data file and load the data
$dataFile = 'site_data.json';
$siteData = json_decode(file_get_contents($dataFile), true);

// Set admin credentials (must match gallery.php)
$admin_user = "persiankat123";
$admin_pass = "eternum best game";

// Handle login attempt from the modal
if (isset($_POST['login_index'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['admin'] = true;
        header("Location: index.php"); // Refresh the page to show admin state
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>After Credits | An AVN Collection</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <style>
        :root{--brand-red:#ff4747;--brand-red-hover:#ff1a1a;--dark-bg:#121212;--dark-card:#1e1e1e;--dark-subtle:#2c2c2c;--text-light:#ccc;--text-white:#fff;}
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: "Poppins", sans-serif; background-color: var(--dark-bg); color: var(--text-white); overflow: hidden; }

        .hero-container { height: 100vh; width: 100%; display: flex; justify-content: center; align-items: center; text-align: center; position: relative; overflow: hidden; }
        #bg-video {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 100%;
            min-height: 100%;
            z-index: 1;
            object-fit: cover;
        }
        .hero-container::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient( circle at var(--mouse-x, 50%) var(--mouse-y, 50%), rgba(255, 71, 71, 0.08) 0%, rgba(255, 71, 71, 0.0) 25% ), linear-gradient( to top, rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.3) );
            z-index: 2;
            transition: background-image 0.1s ease-out;
        }
        
        .hero-container::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 3;
            box-shadow: inset 0 0 120px 40px rgba(0,0,0,0.7);
            pointer-events: none;
        }

        .page-header { position: absolute; top: 0; left: 0; width: 100%; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; z-index: 5; }
        .site-title { font-weight: 700; font-size: 1.2rem; color: var(--text-white); text-decoration: none; letter-spacing: 1px; }
        .site-title span { color: var(--brand-red); }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .social-item { display: flex; align-items: center; gap: 8px; color: var(--text-light); font-size: 0.9rem; text-decoration: none; transition: color 0.2s, transform 0.2s; }
        .social-item:hover { color: var(--text-white); transform: translateY(-2px); }
        .social-item i { font-size: 1.4rem; }
        .social-item .discord-icon { color: #7289da; }
        .social-item .reddit-icon { color: #ff4500; }
        .admin-btn { font-size: 1.5rem; color: #aaa; text-decoration: none; transition: all 0.3s ease; background: none; border: none; cursor: pointer; padding: 5px; }
        .admin-btn:hover { color: var(--brand-red); transform: rotate(45deg) scale(1.1); }
        
        .hero-content { position: relative; z-index: 4; max-width: 800px; padding: 20px; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes softGlow { 0%, 100% { text-shadow: 0 0 8px rgba(255, 71, 71, 0.6), 0 0 16px rgba(255, 71, 71, 0.4); } 50% { text-shadow: 0 0 16px rgba(255, 71, 71, 0.9), 0 0 32px rgba(255, 71, 71, 0.6); } }
        .hero-content h1 { font-family: "Bebas Neue", sans-serif; font-size: 4.5rem; font-weight: 700; margin-bottom: 1rem; background: linear-gradient(90deg, var(--brand-red), #ff7070); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; letter-spacing: 3px; animation: softGlow 3s ease-in-out infinite, fadeInUp 0.8s ease-out 0.5s forwards; opacity: 0; }
        .hero-content p { font-size: 1.3rem; color: var(--text-light); margin-bottom: 2.5rem; animation: fadeInUp 0.8s ease-out 0.7s forwards; opacity: 0; }
        
        .hero-button {
            display: inline-flex;
            align-items: center;
            gap: 10px; 
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 1px;
            padding: 15px 40px;
            border-radius: 8px;
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            cursor: pointer;
            color: var(--text-white);
            text-decoration: none;
            background-image: linear-gradient(to bottom, #ff4747, #d83030);
            border: 1px solid #ff6b6b;
            box-shadow: 0 0 20px rgba(255, 71, 71, 0.5), inset 0 1px 2px rgba(255, 255, 255, 0.2);
            text-shadow: 0 0 5px rgba(0,0,0,0.5);
            animation: fadeInUp 0.8s ease-out 0.9s forwards;
            opacity: 0;
            position: relative;
            overflow: visible; 
        }
        .hero-button span {
            transition: transform 0.4s ease;
        }
        .hero-button .emoji {
            font-size: 1.4rem; 
            transition: transform 0.4s ease;
        }
        .hero-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(255, 71, 71, 0.8), inset 0 1px 2px rgba(255, 255, 255, 0.2);
        }
        .hero-button:hover .emoji {
            transform: scale(1.3) rotate(-15deg); 
        }
        
        .admin-panel { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 1001; display: none; justify-content: center; align-items: center; padding: 20px; }
        .admin-panel.active { display: flex; }
        .modal-box { background: var(--dark-card); padding: 30px; border-radius: 10px; width: 90%; max-width: 400px; box-shadow: 0 0 25px rgba(255, 71, 71, 0.5); position: relative; color: var(--text-white); text-align: center; border: 1px solid var(--dark-subtle); }
        .modal-box h2 { font-size: 1.5rem; color: var(--brand-red); margin-top: 0; margin-bottom: 25px; }
        .modal-box input, .modal-box button { width: 100%; padding: 12px; margin-bottom: 15px; background: #333; border: 1px solid #444; border-radius: 5px; color: #fff; font-family: 'Poppins', sans-serif; font-size: 1rem; }
        .modal-box input:focus { outline: none; border-color: var(--brand-red); box-shadow: 0 0 8px rgba(255, 71, 71, .5); }
        .modal-box button { background: var(--brand-red); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; border: none; cursor: pointer; }
        .close-admin { position: absolute; top: 10px; right: 15px; font-size: 2rem; color: #888; cursor: pointer; transition: color .2s ease; line-height: 1; }
        .close-admin:hover { color: #fff; }
        #preloader { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--dark-bg); z-index: 9999; display: flex; justify-content: center; align-items: center; transition: opacity 0.75s ease, visibility 0.75s ease; }
        #preloader.loaded { opacity: 0; visibility: hidden; }
        .preloader-ring { width: 60px; height: 60px; border: 4px solid rgba(255, 71, 71, 0.2); border-top-color: var(--brand-red); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .toast { position: fixed; bottom: -100px; left: 50%; transform: translateX(-50%); background-color: var(--brand-red); color: var(--text-white); padding: 12px 25px; border-radius: 5px; font-weight: 600; font-size: 0.9rem; z-index: 10000; transition: bottom 0.5s ease-in-out; box-shadow: 0 0 20px rgba(255, 71, 71, 0.5); }
        .toast.show { bottom: 30px; }

        @media (max-width: 768px) {
            .hero-content h1 { font-size: 3rem; }
            .hero-content p { font-size: 1.1rem; }
            .social-item span { display: none; }
            .site-title { font-size: 1rem; }
            .page-header { padding: 15px; }
            #bg-video { width: auto; height: 100%; transform: translate(-60%, -50%); }
        }
    </style>
</head>
<body>
    <div id="preloader"><div class="preloader-ring"></div></div>

    <div class="hero-container">
        <video autoplay loop muted playsinline id="bg-video">
            <source src="eternum.mp4" type="video/mp4" />
        </video>

        <header class="page-header">
            <a href="index.php" class="site-title">I<span>SWEAR</span></a>
            <div class="header-right">
                <a href="#" class="social-item" id="discord-display" title="Discord"><i class="fab fa-discord discord-icon"></i><span class="social-text"></span></a>
                <a href="#" class="social-item" id="reddit-display" title="Reddit"><i class="fab fa-reddit reddit-icon"></i><span class="social-text"></span></a>
                <?php if (isset($_SESSION['admin'])): ?>
                    <button class="admin-btn" onclick="showSocialsPanel()" title="Edit Socials"><i class="fa-solid fa-pencil"></i></button>
                <?php else: ?>
                    <button class="admin-btn" onclick="showLoginPanel()" title="Admin Login"><i class="fa-solid fa-gear"></i></button>
                <?php endif; ?>
            </div>
        </header>

        <div class="hero-content">
            <h1>I Only</h1>
            <!-- --- MODIFIED LINE --- -->
            <p>Play for the plot😭🙏🏿</p>
            <a href="gallery.php" class="hero-button">
                <span>Touch Me</span>
                <span class="emoji">🥺</span>
            </a>
        </div>
    </div>

    <div class="admin-panel" id="adminPanel">
        <div class="modal-box">
            <span class="close-admin" onclick="hideAdminPanel()">×</span>
            <form id="loginForm" method="POST" style="display: none;"></form>
            <div id="socialsEditArea" style="display: none;"></div>
        </div>
    </div>
    <div id="toast-notification" class="toast"></div>

    <script>
        let siteData = <?php echo json_encode($siteData); ?>;
        const adminPanel = document.getElementById('adminPanel');
        const loginForm = document.getElementById('loginForm');
        const socialsEditArea = document.getElementById('socialsEditArea');
        
        function renderAllContent() {
            const discordDisplay = document.getElementById('discord-display');
            discordDisplay.querySelector('span.social-text').textContent = siteData.discordName || '';
            discordDisplay.onclick = (e) => { e.preventDefault(); if (siteData.discordUserId) { window.open(`https://discord.com/users/${siteData.discordUserId}`, '_blank'); } else { showToast('Discord User ID not set.'); } };
            
            const redditDisplay = document.getElementById('reddit-display');
            redditDisplay.querySelector('span.social-text').textContent = siteData.redditName || '';
            redditDisplay.onclick = (e) => { e.preventDefault(); if (siteData.redditUrl) { window.open(siteData.redditUrl, '_blank'); } };
        }
        async function saveData(newData) { 
            try { 
                const response = await fetch('save_data.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(newData) }); 
                if (!response.ok) throw new Error(`Server error: ${response.status}`); 
                const result = await response.json(); 
                if (result.success) { 
                    siteData = newData; 
                    renderAllContent(); 
                    showToast('Changes saved successfully!'); 
                    return true; 
                } else { 
                    showToast('Failed to save: ' + (result.error || 'Unknown error')); 
                    return false; 
                } 
            } catch (error) { 
                console.error("Save Data Error:", error); 
                showToast('An error occurred while saving.'); 
                return false; 
            } 
        }
        function showToast(message) { 
            const toast = document.getElementById('toast-notification'); 
            toast.textContent = message; 
            toast.classList.add('show'); 
            setTimeout(() => { toast.classList.remove('show'); }, 2800); 
        }
        function hideAdminPanel() { adminPanel.classList.remove('active'); }
        function showLoginPanel() { 
            loginForm.innerHTML = `<h2>Admin Login</h2><?php if (!empty($error)) echo "<p style=\'color:red; margin-bottom:15px;\'>$error</p>"; ?><input type="text" name="username" placeholder="Username" required><input type="password" name="password" placeholder="Password" required><button type="submit" name="login_index">Login</button>`; 
            loginForm.style.display = 'block'; 
            socialsEditArea.style.display = 'none'; 
            adminPanel.classList.add('active'); 
        }
        function showSocialsPanel() { 
            socialsEditArea.innerHTML = `<h2>Edit Socials</h2><div class="edit-section"><input type="text" id="editDiscordName" placeholder="Discord Display Name" value="${siteData.discordName || ''}"/><input type="text" id="editDiscordId" placeholder="Discord User ID" value="${siteData.discordUserId || ''}"/><input type="text" id="editRedditUsername" placeholder="Reddit Username" value="${siteData.redditName || ''}"/></div><button onclick="saveSocialChanges()">Save Socials</button>`; 
            loginForm.style.display = 'none'; 
            socialsEditArea.style.display = 'block'; 
            adminPanel.classList.add('active'); 
        }
        async function saveSocialChanges() { 
            const newDiscordName = document.getElementById('editDiscordName').value; 
            const newDiscordId = document.getElementById('editDiscordId').value; 
            const newRedditName = document.getElementById('editRedditUsername').value; 
            const newRedditUrl = newRedditName ? `https://www.reddit.com/user/${newRedditName}` : ''; 
            const newData = { ...siteData, discordName: newDiscordName, discordUserId: newDiscordId, redditName: newRedditName, redditUrl: newRedditUrl }; 
            const success = await saveData(newData); 
            if (success) hideAdminPanel(); 
        }

        window.addEventListener('load', () => {
            document.getElementById('preloader').classList.add('loaded');
            renderAllContent();
            const video = document.getElementById('bg-video');
            if (video) {
                try { video.play(); } 
                catch (error) { console.warn("Video playback was prevented by the browser.", error); }
            }
        });
        
        const heroContainer = document.querySelector('.hero-container');
        heroContainer.addEventListener('mousemove', e => {
            const rect = heroContainer.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            heroContainer.style.setProperty('--mouse-x', `${x}px`);
            heroContainer.style.setProperty('--mouse-y', `${y}px`);
        });
        
        <?php if (!empty($error)): ?> showLoginPanel(); <?php endif; ?>
    </script>
</body>
</html>