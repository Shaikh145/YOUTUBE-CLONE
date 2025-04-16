<?php
session_start();
require_once 'db.php';

// Check if video ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$video_id = $_GET['id'];
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

try {
    // Get video details
    $stmt = $pdo->prepare("SELECT videos.*, users.username 
                          FROM videos 
                          JOIN users ON videos.user_id = users.id 
                          WHERE videos.id = ?");
    $stmt->execute([$video_id]);
    
    if($stmt->rowCount() == 0) {
        header('Location: index.php');
        exit;
    }
    
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Increment view count
    $update_stmt = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $update_stmt->execute([$video_id]);
    
    // Check if user has liked the video
    $liked = false;
    if($user_id) {
        $like_stmt = $pdo->prepare("SELECT id FROM likes WHERE video_id = ? AND user_id = ?");
        $like_stmt->execute([$video_id, $user_id]);
        $liked = $like_stmt->rowCount() > 0;
    }
    
    // Get like count
    $like_count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE video_id = ?");
    $like_count_stmt->execute([$video_id]);
    $like_count = $like_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get comments
    $comments_stmt = $pdo->prepare("SELECT comments.*, users.username 
                                  FROM comments 
                                  JOIN users ON comments.user_id = users.id 
                                  WHERE comments.video_id = ? 
                                  ORDER BY comments.created_at DESC");
    $comments_stmt->execute([$video_id]);
    $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get related videos (excluding current video)
    $related_stmt = $pdo->prepare("SELECT videos.*, users.username, 
                                  (SELECT COUNT(*) FROM likes WHERE likes.video_id = videos.id) as like_count
                                  FROM videos 
                                  JOIN users ON videos.user_id = users.id 
                                  WHERE videos.id != ? 
                                  ORDER BY RAND() 
                                  LIMIT 6");
    $related_stmt->execute([$video_id]);
    $related_videos = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title']); ?> - YouTubeClone</title>
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
        
        .main-content {
            display: flex;
            max-width: 1400px;
            margin: 20px auto;
            padding: 0 20px;
        }
        
        .video-container {
            flex: 3;
            margin-right: 20px;
        }
        
        .video-player {
            width: 100%;
            aspect-ratio: 16 / 9;
            background-color: black;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .video-player video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .video-info {
            margin-top: 15px;
        }
        
        .video-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }
        
        .video-stats {
            display: flex;
            align-items: center;
            color: #606060;
            font-size: 14px;
        }
        
        .views-count {
            margin-right: 15px;
        }
        
        .upload-date {
            margin-right: 15px;
        }
        
        .video-actions {
            display: flex;
        }
        
        .like-btn {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            color: #606060;
            font-size: 14px;
            cursor: pointer;
        }
        
        .like-btn.active {
            color: #065fd4;
        }
        
        .like-btn i {
            margin-right: 5px;
        }
        
        .like-count {
            margin-left: 5px;
        }
        
        .channel-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .channel-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background-color: #ddd;
            margin-right: 15px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #606060;
        }
        
        .channel-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #0a0a0a;
        }
        
        .video-description {
            color: #606060;
            font-size: 14px;
            margin-bottom: 20px;
            white-space: pre-line;
            max-height: 80px;
            overflow: hidden;
            transition: max-height 0.3s;
        }
        
        .video-description.expanded {
            max-height: 1000px;
        }
        
        .show-more-btn {
            color: #606060;
            font-size: 14px;
            font-weight: 600;
            background: none;
            border: none;
            cursor: pointer;
            margin-bottom: 20px;
        }
        
        .comments-section {
            margin-top: 30px;
        }
        
        .comments-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .comment-form {
            display: flex;
            margin-bottom: 30px;
        }
        
        .comment-form textarea {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: none;
            height: 60px;
            transition: border-color 0.3s;
        }
        
        .comment-form textarea:focus {
            border-color: #1a73e8;
            outline: none;
        }
        
        .comment-form button {
            margin-left: 10px;
            background-color: #065fd4;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0 15px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .comment-form button:hover {
            background-color: #0356c3;
        }
        
        .login-to-comment {
            color: #606060;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .login-to-comment a {
            color: #065fd4;
            text-decoration: none;
        }
        
        .comments-list {
            margin-bottom: 30px;
        }
        
        .comment {
            display: flex;
            margin-bottom: 20px;
        }
        
        .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 15px;
            background-color: #ddd;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #606060;
        }
        
        .comment-content {
            flex: 1;
        }
        
        .comment-header {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .comment-username {
            font-weight: 600;
            margin-right: 10px;
            color: #0a0a0a;
        }
        
        .comment-time {
            font-size: 12px;
            color: #606060;
        }
        
        .comment-text {
            color: #0a0a0a;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .no-comments {
            color: #606060;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .related-videos {
            flex: 1;
        }
        
        .related-video {
            display: flex;
            margin-bottom: 15px;
            text-decoration: none;
            color: inherit;
        }
        
        .related-thumbnail {
            width: 168px;
            height: 94px;
            background-color: #eee;
            border-radius: 8px;
            margin-right: 10px;
            object-fit: cover;
        }
        
        .related-info {
            flex: 1;
        }
        
        .related-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #0a0a0a;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .related-channel {
            font-size: 12px;
            color: #606060;
            margin-bottom: 5px;
        }
        
        .related-meta {
            font-size: 12px;
            color: #606060;
            display: flex;
        }
        
        .related-views {
            margin-right: 5px;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            display: none;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            text-align: center;
            display: none;
        }
        
        @media (max-width: 1024px) {
            .main-content {
                flex-direction: column;
            }
            
            .video-container {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .related-videos {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                gap: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 10px;
            }
            
            .search-container {
                width: 50%;
            }
            
            .logo span {
                display: none;
            }
            
            .related-thumbnail {
                width: 120px;
                height: 68px;
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
    
    <div class="main-content">
        <div class="video-container">
            <div class="video-player">
                <video controls autoplay>
                    <source src="<?php echo htmlspecialchars($video['file_path']); ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
            
            <div class="video-info">
                <h1 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h1>
                
                <div class="video-meta">
                    <div class="video-stats">
                        <span class="views-count"><?php echo number_format($video['views']); ?> views</span>
                        <span class="upload-date"><?php echo date('M j, Y', strtotime($video['upload_date'])); ?></span>
                    </div>
                    
                    <div class="video-actions">
                        <button class="like-btn <?php echo $liked ? 'active' : ''; ?>" id="like-btn" data-video-id="<?php echo $video_id; ?>">
                            <i class="fas fa-thumbs-up"></i>
                            <span>Like</span>
                            <span class="like-count"><?php echo $like_count; ?></span>
                        </button>
                    </div>
                </div>
                
                <div class="channel-info">
                    <div class="channel-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="channel-name"><?php echo htmlspecialchars($video['username']); ?></div>
                    </div>
                </div>
                
                <div class="video-description" id="description">
                    <?php echo nl2br(htmlspecialchars($video['description'])); ?>
                </div>
                
                <?php if(!empty($video['description'])): ?>
                    <button class="show-more-btn" id="show-more-btn">Show more</button>
                <?php endif; ?>
                
                <div class="comments-section">
                    <h3 class="comments-header"><?php echo count($comments); ?> Comments</h3>
                    
                    <div id="error-message" class="error-message"></div>
                    <div id="success-message" class="success-message"></div>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form class="comment-form" id="comment-form">
                            <textarea id="comment-text" placeholder="Add a comment..."></textarea>
                            <button type="submit">Comment</button>
                        </form>
                    <?php else: ?>
                        <div class="login-to-comment">
                            <a href="login.php">Login</a> to add a comment
                        </div>
                    <?php endif; ?>
                    
                    <div class="comments-list" id="comments-list">
                        <?php if(empty($comments)): ?>
                            <div class="no-comments">No comments yet. Be the first to comment!</div>
                        <?php else: ?>
                            <?php foreach($comments as $comment): ?>
                                <div class="comment">
                                    <div class="comment-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="comment-content">
                                        <div class="comment-header">
                                            <span class="comment-username"><?php echo htmlspecialchars($comment['username']); ?></span>
                                            <span class="comment-time"><?php echo date('j M Y', strtotime($comment['created_at'])); ?></span>
                                        </div>
                                        <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="related-videos">
            <h3 style="margin-bottom: 15px;">Related Videos</h3>
            <?php foreach($related_videos as $related): ?>
                <a href="watch.php?id=<?php echo $related['id']; ?>" class="related-video">
                    <img src="<?php echo (!empty($related['thumbnail'])) ? $related['thumbnail'] : 'https://via.placeholder.com/168x94?text=Video+Thumbnail'; ?>" alt="<?php echo htmlspecialchars($related['title']); ?>" class="related-thumbnail">
                    <div class="related-info">
                        <div class="related-title"><?php echo htmlspecialchars($related['title']); ?></div>
                        <div class="related-channel"><?php echo htmlspecialchars($related['username']); ?></div>
                        <div class="related-meta">
                            <span class="related-views"><?php echo number_format($related['views']); ?> views</span>
                            <span class="related-date"><?php echo date('j M Y', strtotime($related['upload_date'])); ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <script>
        // Show more description
        const description = document.getElementById('description');
        const showMoreBtn = document.getElementById('show-more-btn');
        
        if(showMoreBtn) {
            showMoreBtn.addEventListener('click', function() {
                description.classList.toggle('expanded');
                this.textContent = description.classList.contains('expanded') ? 'Show less' : 'Show more';
            });
        }
        
        // Like functionality
        const likeBtn = document.getElementById('like-btn');
        const likeCount = likeBtn.querySelector('.like-count');
        
        likeBtn.addEventListener('click', function() {
            <?php if(isset($_SESSION['user_id'])): ?>
                const videoId = this.getAttribute('data-video-id');
                
                fetch('add_like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ video_id: videoId })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.status === 'success') {
                        this.classList.toggle('active');
                        likeCount.textContent = data.like_count;
                    } else {
                        document.getElementById('error-message').textContent = data.message;
                        document.getElementById('error-message').style.display = 'block';
                        setTimeout(() => {
                            document.getElementById('error-message').style.display = 'none';
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            <?php else: ?>
                window.location.href = 'login.php';
            <?php endif; ?>
        });
        
        // Comment functionality
        const commentForm = document.getElementById('comment-form');
        const commentsList = document.getElementById('comments-list');
        
        <?php if(isset($_SESSION['user_id'])): ?>
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const commentText = document.getElementById('comment-text').value.trim();
            
            if(!commentText) {
                document.getElementById('error-message').textContent = 'Comment cannot be empty';
                document.getElementById('error-message').style.display = 'block';
                setTimeout(() => {
                    document.getElementById('error-message').style.display = 'none';
                }, 3000);
                return;
            }
            
            fetch('add_comment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ 
                    video_id: <?php echo $video_id; ?>,
                    comment: commentText
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // Add the new comment to the list
                    const newComment = document.createElement('div');
                    newComment.className = 'comment';
                    newComment.innerHTML = `
                        <div class="comment-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="comment-content">
                            <div class="comment-header">
                                <span class="comment-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <span class="comment-time">Just now</span>
                            </div>
                            <div class="comment-text">${commentText.replace(/\n/g, '<br>')}</div>
                        </div>
                    `;
                    
                    // Remove "no comments" message if it exists
                    const noComments = commentsList.querySelector('.no-comments');
                    if(noComments) {
                        commentsList.removeChild(noComments);
                    }
                    
                    // Add the new comment at the top
                    commentsList.insertBefore(newComment, commentsList.firstChild);
                    
                    // Clear the comment input
                    document.getElementById('comment-text').value = '';
                    
                    // Update comment count
                    const commentsHeader = document.querySelector('.comments-header');
                    const currentCount = parseInt(commentsHeader.textContent);
                    commentsHeader.textContent = (currentCount + 1) + ' Comments';
                    
                    // Show success message
                    document.getElementById('success-message').textContent = data.message;
                    document.getElementById('success-message').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('success-message').style.display = 'none';
                    }, 3000);
                } else {
                    document.getElementById('error-message').textContent = data.message;
                    document.getElementById('error-message').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('error-message').style.display = 'none';
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>
