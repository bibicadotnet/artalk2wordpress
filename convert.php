<?php
// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tăng giới hạn bộ nhớ
ini_set('memory_limit', '256M');

// Kết nối đến WordPress
require_once('/var/www/html/wp-load.php');

// Đọc file .artrans
$artrans_json = file_get_contents('backup-2025211-224210.artrans');
$artrans = json_decode($artrans_json, true);

// Kiểm tra lỗi JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Lỗi khi đọc file JSON: " . json_last_error_msg());
}

// Lấy danh sách bài viết trong WordPress và tạo bảng ánh xạ
$posts = get_posts(array(
    'numberposts' => -1,
    'post_type'   => 'post',
    'post_status' => 'publish',
));

$page_key_to_post_info = array();
foreach ($posts as $post) {
    $page_key = parse_url(get_permalink($post->ID), PHP_URL_PATH);
    $page_key_to_post_info[$page_key] = array(
        'ID' => $post->ID,
        'title' => $post->post_title,
        'link' => get_permalink($post->ID)
    );
}

// Tạo file XML với DOMDocument
$dom = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

// Tạo phần tử gốc <rss>
$rss = $dom->createElement('rss');
$rss->setAttribute('version', '2.0');
$rss->setAttribute('xmlns:excerpt', 'http://wordpress.org/export/1.2/excerpt/');
$rss->setAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
$rss->setAttribute('xmlns:wfw', 'http://wellformedweb.org/CommentAPI/');
$rss->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
$rss->setAttribute('xmlns:wp', 'http://wordpress.org/export/1.2/');
$dom->appendChild($rss);

// Tạo phần tử <channel>
$channel = $dom->createElement('channel');
$rss->appendChild($channel);

// Thêm các phần tử bắt buộc trong <channel>
$channel->appendChild($dom->createElement('title', 'Comments Export'));
$channel->appendChild($dom->createElement('link', get_site_url()));
$channel->appendChild($dom->createElement('description', 'Exported comments from Artran to WordPress'));
$channel->appendChild($dom->createElement('pubDate', date('r')));
$channel->appendChild($dom->createElement('language', 'en-US'));
$channel->appendChild($dom->createElement('wp:wxr_version', '1.2'));

// Tạo mảng để nhóm comments theo bài viết
$comments_by_post = array();
foreach ($artrans as $artran) {
    $page_key = $artran['page_key'];
    if (!isset($comments_by_post[$page_key])) {
        $comments_by_post[$page_key] = array();
    }
    $comments_by_post[$page_key][] = $artran;
}

// Lặp qua từng bài viết và thêm comments tương ứng
foreach ($comments_by_post as $page_key => $comments) {
    if (!isset($page_key_to_post_info[$page_key])) {
        echo "Không tìm thấy bài viết tương ứng với page_key: $page_key<br>";
        continue;
    }

    $post_info = $page_key_to_post_info[$page_key];
    
    $item = $dom->createElement('item');
    $channel->appendChild($item);

    // Thêm thông tin cơ bản về bài viết
    $item->appendChild($dom->createElement('title'))->appendChild($dom->createCDATASection($post_info['title']));
    $item->appendChild($dom->createElement('link', $post_info['link']));
    $item->appendChild($dom->createElement('wp:post_id', $post_info['ID']));
    $item->appendChild($dom->createElement('wp:post_type', 'post'));
    $item->appendChild($dom->createElement('wp:status', 'publish'));

    // Thêm comments cho bài viết này
    foreach ($comments as $artran) {
        $comment = $dom->createElement('wp:comment');
        $item->appendChild($comment);

        // Ánh xạ các trường và sử dụng CDATA cho các trường chứa ký tự Unicode
        $comment->appendChild($dom->createElement('wp:comment_id', $artran['id']));
        $comment->appendChild($dom->createElement('wp:comment_author'))->appendChild($dom->createCDATASection($artran['nick']));
        $comment->appendChild($dom->createElement('wp:comment_author_email'))->appendChild($dom->createCDATASection($artran['email']));
        $comment->appendChild($dom->createElement('wp:comment_author_url'))->appendChild($dom->createCDATASection($artran['link']));
        $comment->appendChild($dom->createElement('wp:comment_author_IP', $artran['ip']));
        $comment->appendChild($dom->createElement('wp:comment_date', $artran['created_at']));
        $comment->appendChild($dom->createElement('wp:comment_date_gmt', gmdate('Y-m-d H:i:s', strtotime($artran['created_at']))));
        $comment->appendChild($dom->createElement('wp:comment_content'))->appendChild($dom->createCDATASection($artran['content']));
        $comment->appendChild($dom->createElement('wp:comment_approved', $artran['is_pending'] === 'false' ? 1 : 0));
        $comment->appendChild($dom->createElement('wp:comment_parent', $artran['rid']));
        $comment->appendChild($dom->createElement('wp:comment_post_ID', $post_info['ID']));
    }
}

// Lưu file XML
$dom->save('wordpress-comments.xml');

echo "Đã tạo file XML thành công: wordpress-comments.xml";
?>
