<?php
session_start();
require_once 'db.php';

// Get trending videos (most viewed)
try {
    $stmt = $pdo->query("SELECT videos.*, users.username, 
                        (SELECT COUNT(*) FROM likes WHERE likes.video_id = videos.id) as like_count
                        FROM videos 
                        JOIN users ON videos.user_id = users.id 
                        ORDER BY views DESC 
                        LIMIT 12");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error fetching videos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trending - YouTubeClone</title>
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
        
        .menu .login-btn {
            background-color: #065fd4;
            color: white;
            padding: 8px 15px;
            border-radius: 3px;
            margin-left: 15px;
        }
        
        .menu .login-btn:hover {
            background-color: #0356c3;
            color: white;
        }
        
        .categories {
            display: flex;
            overflow-x: auto;
            padding: 15px;
            background-color: white;
            margin-bottom: 20px;
            scrollbar-width: none;
        }
        
        .categories::-webkit-scrollbar {
            display: none;
        }
        
        .category {
            margin-right: 15px;
            padding: 8px 15px;
            background-color: #f1f1f1;
            border-radius: 16px;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .category:hover, .category.active {
            background-color: #e5e5e5;
        }
        
        .videos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
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
            align-items: center;
            color: #606060;
            font-size: 14px;
        }
        
        .video-meta img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .views-time {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            color: #606060;
            font-size: 14px;
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
        
        .section-title {
            padding: 0 20px;
            margin-top: 30px;
            font-size: 22px;
            color: #0a0a0a;
        }
        
        .no-videos {
            text-align: center;
            padding: 50px;
            color: #606060;
            font-size: 18px;
        }
        
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 10px;
            color: #606060;
        }
        
        .trending-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(255, 0, 0, 0.8);
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .video-card {
            position: relative;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 10px;
            }
            
            .search-container {
                width: 50%;
            }
            
            .videos-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                padding: 10px;
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
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <?php else: ?>
                <a href="login.php" class="login-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                <a href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
            <?php endif; ?>
        </div>
    </header>
    
    <div class="categories">
        <div class="category" onclick="location.href='index.php'">All</div>
        <div class="category active">Trending</div>
        <div class="category" onclick="location.href='recent.php'">Recently Uploaded</div>
        <div class="category">Music</div>
        <div class="category">Gaming</div>
        <div class="category">Movies</div>
        <div class="category">News</div>
        <div class="category">Sports</div>
        <div class="category">Education</div>
        <div class="category">Comedy</div>
    </div>
    
    <h2 class="section-title">Trending Videos</h2>
    
    <div class="videos-container">
        <?php if(empty($videos)): ?>
            <div class="no-videos">No trending videos available yet.</div>
        <?php else: ?>
            <?php foreach($videos as $video): ?>
                <div class="video-card" onclick="location.href='watch.php?id=<?php echo $video['id']; ?>'">
                    <div class="trending-tag"><i class="fas fa-fire"></i> Trending</div>
                    <img src="<?php echo (!empty($video['thumbnail'])) ? $video['thumbnail'] : 'https://via.placeholder.com/300x180?text=Video+Thumbnail'; ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" class="thumbnail">
                    <div class="video-info">
                        <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                        <div class="video-meta">
                            <div class="user-avatar"><i class="fas fa-user"></i></div>
                            <span><?php echo htmlspecialchars($video['username']); ?></span>
                        </div>
                        <div class="views-time">
                            <span><?php echo number_format($video['views']); ?> views</span>
                            <span><?php echo date('j M Y', strtotime($video['upload_date'])); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if(isset($_SESSION['user_id'])): ?>
        <a href="upload.php" class="upload-btn">
            <i class="fas fa-plus"></i>
        </a>
    <?php endif; ?>

    <script>
        // Script for category selection
        const categories = document.querySelectorAll('.category');
        
        categories.forEach(category => {
            category.addEventListener('click', function() {
                categories.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
