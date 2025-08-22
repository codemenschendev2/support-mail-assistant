# Support Mail Assistant

Ứng dụng hỗ trợ xử lý email hỗ trợ khách hàng sử dụng Gmail API và hệ thống knowledge base thông minh.

## Tính năng

- **OAuth2 Authentication**: Đăng nhập an toàn với Google
- **Gmail API Integration**: Quản lý draft emails và gửi email
- **Knowledge Base**: Hệ thống tìm kiếm thông minh cho template và hướng dẫn
- **Auto-draft Generation**: Tự động tạo draft email dựa trên nội dung và knowledge base
- **Modern UI**: Giao diện Bootstrap 5 responsive

## Yêu cầu hệ thống

- PHP 8.1+
- XAMPP (Apache + MySQL)
- Composer
- Google Cloud Console project với Gmail API enabled

## Cài đặt

### 1. Clone repository
```bash
git clone <repository-url>
cd support-mail-assistant
```

### 2. Cài đặt dependencies
```bash
composer install
```

### 3. Cấu hình Google OAuth
1. Tạo project trên [Google Cloud Console](https://console.cloud.google.com/)
2. Enable Gmail API
3. Tạo OAuth 2.0 credentials
4. Copy `oauth-client.json` vào thư mục `credentials/`

### 4. Cấu hình môi trường
```bash
cp .env.example .env
# Chỉnh sửa .env với thông tin Google OAuth của bạn
```

### 5. Cấu hình Apache
Đảm bảo thư mục project nằm trong `htdocs` của XAMPP và có thể truy cập qua `http://localhost/support-mail-assistant/`

## Cấu trúc thư mục

```
support-mail-assistant/
├── .env.example          # Template biến môi trường
├── bootstrap.php         # Khởi tạo ứng dụng
├── google_client.php     # Cấu hình Google API client
├── helpers/              # Các helper classes
│   ├── Env.php          # Quản lý biến môi trường
│   ├── Html.php         # Helper HTML
│   ├── Response.php     # HTTP response helper
│   ├── GmailMime.php    # MIME message helper
│   └── Knowledge.php    # Knowledge base helper
├── knowledge/            # Knowledge base data
│   └── knowledge.json   # Dữ liệu knowledge base
├── oauth/                # OAuth authentication
│   ├── start.php        # Bắt đầu OAuth flow
│   └── callback.php     # Xử lý OAuth callback
├── endpoints/            # API endpoints
│   ├── check_and_draft.php  # Kiểm tra và tạo draft
│   ├── list_drafts.php      # Liệt kê draft emails
│   └── send_draft.php       # Gửi draft email
├── views/                # Giao diện người dùng
│   ├── layout.php       # Template chính
│   └── drafts_list.php  # Trang danh sách draft
├── credentials/          # Thông tin xác thực
│   ├── oauth-client.json # Google OAuth credentials
│   └── token.json       # OAuth tokens (tự động tạo)
├── vendor/               # Composer dependencies
└── README.md            # Tài liệu này
```

## Sử dụng

### 1. Khởi động ứng dụng
Truy cập `http://localhost/support-mail-assistant/`

### 2. Đăng nhập Google
Click "Đăng nhập Google" để xác thực OAuth2

### 3. Quản lý Draft Emails
- Xem danh sách draft emails
- Tạo draft mới từ knowledge base
- Gửi draft emails

### 4. Quản lý Knowledge Base
Chỉnh sửa `knowledge/knowledge.json` để thêm/sửa/xóa các mẫu và hướng dẫn.

## API Endpoints

### POST /endpoints/check_and_draft.php
Tạo draft email dựa trên nội dung và knowledge base.

**Request:**
```json
{
  "email_content": "Nội dung email cần xử lý",
  "subject": "Chủ đề email"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "draft_content": "Nội dung draft được tạo",
    "mime_message": "MIME message cho Gmail API",
    "knowledge_results": [...]
  }
}
```

### GET /endpoints/list_drafts.php
Lấy danh sách draft emails từ Gmail.

### POST /endpoints/send_draft.php
Gửi draft email.

**Request:**
```json
{
  "draft_id": "draft_id_from_gmail"
}
```

## Bảo mật

- Sử dụng OAuth2 thay vì username/password
- Tokens được lưu an toàn trong session và file
- Tất cả input được escape để tránh XSS
- CSRF protection thông qua OAuth state parameter

## Phát triển

### Thêm helper mới
Tạo file mới trong thư mục `helpers/` với cấu trúc:
```php
<?php
declare(strict_types=1);

class NewHelper
{
    // Implementation
}
```

### Thêm endpoint mới
Tạo file mới trong thư mục `endpoints/` và đảm bảo:
- Sử dụng `declare(strict_types=1);`
- Include `bootstrap.php`
- Xử lý authentication
- Sử dụng `Response` helper cho output

## Troubleshooting

### Lỗi OAuth
- Kiểm tra `credentials/oauth-client.json` có đúng format
- Đảm bảo redirect URI trong Google Console khớp với `.env`
- Kiểm tra Gmail API đã được enable

### Lỗi Permission
- Đảm bảo thư mục `credentials/` có quyền ghi
- Kiểm tra Apache có quyền đọc project files

## Đóng góp

1. Fork repository
2. Tạo feature branch
3. Commit changes
4. Push to branch
5. Tạo Pull Request

## License

MIT License - xem file LICENSE để biết thêm chi tiết.
