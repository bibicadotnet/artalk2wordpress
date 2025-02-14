# artalk2wordpress

### Bước 1: trên Artalk xuất ra file backup
Vào /sidebar/#/transfer, ấn vào Export, nó sẽ tạo ra file dạng backup-2025211-224210.artrans

### Bước 2: trên WordPress
Tạo thư mục artrans2wordpress, chmod thành 777

Bên trong tạo 1 file php convert.php có nội dung như bên dưới

Sửa lại nội dung dòng 11 và 14 theo thông tin của bạn

Khi chạy convert.php nó sẽ tạo ra file wordpress-comments.xml

### Bước 3: import vào WordPress
Trước khi import có thể xóa tất cả comment đang có trên WordPress bằng WP-CLI cho sạch sẽ
```
export WP_CLI_ALLOW_ROOT=1
wp comment delete $(wp comment list --format=ids) --force --allow-root
```
Lúc này có thể vào WordPress, Tools -> Import, chọn file wordpress-comments.xml để thêm vào các comment từ Artalk , khi chạy sẽ thấy vài cảnh báo, liên quan tới tài khoản hay bài viết đã có gì đó, nhưng không ảnh hưởng tới hiệu quả, cứ ấn đồng ý import là được
