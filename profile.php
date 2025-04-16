<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Get user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get user videos
    $videos_stmt = $pdo->prepare("SELECT videos.*, 
                                (SELECT COUNT(*) FROM likes WHERE likes.video_id = videos.id) as like_count,
                                (SELECT COUNT(*) FROM comments WHERE comments.video_id = videos.id) as comment_count
                                FROM videos 
                                WHERE user_id = ? 
                                ORDER BY upload_date DESC");
    $videos_stmt->execute([$user_id]);
    $videos = $videos_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total views, likes and videos
    $stats_stmt = $pdo->prepare("SELECT 
                                  SUM(views) as total_views,
                                  COUNT(*) as total_videos,
                                  (SELECT COUNT(*) FROM likes JOIN videos ON likes.video_id = videos.id WHERE videos.user_id = ?) as total_likes
                                FROM videos 
                                WHERE user_id = ?");
    $stats_stmt->execute([$user_id, $user_id]);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - YouTubeClone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', Arial, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #0a0a0a;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 1px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            color: #ff0000;
            text-decoration: none;
            font-size: 22px;
            font-weight: bold;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .search-container {
            display: flex;
            width: 40%;
        }
        
        .search-container input {
            width: 85%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 3px 0 0 3px;
            outline: none;
        }
        
        .search-container button {
            padding: 10px 15px;
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-left: none;
            border-radius: 0 3px 3px 0;
            cursor: pointer;
        }
        
        .menu {
            display: flex;
            align-items: center;
        }
        
        .menu a {
            margin-left: 15px;
            text-decoration: none;
            color: #606060;
            font-size: 14px;
        }
        
        .menu a:hover {
            color: #ff0000;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #ddd;
            margin-right: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #606060;
            font-size: 30px;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .username {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #0a0a0a;
        }
        
        .joined-date {
            color: #606060;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stats {
            display: flex;
            color: #606060;
            font-size: 14px;
        }
        
        .stat {
            margin-right: 20px;
            display: flex;
            align-items: center;
        }
        
        .stat i {
            margin-right: 5px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #606060;
            position: relative;
        }
        
        .tab.active {
            color: #0a0a0a;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #ff0000;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .video-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            cursor: pointer;
        }
        
        .video-card:hover {
            transform: translateY(-5px);
        }
        
        .thumbnail {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #eee;
        }
        
        .video-info {
            padding: 15px;
        }
        
        .video-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #0a0a0a;
        }
        
        .video-meta {
            display: flex;
            justify-content: space-between;
            color: #606060;
            font-size: 14px;
        }
        
        .video-stats {
            display: flex;
        }
        
        .video-stat {
            margin-right: 10px;
            display: flex;
            align-items: center;
        }
        
        .video-stat i {
            margin-right: 5px;
            font-size: 12px;
        }
        
        .no-videos {
            text-align: center;
            padding: 50px;
            color: #606060;
            font-size: 18px;
        }
        
        .upload-btn {
            position: fixed;
            right: 30px;
            bottom: 30px;
            background-color: #ff0000;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }
        
        .upload-btn:hover {
            background-color: #d40000;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 10px;
            }
            
            .search-container {
                width: 50%;
            }
            
            .profile-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .profile-avatar {
                margin-bottom: 15px;
            }
            
            .videos-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
            
            .logo span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">
            <i class="fab fa-youtube"></i>
            <span>YouTubeClone</span>
        </a>
        
        <div class="search-container">
            <input type="text" placeholder="Search">
            <button><i class="fas fa-search"></i></button>
        </div>
        
        <div class="menu">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </header>
    
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user"></i>
            </div>
            
            <div class="profile-info">
                <h1 class="username"><?php echo htmlspecialchars($user['username']); ?></h1>
                <div class="joined-date">Joined <?php echo date('F Y', strtotime($user['created_at'])); ?></div>
                
                <div class="stats">
                    <div class="stat">
                        <i class="fas fa-video"></i>
                        <span><?php echo $stats['total_videos'] ?? 0; ?> videos</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-eye"></i>
                        <span><?php echo number_format($stats['total_views'] ?? 0); ?> views</span>
                    </div>
                    <div class="stat">
                        <i class="fas fa-thumbs-up"></i>
                        <span><?php echo number_format($stats['total_likes'] ?? 0); ?> likes</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="tabs">
            <div class="tab active" data-tab="videos">Videos</div>
            <div class="tab" data-tab="about">About</div>
        </div>
        
        <div class="tab-content active" id="videos-tab">
            <?php if(empty($videos)): ?>
                <div class="no-videos">You haven't uploaded any videos yet.</div>
            <?php else: ?>
                <div class="videos-grid">
                    <?php foreach($videos as $video): ?>
                        <div class="video-card" onclick="location.href='watch.php?id=<?php echo $video['id']; ?>'">
                            <img src="<?php echo (!empty($video['thumbnail'])) ? $video['thumbnail'] : 'https://via.placeholder.com/300x180?text=Video+Thumbnail'; ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="thumbnail">
                            <div class="video-info">
                                <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                                <div class="video-meta">
                                    <span><?php echo number_format($video['views']); ?> views</span>
                                    <span><?php echo date('j M Y', strtotime($video['upload_date'])); ?></span>
                                </div>
                                <div class="video-stats">
                                    <div class="video-stat">
                                        <i class="fas fa-thumbs-up"></i>
                                        <span><?php echo $video['like_count']; ?></span>
                                    </div>
                                    <div class="video-stat">
                                        <i class="fas fa-comment"></i>
                                        <span><?php echo $video['comment_count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="about-tab">
            <h2 style="margin-bottom: 20px;">About</h2>
            <p>
                <strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?><br>
                <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?><br>
                <strong>Joined:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?><br>
                <strong>Total Videos:</strong> <?php echo $stats['total_videos'] ?? 0; ?><br>
                <strong>Total Views:</strong> <?php echo number_format($stats['total_views'] ?? 0); ?><br>
                <strong>Total Likes:</strong> <?php echo number_format($stats['total_likes'] ?? 0); ?>
            </p>
        </div>
        
        <a href="upload.php" class="upload-btn">
            <i class="fas fa-plus"></i>
        </a>
    </div>
    
    <script>
        // Tab functionality
        const tabs = document.querySelectorAll('.tab');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs and contents
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById(this.getAttribute('data-tab') + '-tab').classList.add('active');
            });
        });
    </script>
</body>
</html>
